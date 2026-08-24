<?php
declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/FiscalPipelineTestSupport.php';

// args: [dbname, tenantId, documentId, certRoot, secretRoot, artifactRoot]
$db = $argv[1] ?? '';
$tenant = (int) ($argv[2] ?? 0);
$doc = (int) ($argv[3] ?? 0);
$certRoot = $argv[4] ?? '';
$secretRoot = $argv[5] ?? '';
$artifactRoot = $argv[6] ?? '';

$cfg = require __DIR__ . '/../../config.php';
$d = $cfg['db'];
try {
    $pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $d['host'], $d['port'], $db), $d['username'], $d['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $service = fiscal_pipeline_service($pdo, $tenant, null, $artifactRoot, $certRoot, $secretRoot);
    $result = $service->prepare($tenant, $doc, 999);
    echo json_encode(['ok' => true, 'result' => $result], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}

