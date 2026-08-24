<?php
declare(strict_types=1);

namespace MiniErp\Services;

use MiniErp\Fiscal\{A1CertificateInspector,FiscalArtifactStorage,FiscalMasterKey,LocalEncryptedSecretStorage,OperationalCertificateProvider,PrivateCertificateStorage};
use MiniErp\Repositories\{FiscalConfigurationRepository,FiscalDocumentEventRepository,FiscalOperationRepository};
use PDO;

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
        return new OfflineFiscalDocumentPipelineService(
            $pdo,
            new FiscalOperationRepository($pdo, $tenantId),
            $configuration,
            $certificate,
            artifactStorage: new FiscalArtifactStorage($storageRoot . '/storage/fiscal/artifacts'),
            events: new FiscalDocumentEventRepository($pdo, $tenantId),
        );
    }
}
