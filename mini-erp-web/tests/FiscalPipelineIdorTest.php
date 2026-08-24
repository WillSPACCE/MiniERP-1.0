<?php

declare(strict_types=1);
if (getenv('RUN_FISCAL_MARIADB_TESTS') !== '1') {
    echo "FiscalPipelineIdorTest SKIPPED\n";
    exit;
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/helpers/FiscalPipelineTestSupport.php';

[$server, $pdo, $database] = fiscal_test_db();
$tenantA = 991004;
$estA = 991104;
$tenantB = 991005;
$estB = 991105;
$taxId = '12345678000195';

try {
    $certA = fiscal_seed_certificate($pdo, $tenantA, $estA, $taxId, 'TENANT A');
    $certB = fiscal_seed_certificate($pdo, $tenantB, $estB, $taxId, 'TENANT B');
    $seedA = fiscal_seed_document($pdo, $tenantA, $estA, '55', 1);

    $beforeCount = (int) $pdo->query("SELECT COUNT(*) FROM fiscal_number_reservations WHERE tenant_id={$tenantA}")->fetchColumn();
    $beforeArtifactCount = (int) $pdo->query("SELECT COUNT(*) FROM fiscal_artifacts WHERE tenant_id={$tenantA}")->fetchColumn();

    try {
        $serviceB = fiscal_pipeline_service($pdo, $tenantB, null, null, $certB['storage_root'] . '/certs', $certB['storage_root'] . '/secrets');
        $serviceB->prepare($tenantB, (int) $seedA['document_id'], 999);
        throw new RuntimeException('cross-tenant prepare should fail');
    } catch (RuntimeException $e) {
        $msg = strtolower($e->getMessage());
        fiscal_assert(str_contains($msg, 'not found') || str_contains($msg, 'document'), 'cross tenant did not fail');
    }

    $afterCount = (int) $pdo->query("SELECT COUNT(*) FROM fiscal_number_reservations WHERE tenant_id={$tenantA}")->fetchColumn();
    $afterArtifactCount = (int) $pdo->query("SELECT COUNT(*) FROM fiscal_artifacts WHERE tenant_id={$tenantA}")->fetchColumn();
    fiscal_assert($beforeCount === $afterCount, 'tenant A reservation count changed after cross-tenant attempt');
    fiscal_assert($beforeArtifactCount === $afterArtifactCount, 'tenant A artifact count changed after cross-tenant attempt');

    echo "FiscalPipelineIdorTest OK\n";
} finally {
    fiscal_drop_database($server, $database);
}
