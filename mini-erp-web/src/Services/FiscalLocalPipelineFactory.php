<?php
declare(strict_types=1);

namespace MiniErp\Services;

use MiniErp\Fiscal\{A1CertificateInspector,FiscalArtifactStorage,FiscalMasterKey,LocalEncryptedSecretStorage,OperationalCertificateProvider,PrivateCertificateStorage};
use MiniErp\Repositories\{FiscalConfigurationRepository,FiscalDocumentEventRepository,FiscalOperationRepository};
use PDO;
use MiniErp\Repositories\PlatformServerSettingsRepository;

final class FiscalLocalPipelineFactory
{
    public static function create(PDO $pdo, int $tenantId, string $root): OfflineFiscalDocumentPipelineService
    {
        $storageRoot = $root;
        $testRoot = getenv('FISCAL_TEST_STORAGE_ROOT');
        if (getenv('APP_ENV') === 'testing' && is_string($testRoot) && $testRoot !== '') {
            $storageRoot = rtrim($testRoot, '/\\');
        }
        $configuration = new FiscalConfigurationRepository($pdo, $tenantId);
        $certificate = new OperationalCertificateProvider(
            new A1CertificateInspector(),
            new PrivateCertificateStorage($storageRoot . '/storage/fiscal/certificates'),
            new LocalEncryptedSecretStorage($storageRoot . '/storage/fiscal/secrets', FiscalMasterKey::resolve($storageRoot)),
            $configuration,
        );
        $technicalResponsible=[];
        try{
            $appConfig=require $root.'/config.php';$db=$appConfig['db'];
            $main=new PDO("mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset=utf8mb4",$db['username'],$db['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $sefaz=(new PlatformServerSettingsRepository($main))->sefazTechnical();
            if($sefaz['sefaz_technical_cnpj']!=='')$technicalResponsible=['cnpj'=>$sefaz['sefaz_technical_cnpj'],'contact'=>$sefaz['sefaz_technical_contact'],'email'=>$sefaz['sefaz_technical_email'],'phone'=>$sefaz['sefaz_technical_phone'],'idCSRT'=>$sefaz['sefaz_csrt_id'],'CSRT'=>(string)(getenv($sefaz['sefaz_csrt_env'])?:'')];
        }catch(\Throwable){}
        return new OfflineFiscalDocumentPipelineService(
            $pdo,
            new FiscalOperationRepository($pdo, $tenantId),
            $configuration,
            $certificate,
            artifactStorage: new FiscalArtifactStorage($storageRoot . '/storage/fiscal/artifacts'),
            events: new FiscalDocumentEventRepository($pdo, $tenantId),
            technicalResponsible: $technicalResponsible,
        );
    }
}
