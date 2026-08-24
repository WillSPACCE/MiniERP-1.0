<?php

declare(strict_types=1);
if (getenv('RUN_FISCAL_MARIADB_TESTS') !== '1') { echo "FiscalPipelineArtifactMissingIntegrityTest SKIPPED\n"; exit; }

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/helpers/FiscalPipelineTestSupport.php';

[$server, $pdo, $database] = fiscal_test_db();
$tenantId = 995020;
$establishmentId = 995120;
$taxId = '12345678000195';

try {
    $cert = fiscal_seed_certificate($pdo, $tenantId, $establishmentId, $taxId, 'ARTIFACT TEST');
    $seed = fiscal_seed_document($pdo, $tenantId, $establishmentId, '55', 1);

    $artifactRoot = sys_get_temp_dir() . '/minierp-fiscal-artifacts';
    if (!is_dir($artifactRoot) && !mkdir($artifactRoot, 0700, true) && !is_dir($artifactRoot)) throw new RuntimeException('failed to create artifact root');
    $service = fiscal_pipeline_service($pdo, $tenantId, null, $artifactRoot, $cert['storage_root'] . '/certs', $cert['storage_root'] . '/secrets');

    // Create an artifact file and insert artifact record so prepare() sees it as existing
    $storage = new \MiniErp\Fiscal\FiscalArtifactStorage($artifactRoot);
    $signedXml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<nfeProc>TEST</nfeProc>";
    $info = $storage->storeSignedXml($tenantId, $establishmentId, (int)$seed['document_id'], $signedXml, 'NFE');

    $artifactRepo = new \MiniErp\Repositories\FiscalArtifactRepository($pdo, $tenantId);
    $artifactRepo->create([
        'establishment_id' => $establishmentId,
        'fiscal_document_id' => (int)$seed['document_id'],
        'fiscal_document_version' => 1,
        'certificate_id' => (int)$cert['certificate_id'],
        'number_reservation_id' => 0,
        'model' => '55',
        'environment' => 2,
        'series' => 1,
        'number' => 1,
        'access_key' => 'TEST-KEY-' . bin2hex(random_bytes(4)),
        'artifact_type' => 'NFE',
        'status' => 'XSD_VALID_OFFLINE',
        'schema_package' => 'nfe',
        'schema_version' => '010e-v1.02',
        'schema_checksum' => $info['sha256'],
        'storage_reference' => $info['storage_reference'],
        'sha256' => $info['sha256'],
        'size_bytes' => (int)$info['size'],
        'created_by' => 999,
    ]);

    // initial prepare should detect existing artifact and be idempotent
    $res = $service->prepare($tenantId, (int)$seed['document_id'], 999);
    fiscal_assert(isset($res['idempotent']) && $res['idempotent'] === true, 'initial prepare should be idempotent');

    $path = $storage->resolve($info['storage_reference']);
    // remove file -> expect ARTIFACT_FILE_MISSING
    unlink($path);
    try {
        $service->prepare($tenantId, (int)$seed['document_id'], 999);
        throw new RuntimeException('expected ARTIFACT_FILE_MISSING');
    } catch (Throwable $e) {
        if ($e->getMessage() !== 'ARTIFACT_FILE_MISSING') throw $e;
    }

    // recreate tampered file -> expect ARTIFACT_INTEGRITY_FAILED
    file_put_contents($path, 'tampered');
    try {
        $service->prepare($tenantId, (int)$seed['document_id'], 999);
        throw new RuntimeException('expected ARTIFACT_INTEGRITY_FAILED');
    } catch (Throwable $e) {
        if ($e->getMessage() !== 'ARTIFACT_INTEGRITY_FAILED') throw $e;
    }

    echo "FiscalPipelineArtifactMissingIntegrityTest OK\n";
} finally {
    fiscal_drop_database($server, $database);
}
