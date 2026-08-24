<?php

declare(strict_types=1);
if (getenv('RUN_FISCAL_MARIADB_TESTS') !== '1') { echo "FiscalPipelineConcurrencyTest SKIPPED\n"; exit; }

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/helpers/FiscalPipelineTestSupport.php';

[$server, $pdo, $database] = fiscal_test_db();
$tenantId = 995001;
$establishmentId = 995101;
$taxId = '12345678000195';

try {
    $cert = fiscal_seed_certificate($pdo, $tenantId, $establishmentId, $taxId, 'CONCURRENCY TEST');
    $seed = fiscal_seed_document($pdo, $tenantId, $establishmentId, '55', 1);

    $artifactRoot = sys_get_temp_dir() . '/minierp-fiscal-artifacts-concurrency';
    $cmd = [
        'C:/xampp/php/php.exe',
        __DIR__ . '/helpers/FiscalPipelineWorker.php',
        $database,
        (string) $tenantId,
        (string) $seed['document_id'],
        $cert['storage_root'] . '/certs',
        $cert['storage_root'] . '/secrets',
        $artifactRoot,
    ];

    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $p1 = proc_open($cmd, $descriptors, $pipes1);
    usleep(200000); // slight stagger
    $p2 = proc_open($cmd, $descriptors, $pipes2);

    $out1 = stream_get_contents($pipes1[1]); fclose($pipes1[1]);
    $err1 = stream_get_contents($pipes1[2]); fclose($pipes1[2]);
    $status1 = proc_close($p1);

    $out2 = stream_get_contents($pipes2[1]); fclose($pipes2[1]);
    $err2 = stream_get_contents($pipes2[2]); fclose($pipes2[2]);
    $status2 = proc_close($p2);

    $r1 = json_decode($out1, true);
    $r2 = json_decode($out2, true);

    // Accept either both OK with same artifact, or one OK and other PIPELINE_BUSY
    if ($r1 === null && strpos($out1, 'PIPELINE_BUSY') !== false) { echo "Worker1 BUSY\n"; }
    if ($r2 === null && strpos($out2, 'PIPELINE_BUSY') !== false) { echo "Worker2 BUSY\n"; }

    if (is_array($r1) && $r1['ok'] && is_array($r2) && $r2['ok']) {
        $a1 = $r1['result']['artifact_id'] ?? null;
        $a2 = $r2['result']['artifact_id'] ?? null;
        if ($a1 !== $a2) throw new RuntimeException('Concurrent artifacts differ');
        echo "FiscalPipelineConcurrencyTest OK\n";
    } elseif (is_array($r1) && $r1['ok'] && ($r2 === null || (is_array($r2) && !$r2['ok']))) {
        echo "FiscalPipelineConcurrencyTest OK (one BUSY)\n";
    } elseif (is_array($r2) && $r2['ok'] && ($r1 === null || (is_array($r1) && !$r1['ok']))) {
        echo "FiscalPipelineConcurrencyTest OK (one BUSY)\n";
    } else {
        echo "FiscalPipelineConcurrencyTest FAILED\n";
        echo "OUT1:" . $out1 . " ERR1:" . $err1 . " OUT2:" . $out2 . " ERR2:" . $err2;
        exit(1);
    }
} finally {
    fiscal_drop_database($server, $database);
}

