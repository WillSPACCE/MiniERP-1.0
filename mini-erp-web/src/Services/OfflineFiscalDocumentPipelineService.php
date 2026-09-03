<?php

declare(strict_types=1);

namespace MiniErp\Services;

use MiniErp\Fiscal\FiscalArtifactStorage;
use MiniErp\Fiscal\FiscalNfeXmlBuilder;
use MiniErp\Fiscal\FiscalNumberAllocator;
use MiniErp\Fiscal\FiscalXmlSigner;
use MiniErp\Fiscal\NfeAccessKeyGenerator;
use MiniErp\Fiscal\OfficialNfeXsdValidator;
use MiniErp\Fiscal\OperationalCertificateProvider;
use MiniErp\Repositories\FiscalArtifactRepository;
use MiniErp\Repositories\FiscalConfigurationRepository;
use MiniErp\Repositories\FiscalNumberReservationRepository;
use MiniErp\Repositories\FiscalOperationRepository;
use MiniErp\Repositories\FiscalDocumentEventRepository;
use PDO;
use RuntimeException;

final class OfflineFiscalDocumentPipelineService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ?FiscalOperationRepository $operations = null,
        private readonly ?FiscalConfigurationRepository $configurationRepo = null,
        private readonly ?OperationalCertificateProvider $certificateProvider = null,
        private readonly ?FiscalNumberAllocator $allocator = null,
        private readonly ?FiscalArtifactStorage $artifactStorage = null,
        private readonly ?OfficialNfeXsdValidator $xsdValidator = null,
        private readonly ?FiscalXmlSigner $xmlSigner = null,
        private readonly ?NfeAccessKeyGenerator $keyGenerator = null,
        private readonly ?FiscalDocumentDTOFactory $dtoFactory = null,
        private readonly ?FiscalNfeXmlBuilder $xmlBuilder = null,
        private readonly ?FiscalDocumentEventRepository $events = null,
        private readonly ?FiscalDocumentPreflightService $preflight = null,
        private readonly array $technicalResponsible = [],
    ) {}

    public function prepare(int $tenantId, int $fiscalDocumentId, int $actorId): array
    {
        $operations = $this->operations ?? new FiscalOperationRepository($this->pdo, $tenantId);
        $configurationRepo = $this->configurationRepo ?? new FiscalConfigurationRepository($this->pdo, $tenantId);
        $certificateProvider = $this->certificateProvider ?? new OperationalCertificateProvider(
            new \MiniErp\Fiscal\A1CertificateInspector(),
            new \MiniErp\Fiscal\PrivateCertificateStorage(sys_get_temp_dir() . '/minierp-certificates'),
            new \MiniErp\Fiscal\LocalEncryptedSecretStorage(sys_get_temp_dir() . '/minierp-secrets', 'MINIERP_FISCAL_SECRET_KEY_32_BYTES_1234'),
            $configurationRepo,
        );
        $allocator = $this->allocator ?? new FiscalNumberAllocator($this->pdo, $tenantId);
        $artifactStorage = $this->artifactStorage ?? new FiscalArtifactStorage(sys_get_temp_dir() . '/minierp-fiscal-artifacts');
        $xsdValidator = $this->xsdValidator ?? new OfficialNfeXsdValidator(__DIR__ . '/../../resources/fiscal/xsd/nfe/010e-v1.02/NFe');
        $xmlSigner = $this->xmlSigner ?? new FiscalXmlSigner();
        $keyGenerator = $this->keyGenerator ?? new NfeAccessKeyGenerator();
        $dtoFactory = $this->dtoFactory ?? new FiscalDocumentDTOFactory();
        $xmlBuilder = $this->xmlBuilder ?? new FiscalNfeXmlBuilder();
        $reservationRepo = new FiscalNumberReservationRepository($this->pdo, $tenantId);
        $artifactRepo = new FiscalArtifactRepository($this->pdo, $tenantId);
        $event = fn(string $type, string $stage, string $status, ?string $code = null, ?string $message = null, array $metadata = []) =>
            $this->events?->append($fiscalDocumentId, $type, $stage, $status, $code, $message, $metadata, $actorId);

        $document = $operations->document($fiscalDocumentId);
        if ((int) ($document['tenant_id'] ?? 0) !== $tenantId) {
            throw new RuntimeException('DOCUMENT_NOT_FOUND');
        }
        $event('PRECHECK_STARTED', 'PRECHECK', 'PROCESSING', null, 'Validação fiscal local iniciada.');
        if (!in_array(($document['status'] ?? ''), ['FISCAL_READY', 'PENDING_TRANSMISSION'], true)) {
            $event('PRECHECK_FAILED', 'PRECHECK', 'FAILED', 'FISCAL_PENDING', implode('; ', $document['pending'] ?? []));
            throw new RuntimeException('FISCAL_PENDING');
        }
        $event('PRECHECK_OK', 'PRECHECK', 'OK', 'FISCAL_READY', 'Documento apto ao pipeline local.');

        $documentVersion = (int) ($document['document_version'] ?? 1);
        $lockName = sprintf('mini-erp:fiscal:%d:%d:%d', $tenantId, $fiscalDocumentId, $documentVersion);
        if (!$this->acquireLock($lockName, 5)) {
            throw new RuntimeException('PIPELINE_BUSY');
        }

        $reservation = null;
        try {
            $order = $operations->order((int) ($document['source_order_id'] ?? 0));
            $establishmentId = (int) ($order['establishment_id'] ?? 0);
            if ($establishmentId <= 0) {
                throw new RuntimeException('DOCUMENT_NOT_FOUND');
            }

            $preflightDocument = $document;
            $preflightDocument['totals']['model'] = (string) ($document['totals']['model'] ?? $order['fiscal_model'] ?? '');
            ($this->preflight ?? new FiscalDocumentPreflightService())->assertReady($preflightDocument);
            $event('PREFLIGHT_OK', 'PREFLIGHT', 'OK', 'READY', 'Cadastros e tributação validados antes da reserva fiscal.');

            $issuer = $document['issuer_snapshot'] ?? [];
            $issuerTaxId = (string) ($issuer['tax_id'] ?? $issuer['cnpj'] ?? '');
            $model = (string) (($document['totals']['model'] ?? $order['fiscal_model'] ?? '55'));
            if (!in_array($model, ['55', '65'], true)) {
                throw new RuntimeException('MODEL_NOT_SUPPORTED');
            }

            $existingArtifact = $artifactRepo->findValidArtifact($fiscalDocumentId, $documentVersion, 'NFE');
            if ($existingArtifact !== null) {
                $storageReference = (string) ($existingArtifact['storage_reference'] ?? '');
                if ($storageReference === '') {
                    throw new RuntimeException('ARTIFACT_FILE_MISSING');
                }
                try {
                    $artifactPath = $artifactStorage->resolve($storageReference);
                } catch (\Throwable) {
                    throw new RuntimeException('ARTIFACT_FILE_MISSING');
                }
                if (!is_file($artifactPath)) {
                    throw new RuntimeException('ARTIFACT_FILE_MISSING');
                }
                $expectedHash = (string) ($existingArtifact['sha256'] ?? '');
                $actualHash = hash_file('sha256', $artifactPath);
                if ($expectedHash !== '' && $actualHash !== $expectedHash) {
                    throw new RuntimeException('ARTIFACT_INTEGRITY_FAILED');
                }
                $reservation = $reservationRepo->findById((int) ($existingArtifact['number_reservation_id'] ?? 0));
                $event('LOCAL_PIPELINE_COMPLETED', 'PIPELINE', 'AGUARDANDO_TRANSMISSAO', 'IDEMPOTENT_REUSE', 'XML local válido reutilizado.');
                return [
                    'tenant_id' => $tenantId,
                    'document_id' => $fiscalDocumentId,
                    'establishment_id' => $establishmentId,
                    'certificate_id' => (int) ($existingArtifact['certificate_id'] ?? 0),
                    'reservation_id' => (int) ($reservation['id'] ?? $existingArtifact['number_reservation_id'] ?? 0),
                    'artifact_id' => (int) ($existingArtifact['id'] ?? 0),
                    'model' => $model,
                    'series' => (int) ($existingArtifact['series'] ?? 0),
                    'number' => (int) ($existingArtifact['number'] ?? 0),
                    'access_key' => (string) ($existingArtifact['access_key'] ?? ''),
                    'cNF' => (string) ($reservation['cnf'] ?? ''),
                    'status' => 'XSD_VALID_OFFLINE',
                    'artifact' => ['storage_reference' => $storageReference, 'sha256' => $actualHash, 'size' => filesize($artifactPath)],
                    'sha256' => $actualHash,
                    'idempotent' => true,
                ];
            }

            $certificate = $certificateProvider->resolveOperationalCertificate($establishmentId, $issuerTaxId);

            $series = $this->resolveSeries($configurationRepo, $establishmentId, $model, 2);
            $reservation = $reservationRepo->findByDocumentVersion($fiscalDocumentId, $documentVersion);
            if ($reservation === null) {
                $allocator->reserve($establishmentId, $model, (int) $series['series'], (int) $fiscalDocumentId, $actorId, $documentVersion);
                $reservation = $reservationRepo->findByDocumentVersion($fiscalDocumentId, $documentVersion);
                if ($reservation !== null) $event('NUMBER_RESERVED', 'NUMBER', 'OK', null, 'Numeração fiscal reservada.', ['reservation_id' => (int)$reservation['id']]);
            }
            if ($reservation === null) {
                throw new RuntimeException('RESERVATION_NOT_CREATED');
            }

            $reservationId = (int) $reservation['id'];
            $reservationRepo->updateStatus($reservationId, 'BUILDING');
            $reservation = $reservationRepo->findById($reservationId);

            $number = (int) ($reservation['number'] ?? 0);
            if ($number <= 0) {
                $number = (int) ($reservation['fiscal_number'] ?? 0);
            }
            if ($number <= 0) {
                throw new RuntimeException('RESERVATION_NUMBER_MISSING');
            }

            $numericCode = $this->numericCodeForReservation($tenantId, $establishmentId, $fiscalDocumentId, $reservationId, $model, (int) $series['series'], $number);
            $accessKey = (string) ($reservation['access_key'] ?? '');
            if ($accessKey === '') {
                $accessKey = $keyGenerator->generate(
                    $this->ufCode($issuer),
                    date('ym'),
                    $issuerTaxId,
                    $model,
                    (int) $series['series'],
                    $number,
                    1,
                    $numericCode,
                );
            }

            $reservationRepo->updateRuntimeMetadata($reservationId, [
                'fiscal_document_version' => $documentVersion,
                'fiscal_series_id' => (int) ($series['id'] ?? 0),
                'model' => $model,
                'environment' => (int) ($series['environment'] ?? 2),
                'series' => (int) $series['series'],
                'number' => $number,
                'cnf' => $numericCode,
                'access_key' => $accessKey,
                'status' => 'BUILDING',
                'idempotency_key' => (string) ($document['idempotency_key'] ?? sprintf('fiscal:%d:%d:%d', $tenantId, $fiscalDocumentId, $documentVersion)),
                'establishment_id' => $establishmentId,
            ]);
            $reservation = $reservationRepo->findById($reservationId);

            $dto = $dtoFactory->create($document, $tenantId, [
                'model' => $model,
                'series' => (int) $series['series'],
                'number' => $number,
                'access_key' => $accessKey,
            ], [
                'series' => (int) $series['series'],
                'number' => $number,
                'access_key' => $accessKey,
                'numeric_code' => $numericCode,
                'uf_code' => $this->ufCode($issuer),
                'issued_at' => date('c'),
                'environment' => (int) ($series['environment'] ?? 2),
                'emission_type' => 1,
                'destination_scope' => 1,
                'process_version' => 'MiniERP-FISCAL-06B-C3',
            ]);

            $event('XML_BUILD_STARTED', 'XML_BUILD', 'PROCESSING', null, 'Geração do XML iniciada.');
            $unsignedXml = $xmlBuilder->build($dto, [
                'access_key' => $accessKey,
                'uf_code' => $this->ufCode($issuer),
                'numeric_code' => $numericCode,
                'series' => (int) $series['series'],
                'number' => $number,
                'issued_at' => date('c'),
                'environment' => (int) ($series['environment'] ?? 2),
                'emission_type' => 1,
                'destination_scope' => 1,
                'process_version' => 'MiniERP-FISCAL-06B-C3',
                'technical_responsible' => $this->technicalResponsible,
            ]);
            $event('XML_GENERATED', 'XML_BUILD', 'OK', null, 'XML gerado localmente.');

            $event('SIGN_STARTED', 'SIGN', 'PROCESSING', null, 'Assinatura digital iniciada.');
            $signed = $xmlSigner->signTestOnly($unsignedXml, $certificate['content'], (string) $certificate['password']);
            if (!$xmlSigner->verify($signed['xml'])) {
                $event('XMLDSIG_INVALID', 'XMLDSIG', 'FAILED', 'XML_SIGNATURE_INVALID', 'Assinatura XML inválida.');
                throw new RuntimeException('XML_SIGNATURE_INVALID');
            }
            $event('SIGNED', 'SIGN', 'OK', null, 'XML assinado.');
            $event('XMLDSIG_VALID', 'XMLDSIG', 'OK', null, 'Assinatura XML validada.');
            $xsdValidator->validate($signed['xml']);
            $event('XSD_VALID', 'XSD', 'OK', null, 'XML validado contra o XSD oficial.');

            $artifactInfo = $artifactStorage->storeSignedXml($tenantId, $establishmentId, $fiscalDocumentId, $signed['xml'], 'NFE');
            $storagePath = $artifactStorage->resolve($artifactInfo['storage_reference']);
            if (!is_file($storagePath)) {
                throw new RuntimeException('ARTIFACT_FILE_MISSING');
            }
            $sha256 = hash_file('sha256', $storagePath);
            if ($sha256 !== hash('sha256', $signed['xml'])) {
                throw new RuntimeException('ARTIFACT_INTEGRITY_FAILED');
            }

            $artifactRecord = $artifactRepo->create([
                'establishment_id' => $establishmentId,
                'fiscal_document_id' => $fiscalDocumentId,
                'fiscal_document_version' => $documentVersion,
                'certificate_id' => (int) ($certificate['certificate_id'] ?? 0),
                'number_reservation_id' => $reservationId,
                'model' => $model,
                'environment' => (int) ($series['environment'] ?? 2),
                'series' => (int) $series['series'],
                'number' => $number,
                'access_key' => $accessKey,
                'artifact_type' => 'NFE',
                'status' => 'XSD_VALID_OFFLINE',
                'schema_package' => 'nfe',
                'schema_version' => OfficialNfeXsdValidator::VERSION,
                'schema_checksum' => hash('sha256', $signed['xml']),
                'storage_reference' => $artifactInfo['storage_reference'],
                'sha256' => $sha256,
                'size_bytes' => (int) ($artifactInfo['size'] ?? filesize($storagePath)),
                'created_by' => $actorId,
            ]);
            $event('ARTIFACT_STORED', 'ARTIFACT', 'OK', null, 'Artifact XML armazenado com integridade verificada.', ['artifact_id' => (int)($artifactRecord['id'] ?? 0)]);

            $reservationRepo->updateStatus($reservationId, 'ARTIFACT_CREATED');
            $reservation = $reservationRepo->findById($reservationId);

            $this->pdo->prepare("UPDATE fiscal_documents SET status='PENDING_TRANSMISSION' WHERE tenant_id=? AND id=?")->execute([$tenantId, $fiscalDocumentId]);
            $event('DANFE_READY', 'DANFE', 'OK', null, 'DANFE disponível para geração local.');
            $event('LOCAL_PIPELINE_COMPLETED', 'PIPELINE', 'AGUARDANDO_TRANSMISSAO', null, 'XML gerado, assinado e validado offline. Aguardando transmissão.');
            return [
                'tenant_id' => $tenantId,
                'document_id' => $fiscalDocumentId,
                'establishment_id' => $establishmentId,
                'certificate_id' => (int) ($certificate['certificate_id'] ?? 0),
                'reservation_id' => $reservationId,
                'artifact_id' => (int) ($artifactRecord['id'] ?? 0),
                'model' => $model,
                'series' => (int) $series['series'],
                'number' => $number,
                'access_key' => $accessKey,
                'cNF' => $numericCode,
                'status' => 'XSD_VALID_OFFLINE',
                'artifact' => [
                    'storage_reference' => $artifactInfo['storage_reference'],
                    'sha256' => $sha256,
                    'size' => (int) ($artifactInfo['size'] ?? filesize($storagePath)),
                ],
                'sha256' => $sha256,
                'idempotent' => false,
            ];
        } catch (\Throwable $e) {
            if ($reservation !== null) {
                $reservationRepo->updateStatus((int) $reservation['id'], 'FAILED');
            }
            $code = FiscalErrorSanitizer::code($e);
            $event('LOCAL_PIPELINE_FAILED', 'PIPELINE', 'FAILED', $code, FiscalErrorSanitizer::message($code));
            throw $e;
        } finally {
            $this->releaseLock($lockName);
        }
    }

    public function prepareForDocument(int $tenantId, int $fiscalDocumentId, int $actorId): array
    {
        return $this->prepare($tenantId, $fiscalDocumentId, $actorId);
    }

    private function resolveSeries(FiscalConfigurationRepository $repo, int $establishmentId, string $model, int $environment): array
    {
        foreach ($repo->series($establishmentId) as $series) {
            if ((string) $series['model'] === $model && (int) $series['environment'] === $environment && (int) $series['active'] === 1) {
                return $series;
            }
        }

        throw new RuntimeException('SERIES_NOT_CONFIGURED: Configure uma série fiscal de homologação para este modelo.');
    }

    private function numericCodeForReservation(int $tenantId, int $establishmentId, int $fiscalDocumentId, int $reservationId, string $model, int $series, int $number): string
    {
        $base = sprintf('%d:%d:%d:%s:%d:%d', $tenantId, $establishmentId, $fiscalDocumentId, $model, $series, $number);
        $hash = substr(hash('sha256', $base), 0, 8);
        $code = (int) hexdec($hash) % 100000000;
        return str_pad((string) $code, 8, '0', STR_PAD_LEFT);
    }

    private function ufCode(array $issuer): string
    {
        $explicit = preg_replace('/\D/', '', (string)($issuer['state_code'] ?? '')) ?? '';
        if (strlen($explicit) === 2) return $explicit;
        $uf = strtoupper(trim((string)($issuer['state'] ?? $issuer['uf'] ?? '')));
        $codes = ['RO'=>'11','AC'=>'12','AM'=>'13','RR'=>'14','PA'=>'15','AP'=>'16','TO'=>'17','MA'=>'21','PI'=>'22','CE'=>'23','RN'=>'24','PB'=>'25','PE'=>'26','AL'=>'27','SE'=>'28','BA'=>'29','MG'=>'31','ES'=>'32','RJ'=>'33','SP'=>'35','PR'=>'41','SC'=>'42','RS'=>'43','MS'=>'50','MT'=>'51','GO'=>'52','DF'=>'53'];
        if (!isset($codes[$uf])) throw new RuntimeException('ISSUER_STATE_CODE_INVALID');
        return $codes[$uf];
    }

    private function acquireLock(string $lockName, int $timeoutSeconds): bool
    {
        $stmt = $this->pdo->query('SELECT GET_LOCK(' . $this->pdo->quote($lockName) . ', ' . (int) $timeoutSeconds . ')');
        $result = $stmt ? (int) $stmt->fetchColumn() : 0;
        return $result === 1;
    }

    private function releaseLock(string $lockName): void
    {
        try {
            $this->pdo->query('SELECT RELEASE_LOCK(' . $this->pdo->quote($lockName) . ')');
        } catch (\Throwable) {
            // lock is best-effort; do not throw during cleanup
        }
    }
}
