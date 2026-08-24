<?php

declare(strict_types=1);
if (getenv('RUN_FISCAL_MARIADB_TESTS') !== '1') {
    echo "FiscalPipelineIdempotencyTest SKIPPED\n";
    exit;
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/helpers/FiscalPipelineTestSupport.php';

[$server, $pdo, $database] = fiscal_test_db();
$tenantId = 991002;
$establishmentId = 991102;
$taxId = '12345678000195';

try {
    $cert = fiscal_seed_certificate($pdo, $tenantId, $establishmentId, $taxId, 'IDEMPOTENCY TEST');
    $seed = fiscal_seed_document($pdo, $tenantId, $establishmentId, '55', 1);

    $service = fiscal_pipeline_service($pdo, $tenantId, null, null, $cert['storage_root'] . '/certs', $cert['storage_root'] . '/secrets');
    $resultA = $service->prepare($tenantId, (int) $seed['document_id'], 999);
    $resultB = $service->prepare($tenantId, (int) $seed['document_id'], 999);

    fiscal_assert((int) ($resultA['reservation_id'] ?? 0) === (int) ($resultB['reservation_id'] ?? 0), 'reservation_id changed');
    fiscal_assert((int) ($resultA['artifact_id'] ?? 0) === (int) ($resultB['artifact_id'] ?? 0), 'artifact_id changed');
    fiscal_assert((int) ($resultA['number'] ?? 0) === (int) ($resultB['number'] ?? 0), 'number changed');
    fiscal_assert((string) ($resultA['cNF'] ?? '') === (string) ($resultB['cNF'] ?? ''), 'cnf changed');
    fiscal_assert((string) ($resultA['access_key'] ?? '') === (string) ($resultB['access_key'] ?? ''), 'access_key changed');

    $reservationCount = (int) $pdo->query("SELECT COUNT(*) FROM fiscal_number_reservations WHERE tenant_id={$tenantId}")->fetchColumn();
    $artifactCount = (int) $pdo->query("SELECT COUNT(*) FROM fiscal_artifacts WHERE tenant_id={$tenantId}")->fetchColumn();
    fiscal_assert($reservationCount === 1, 'reservation_count != 1');
    fiscal_assert($artifactCount === 1, 'artifact_count != 1');

    echo "FiscalPipelineIdempotencyTest OK\n";
} finally {
    fiscal_drop_database($server, $database);
}
