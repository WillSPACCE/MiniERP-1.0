<?php

declare(strict_types=1);
if (getenv('RUN_FISCAL_MARIADB_TESTS') !== '1') {
    echo "FiscalPipelineRetryTest SKIPPED\n";
    exit;
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/helpers/FiscalPipelineTestSupport.php';

[$server, $pdo, $database] = fiscal_test_db();
$tenantId = 991003;
$establishmentId = 991103;
$taxId = '12345678000195';

try {
    $cert = fiscal_seed_certificate($pdo, $tenantId, $establishmentId, $taxId, 'RETRY TEST');
    $seed = fiscal_seed_document($pdo, $tenantId, $establishmentId, '55', 1);

    $invalidValidator = new MiniErp\Fiscal\OfficialNfeXsdValidator(__DIR__ . '/missing-xsd');
    $configRepo = new MiniErp\Repositories\FiscalConfigurationRepository($pdo, $tenantId);
    $provider = new MiniErp\Fiscal\OperationalCertificateProvider(
        new MiniErp\Fiscal\A1CertificateInspector(),
        new MiniErp\Fiscal\PrivateCertificateStorage($cert['storage_root'] . '/certs'),
        new MiniErp\Fiscal\LocalEncryptedSecretStorage($cert['storage_root'] . '/secrets', str_repeat('S', 32)),
        $configRepo,
    );
    $serviceFail = new MiniErp\Services\OfflineFiscalDocumentPipelineService(
        $pdo,
        new MiniErp\Repositories\FiscalOperationRepository($pdo, $tenantId),
        $configRepo,
        $provider,
        null,
        new MiniErp\Fiscal\FiscalArtifactStorage(sys_get_temp_dir() . '/minierp-fiscal-retry-' . bin2hex(random_bytes(4))),
        $invalidValidator,
        new MiniErp\Fiscal\FiscalXmlSigner(),
        new MiniErp\Fiscal\NfeAccessKeyGenerator(),
        new MiniErp\Services\FiscalDocumentDTOFactory(),
        new MiniErp\Fiscal\FiscalNfeXmlBuilder(),
    );

    try {
        $serviceFail->prepare($tenantId, (int) $seed['document_id'], 999);
        throw new RuntimeException('expected pipeline failure');
    } catch (RuntimeException $e) {
        $msg = strtolower($e->getMessage());
        if (!str_contains($msg, 'xsd') && !str_contains($msg, 'xml') && !str_contains($msg, 'not found')) {
            throw $e;
        }
    }

    $reservationRepo = new MiniErp\Repositories\FiscalNumberReservationRepository($pdo, $tenantId);
    $reservation = $pdo->query("SELECT * FROM fiscal_number_reservations WHERE tenant_id={$tenantId} AND fiscal_document_id={$seed['document_id']}")->fetch(PDO::FETCH_ASSOC);
    fiscal_assert($reservation !== false, 'reservation not created before retry');
    fiscal_assert((string) ($reservation['status'] ?? '') === 'FAILED' || (string) ($reservation['status'] ?? '') === 'BUILDING', 'reservation status not failed');

    $serviceOK = fiscal_pipeline_service($pdo, $tenantId, null, null, $cert['storage_root'] . '/certs', $cert['storage_root'] . '/secrets');
    $result = $serviceOK->prepare($tenantId, (int) $seed['document_id'], 999);
    $after = $reservationRepo->findById((int) $result['reservation_id']);

    fiscal_assert((int) $after['id'] === (int) $reservation['id'], 'retry changed reservation_id');
    fiscal_assert((int) $after['number'] === (int) $result['number'], 'retry changed number');
    fiscal_assert((string) $after['cnf'] === (string) $result['cNF'], 'retry changed cnf');
    fiscal_assert((string) $after['access_key'] === (string) $result['access_key'], 'retry changed access_key');
    fiscal_assert((int) $pdo->query("SELECT COUNT(*) FROM fiscal_artifacts WHERE tenant_id={$tenantId} AND fiscal_document_id={$seed['document_id']}")->fetchColumn() === 1, 'artifact not restored after retry');

    echo "FiscalPipelineRetryTest OK\n";
} finally {
    fiscal_drop_database($server, $database);
}
