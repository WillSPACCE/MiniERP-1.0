<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Fiscal/NfeAccessKeyGenerator.php';
require_once __DIR__ . '/../src/Fiscal/FiscalDocumentDTO.php';
require_once __DIR__ . '/../src/Fiscal/FiscalArtifactStorage.php';
require_once __DIR__ . '/../src/Fiscal/FiscalNumericCodeGenerator.php';

use MiniErp\Fiscal\FiscalArtifactStorage;
use MiniErp\Fiscal\FiscalDocumentDTO;
use MiniErp\Fiscal\NfeAccessKeyGenerator;
use MiniErp\Fiscal\FiscalNumericCodeGenerator;

function fiscal06bAssert(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }

$generator = new NfeAccessKeyGenerator();
$officialExample = '43241122858959000103550010000121041687042088';
fiscal06bAssert($generator->digit(substr($officialExample, 0, 43)) === 8, 'DV numérico oficial');
$alpha = $generator->generate('35', '2608', '12ABC34501DE35', '55', 1, 123, 1, '87654321');
fiscal06bAssert(strlen($alpha) === 44 && preg_match('/^[A-Z0-9]{43}[0-9]$/', $alpha) === 1, 'chave alfanumérica de 44 posições');
fiscal06bAssert($generator->generate('35', '2608', '12ABC34501DE35', '55', 1, 123, 1, '87654321') === $alpha, 'chave determinística');
$numericCode = (new FiscalNumericCodeGenerator())->generate();
fiscal06bAssert(preg_match('/^\d{8}$/', $numericCode) === 1, 'cNF criptograficamente seguro com 8 posições');

$document = [
    'id' => 7, 'status' => 'FISCAL_READY', 'issuer_snapshot' => ['tax_id' => '12ABC34501DE35'],
    'recipient_snapshot' => ['cpf_cnpj' => '00000000000'], 'totals' => ['model' => '55', 'grand' => '10.00'],
    'payment_snapshot' => ['method' => '01'], 'transport_snapshot' => [],
    'items' => [['product_snapshot_json' => '{"nome":"TEST_ONLY"}', 'tax_resolution_json' => '{"cfop":"5102"}']],
];
$dto = FiscalDocumentDTO::fromSnapshots($document, 14);
fiscal06bAssert($dto->tenantId === 14 && $dto->items[0]['product']['nome'] === 'TEST_ONLY', 'DTO usa snapshots');
$document['status'] = 'FISCAL_PENDING';
try { FiscalDocumentDTO::fromSnapshots($document, 14); throw new RuntimeException('PENDING permitido'); } catch (RuntimeException $error) { fiscal06bAssert($error->getMessage() !== 'PENDING permitido', 'PENDING fail closed'); }

$temporary = sys_get_temp_dir() . '/mini-erp-fiscal06b-' . bin2hex(random_bytes(5));
mkdir($temporary, 0700, true);
try {
    $storage = new FiscalArtifactStorage($temporary);
    $xml = '<?xml version="1.0" encoding="UTF-8"?><NFe xmlns="http://www.portalfiscal.inf.br/nfe"><infNFe Id="NFe' . $alpha . '"/></NFe>';
    $artifact = $storage->storeUnsignedXml(14, 1, 7, $xml);
    fiscal06bAssert($artifact['sha256'] === hash('sha256', $xml) && $artifact['status'] === 'GENERATED_UNSIGNED', 'storage/hash');
    fiscal06bAssert(is_file($storage->resolve($artifact['storage_reference'])), 'resolução interna');
    try { $storage->resolve('../public/index.php'); throw new RuntimeException('traversal permitido'); } catch (RuntimeException $error) { fiscal06bAssert($error->getMessage() !== 'traversal permitido', 'path traversal bloqueado'); }
} finally {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporary, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $entry) $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    rmdir($temporary);
}

$allocator = file_get_contents(__DIR__ . '/../src/Fiscal/FiscalNumberAllocator.php');
fiscal06bAssert(str_contains($allocator, 'FOR UPDATE') && str_contains($allocator, 'fiscal_document_id=?'), 'allocator transacional/idempotente');
echo "Fiscal06BFoundation OK\n";
