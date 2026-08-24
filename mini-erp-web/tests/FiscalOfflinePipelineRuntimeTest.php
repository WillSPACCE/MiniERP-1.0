<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MiniErp\Fiscal\FiscalDocumentDTO;
use MiniErp\Services\OfflineFiscalDocumentPipelineService;
use MiniErp\Services\FiscalDocumentDTOFactory;

function assertFiscalRuntime(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

assertFiscalRuntime(class_exists(OfflineFiscalDocumentPipelineService::class), 'OfflineFiscalDocumentPipelineService missing');
assertFiscalRuntime(class_exists(FiscalDocumentDTOFactory::class), 'FiscalDocumentDTOFactory missing');

$factory = new FiscalDocumentDTOFactory();
$document = [
    'id' => 42,
    'tenant_id' => 14,
    'status' => 'FISCAL_READY',
    'issuer_snapshot' => ['tax_id' => '12ABC34501DE35', 'legal_name' => 'Emitente Teste', 'state_registration' => '123456789', 'tax_regime_code' => '1', 'street' => 'Rua A', 'number' => '100', 'district' => 'Centro', 'city_ibge_code' => '3550308', 'city_name' => 'Sao Paulo', 'state' => 'SP'],
    'recipient_snapshot' => ['tax_id' => '12345678000199', 'legal_name' => 'Destinatario Teste', 'state_registration_indicator' => '1', 'email' => 'dest@example.com', 'street' => 'Rua B', 'number' => '20', 'district' => 'Bela Vista', 'city_ibge_code' => '3550308', 'city_name' => 'Sao Paulo', 'state' => 'SP'],
    'payment_snapshot' => ['method' => '01', 'amount' => '100.00'],
    'transport_snapshot' => ['freight_mode' => 9],
    'totals' => ['model' => '55', 'operation_nature' => 'VENDA', 'operation_type' => 'EXIT', 'products' => '100.00', 'grand' => '100.00', 'purpose' => 1, 'final_consumer' => 1, 'presence_indicator' => 1],
    'items' => [[
        'product_snapshot_json' => json_encode(['codigo' => 'P1', 'nome' => 'Produto Teste', 'ncm' => '01012100', 'unidade' => 'UN', 'gtin' => 'SEM GTIN'], JSON_THROW_ON_ERROR),
        'tax_resolution_json' => json_encode(['cfop' => '5102', 'icms' => ['cst' => '00', 'base' => '100.00', 'rate' => '18.00', 'amount' => '18.00'], 'ipi' => [], 'pis' => ['cst' => '01', 'base' => '100.00', 'rate' => '0.65', 'amount' => '0.65'], 'cofins' => ['cst' => '01', 'base' => '100.00', 'rate' => '3.00', 'amount' => '3.00']], JSON_THROW_ON_ERROR),
        'quantity_commercial' => '1',
        'unit_value_commercial' => '100.00',
        'gross_total' => '100.00',
        'net_total' => '100.00',
    ]],
];

$dto = $factory->create($document, 14, ['series' => 1, 'number' => 1, 'access_key' => '35123456789012345678901234567890123456789012345678'], ['environment' => 2, 'emission_type' => 1, 'destination_scope' => 1, 'process_version' => 'MiniERP-1.0']);
assertFiscalRuntime($dto instanceof FiscalDocumentDTO, 'DTO factory did not return FiscalDocumentDTO');
assertFiscalRuntime($dto->model === '55', 'DTO factory model mismatch');
assertFiscalRuntime($dto->issuer['tax_id'] === '12ABC34501DE35', 'Issuer snapshot not preserved');

echo "FiscalOfflinePipelineRuntime OK\n";
