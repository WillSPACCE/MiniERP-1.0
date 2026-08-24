<?php

declare(strict_types=1);
if (getenv('RUN_FISCAL_MARIADB_TESTS') !== '1') { echo "FiscalPipelineDifferentDocumentsConcurrencyTest SKIPPED\n"; exit; }

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/helpers/FiscalPipelineTestSupport.php';

[$server, $pdo, $database] = fiscal_test_db();
$tenantId = 995002;
$establishmentId = 995102;
$taxId = '12345678000195';

try {
    $cert = fiscal_seed_certificate($pdo, $tenantId, $establishmentId, $taxId, 'CONCURRENCY DIFF TEST');
    $seedA = fiscal_seed_document($pdo, $tenantId, $establishmentId, '55', 1);
    $seedB = fiscal_seed_document($pdo, $tenantId, $establishmentId, '55', 1);

    $artifactRootA = sys_get_temp_dir() . '/minierp-fiscal-concurrency-a-' . bin2hex(random_bytes(4));
    $artifactRootB = sys_get_temp_dir() . '/minierp-fiscal-concurrency-b-' . bin2hex(random_bytes(4));

    $worker = __DIR__ . '/helpers/FiscalPipelineWorker.php';
    $cmdA = ['C:/xampp/php/php.exe', $worker, $database, (string)$tenantId, (string)$seedA['document_id'], $cert['storage_root'] . '/certs', $cert['storage_root'] . '/secrets', $artifactRootA];
    $cmdB = ['C:/xampp/php/php.exe', $worker, $database, (string)$tenantId, (string)$seedB['document_id'], $cert['storage_root'] . '/certs', $cert['storage_root'] . '/secrets', $artifactRootB];

    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $pA = proc_open($cmdA, $descriptors, $pipesA);
    usleep(200000);
    $pB = proc_open($cmdB, $descriptors, $pipesB);

    fiscal_assert($pA !== false && $pB !== false, 'failed to start workers');

    $outA = stream_get_contents($pipesA[1]); fclose($pipesA[1]);
    $errA = stream_get_contents($pipesA[2]); fclose($pipesA[2]);
    $codeA = proc_close($pA);

    $outB = stream_get_contents($pipesB[1]); fclose($pipesB[1]);
    $errB = stream_get_contents($pipesB[2]); fclose($pipesB[2]);
    $codeB = proc_close($pB);

    fiscal_assert($codeA === 0 && $codeB === 0, 'one of workers failed: ' . trim($errA . PHP_EOL . $errB));

    $jA = json_decode(trim($outA), true);
    $jB = json_decode(trim($outB), true);

    echo "OUTA:" . trim($outA) . PHP_EOL;
    echo "OUTB:" . trim($outB) . PHP_EOL;

    fiscal_assert(is_array($jA) && ($jA['ok'] ?? false) === true, 'worker A did not succeed: ' . trim($outA));
    fiscal_assert(is_array($jB) && ($jB['ok'] ?? false) === true, 'worker B did not succeed: ' . trim($outB));

    $numA = (int) ($jA['result']['number'] ?? 0);
    $numB = (int) ($jB['result']['number'] ?? 0);
    fiscal_assert($numA > 0 && $numB > 0, 'invalid numbers');
    fiscal_assert($numA !== $numB, 'numbers should differ for different documents');

    echo "FiscalPipelineDifferentDocumentsConcurrencyTest OK\n";
} finally {
    fiscal_drop_database($server, $database);
}
