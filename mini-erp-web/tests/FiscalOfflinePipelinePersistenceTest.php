<?php

declare(strict_types=1);
if (getenv('RUN_FISCAL_MARIADB_TESTS') !== '1') {
    echo "FiscalOfflinePipelinePersistenceTest SKIPPED\n";
    exit;
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/helpers/FiscalPipelineTestSupport.php';

[$server, $pdo, $database] = fiscal_test_db();
$tenantId = 991001;
$establishmentId = 991101;
$taxId = '12345678000195';

try {
    $cert = fiscal_seed_certificate($pdo, $tenantId, $establishmentId, $taxId, 'PERSIST TEST');
    $seed = fiscal_seed_document($pdo, $tenantId, $establishmentId, '55', 1);

    $artifactRoot = sys_get_temp_dir() . '/minierp-fiscal-artifacts-test-persistence';
    $service = fiscal_pipeline_service($pdo, $tenantId, null, $artifactRoot, $cert['storage_root'] . '/certs', $cert['storage_root'] . '/secrets');
    $result = $service->prepare($tenantId, (int) $seed['document_id'], 999);

    fiscal_assert((int) ($result['reservation_id'] ?? 0) > 0, 'reservation not persisted');
    fiscal_assert((int) ($result['artifact_id'] ?? 0) > 0, 'artifact not persisted');
    fiscal_assert((string) ($result['access_key'] ?? '') !== '', 'access key missing');
    fiscal_assert((string) ($result['cNF'] ?? '') !== '', 'cnf missing');
    fiscal_assert((string) ($result['artifact']['storage_reference'] ?? '') !== '', 'storage_reference missing');
    fiscal_assert((int) ($result['artifact']['size'] ?? 0) > 0, 'size_bytes missing');

    $reservationRepo = new MiniErp\Repositories\FiscalNumberReservationRepository($pdo, $tenantId);
    $artifactRepo = new MiniErp\Repositories\FiscalArtifactRepository($pdo, $tenantId);
    $reservation = $reservationRepo->findById((int) $result['reservation_id']);
    $artifact = $artifactRepo->findById((int) $result['artifact_id']);

    fiscal_assert($reservation !== null && (int) $reservation['id'] > 0, 'reservation row missing');
    fiscal_assert($artifact !== null && (int) $artifact['id'] > 0, 'artifact row missing');
    fiscal_assert((int) $artifact['number_reservation_id'] === (int) $reservation['id'], 'artifact reservation link mismatch');
    fiscal_assert((int) ($artifact['certificate_id'] ?? 0) > 0, 'artifact certificate_id missing');
    fiscal_assert((int) ($reservation['number'] ?? 0) > 0, 'reservation.number missing');
    fiscal_assert((string) ($reservation['cnf'] ?? '') !== '', 'reservation.cnf missing');
    fiscal_assert((string) ($reservation['access_key'] ?? '') !== '', 'reservation.access_key missing');
    fiscal_assert((string) ($artifact['access_key'] ?? '') === (string) $reservation['access_key'], 'artifact access_key mismatch');
    fiscal_assert((string) ($artifact['sha256'] ?? '') !== '', 'artifact.sha256 missing');
    fiscal_assert((int) ($artifact['size_bytes'] ?? 0) > 0, 'artifact.size_bytes missing');
    fiscal_assert((string) ($artifact['storage_reference'] ?? '') !== '', 'artifact.storage_reference missing');

    $storage = new MiniErp\Fiscal\FiscalArtifactStorage(sys_get_temp_dir() . '/minierp-fiscal-artifacts-test-persistence');
    $path = $storage->resolve((string) $artifact['storage_reference']);
    fiscal_assert(is_file($path), 'stored artifact file missing');

    echo "FiscalOfflinePipelinePersistenceTest OK\n";
} finally {
    fiscal_drop_database($server, $database);
}
