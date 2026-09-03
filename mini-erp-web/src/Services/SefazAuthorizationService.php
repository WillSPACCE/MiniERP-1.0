<?php
declare(strict_types=1);

namespace MiniErp\Services;

use MiniErp\Adapters\NfePhpSefazAuthorizationClient;
use MiniErp\Fiscal\FiscalArtifactStorage;
use MiniErp\Fiscal\OperationalCertificateProvider;
use MiniErp\Repositories\FiscalArtifactRepository;
use MiniErp\Repositories\FiscalDocumentEventRepository;
use PDO;
use RuntimeException;

final class SefazAuthorizationService
{
    public function __construct(
        private PDO $pdo,
        private int $tenantId,
        private FiscalArtifactRepository $artifacts,
        private FiscalDocumentEventRepository $events,
        private FiscalArtifactStorage $storage,
        private OperationalCertificateProvider $certificates,
        private NfePhpSefazAuthorizationClient $client = new NfePhpSefazAuthorizationClient(),
    ) {}

    public function transmitHomologation(int $documentId, int $actorId): array
    {
        $document = $this->one('SELECT * FROM fiscal_documents WHERE tenant_id=? AND id=?', [$this->tenantId, $documentId]);
        if (!$document) throw new RuntimeException('DOCUMENT_NOT_FOUND');
        $version = (int)$document['document_version'];
        if ($authorized = $this->artifacts->findByDocumentVersion($documentId, $version, 'NFE_PROC')) {
            return ['authorized'=>true,'idempotent'=>true,'artifact_id'=>(int)$authorized['id'],'status'=>'AUTHORIZED'];
        }
        $artifact = $this->artifacts->findByDocumentVersion($documentId, $version, 'NFE');
        if (!$artifact || !in_array((string)$artifact['status'], ['XSD_VALID_OFFLINE','PENDING_TRANSMISSION','READY','REJECTED'], true)) throw new RuntimeException('SIGNED_XML_NOT_READY');
        if ((int)$artifact['environment'] !== 2) throw new RuntimeException('SEFAZ_PRODUCTION_BLOCKED');
        if ((string)$artifact['model'] !== '55') throw new RuntimeException('SEFAZ_MODEL_NOT_SUPPORTED');

        $establishment = $this->one('SELECT * FROM establishments WHERE tenant_id=? AND id=?', [$this->tenantId, (int)$artifact['establishment_id']]);
        if (!$establishment) throw new RuntimeException('SEFAZ_REGISTRATION_INCOMPLETE');
        $xml = $this->storage->read((string)$artifact['storage_reference']);
        $this->storage->assertIntegrity((string)$artifact['storage_reference'], (string)$artifact['sha256']);
        if (!$this->isHomologationXml($xml)) throw new RuntimeException('SEFAZ_PRODUCTION_BLOCKED');

        $loaded = $this->certificates->resolveOperationalCertificate((int)$establishment['id'], (string)$establishment['tax_id']);
        $config = json_encode([
            'atualizacao'=>date('Y-m-d H:i:s'),'tpAmb'=>2,'razaosocial'=>(string)$establishment['legal_name'],
            'siglaUF'=>(string)$establishment['state'],'cnpj'=>(string)$establishment['tax_id'],
            'schemes'=>'PL_010_V1.30','versao'=>'4.00',
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $batchId = substr(str_pad((string)$this->tenantId . (string)$documentId, 15, '0', STR_PAD_LEFT), -15);
        $result = null;
        if ((string)$artifact['status'] === 'REJECTED') {
            $lookup = $this->client->consult($config, $loaded['certificate'], (string)$artifact['access_key'], $xml);
            $this->events->append($documentId, 'SEFAZ_KEY_CONSULTED', 'CONSULTATION', $lookup['authorized']?'AUTHORIZED':'NOT_AUTHORIZED', (string)$lookup['cstat'], (string)$lookup['reason'], ['environment'=>2], $actorId);
            if ($lookup['authorized']) $result = $lookup;
        }
        if ($result === null) {
            $this->events->append($documentId, 'SEFAZ_SUBMIT_STARTED', 'TRANSMISSION', 'PROCESSING', null, 'Envio para autorização em homologação iniciado.', ['environment'=>2,'batch_id'=>$batchId], $actorId);
            $result = $this->client->authorize($config, $loaded['certificate'], $xml, $batchId);
        }
        if ($result['cstat'] === '') throw new RuntimeException('SEFAZ_INVALID_RESPONSE');
        $metadata = ['environment'=>2,'batch_cstat'=>$result['batch_cstat'],'cstat'=>$result['cstat'],'protocol'=>$result['protocol'],'receipt'=>$result['receipt']];
        if ($result['authorized']) {
            $stored = $this->storage->storeAuthorizedXml($this->tenantId, (int)$artifact['establishment_id'], $documentId, (string)$result['authorized_xml']);
            $authorized = $this->artifacts->create([
                'establishment_id'=>(int)$artifact['establishment_id'],'fiscal_document_id'=>$documentId,'fiscal_document_version'=>$version,
                'certificate_id'=>(int)$loaded['certificate_id'],'number_reservation_id'=>(int)$artifact['number_reservation_id'],
                'model'=>'55','environment'=>2,'series'=>(int)$artifact['series'],'number'=>(int)$artifact['number'],
                'access_key'=>(string)$artifact['access_key'],'artifact_type'=>'NFE_PROC','status'=>'AUTHORIZED',
                'schema_package'=>'nfe','schema_version'=>(string)$artifact['schema_version'],'schema_checksum'=>hash('sha256',(string)$result['authorized_xml']),
                'storage_reference'=>$stored['storage_reference'],'sha256'=>$stored['sha256'],'size_bytes'=>$stored['size'],'created_by'=>$actorId,
            ]);
            $this->artifacts->updateStatus((int)$artifact['id'], 'AUTHORIZED');
            $this->pdo->prepare("UPDATE fiscal_documents SET status='AUTHORIZED' WHERE tenant_id=? AND id=?")->execute([$this->tenantId,$documentId]);
            $this->events->append($documentId, 'SEFAZ_AUTHORIZED', 'AUTHORIZATION', 'AUTHORIZED', (string)$result['cstat'], (string)$result['reason'], $metadata, $actorId);
            return ['authorized'=>true,'idempotent'=>false,'artifact_id'=>(int)$authorized['id'],'status'=>'AUTHORIZED','cstat'=>$result['cstat'],'reason'=>$result['reason'],'protocol'=>$result['protocol']];
        }

        $status = $result['processing'] ? 'PROCESSING' : ($result['denied'] ? 'DENIED' : 'REJECTED');
        $this->artifacts->updateStatus((int)$artifact['id'], $status);
        $this->pdo->prepare('UPDATE fiscal_documents SET status=? WHERE tenant_id=? AND id=?')->execute([$status,$this->tenantId,$documentId]);
        $this->events->append($documentId, $result['processing']?'SEFAZ_PROCESSING':'SEFAZ_REJECTED', 'AUTHORIZATION', $status, (string)$result['cstat'], (string)$result['reason'], $metadata, $actorId);
        return ['authorized'=>false,'idempotent'=>false,'artifact_id'=>(int)$artifact['id'],'status'=>$status,'cstat'=>$result['cstat'],'reason'=>$result['reason'],'protocol'=>$result['protocol']];
    }

    private function one(string $sql, array $params): array { $statement=$this->pdo->prepare($sql);$statement->execute($params);return $statement->fetch(PDO::FETCH_ASSOC)?:[]; }
    private function isHomologationXml(string $xml): bool { $dom=new \DOMDocument();return @$dom->loadXML($xml,LIBXML_NONET)&&(string)$dom->getElementsByTagName('tpAmb')->item(0)?->nodeValue==='2'; }
}
