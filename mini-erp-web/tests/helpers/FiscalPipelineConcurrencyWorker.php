<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/FiscalPipelineTestSupport.php';

$host = $argv[1] ?? '127.0.0.1';
$port = $argv[2] ?? '3306';
$dbName = $argv[3] ?? '';
$dbUser = $argv[4] ?? 'root';
$dbPass = $argv[5] ?? '';
$tenantId = (int) ($argv[6] ?? 0);
$documentId = (int) ($argv[7] ?? 0);
$artifactRoot = $argv[8] ?? sys_get_temp_dir() . '/minierp-fiscal-worker';

$pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbName), $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$service = new MiniErp\Services\OfflineFiscalDocumentPipelineService(
    $pdo,
    new MiniErp\Repositories\FiscalOperationRepository($pdo, $tenantId),
    new MiniErp\Repositories\FiscalConfigurationRepository($pdo, $tenantId),
    null,
    null,
    new MiniErp\Fiscal\FiscalArtifactStorage($artifactRoot),
    new MiniErp\Fiscal\OfficialNfeXsdValidator(__DIR__ . '/../../resources/fiscal/xsd/nfe/010e-v1.02/NFe'),
    new MiniErp\Fiscal\FiscalXmlSigner(),
    new MiniErp\Fiscal\NfeAccessKeyGenerator(),
    new MiniErp\Services\FiscalDocumentDTOFactory(),
    new MiniErp\Fiscal\FiscalNfeXmlBuilder(),
);

try {
    $result = $service->prepare($tenantId, $documentId, 999);
    echo json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), PHP_EOL;
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), PHP_EOL;
    exit(1);
}
