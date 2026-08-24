<?php

declare(strict_types=1);

$previewFile = __DIR__ . '/../public/includes/fiscal_preview.php';
$preview = file_get_contents($previewFile);
$css = file_get_contents(__DIR__ . '/../public/assets/style.css');
$ui = file_get_contents(__DIR__ . '/../public/index.php');

foreach ([
    'PRÉVIA DANFE',
    'MODELO 55',
    'PRÉVIA DANFC-e',
    'MODELO 65',
    'SEM VALOR FISCAL',
    'Ainda não gerada',
    'QR Code disponível',
    'Baixar XML',
] as $expected) {
    if (!str_contains($preview, $expected)) {
        throw new RuntimeException('Conteúdo ausente na prévia: ' . $expected);
    }
}

foreach (['preview-55', 'preview-65', '@media print', '80mm', '210mm'] as $expected) {
    if (!str_contains($css, $expected)) {
        throw new RuntimeException('Estilo ausente: ' . $expected);
    }
}

foreach (['random', 'chNFe', 'sefaz_protocol', 'xml_artifact'] as $forbidden) {
    if (str_contains($preview, $forbidden)) {
        throw new RuntimeException('Artefato fiscal fictício encontrado: ' . $forbidden);
    }
}

foreach (['Pessoas', 'Empresa', 'Fornecedores', 'Produtos', 'CFOPs', 'Transportadoras', 'Motoristas', 'Usuários'] as $menu) {
    if (!str_contains($ui, $menu)) {
        throw new RuntimeException('Menu legado ausente: ' . $menu);
    }
}

require_once $previewFile;
$fixture = [
    'issuer_snapshot' => ['legal_name' => 'EMITENTE TEST_ONLY', 'tax_id' => '00000000000000'],
    'recipient_snapshot' => ['nome' => 'DESTINATÁRIO TEST_ONLY', 'cpf_cnpj' => '00000000000'],
    'items' => [[
        'product_snapshot_json' => json_encode(['codigo' => 'T1', 'nome' => 'PRODUTO TEST_ONLY', 'ncm' => '00000000', 'unidade' => 'UN']),
        'tax_resolution_json' => json_encode(['cfop' => '5102', 'icms' => ['cst' => '00']]),
        'quantity_commercial' => '1.0000', 'unit_price' => '10.00', 'net_total' => '10.00',
    ]],
    'totals' => ['products' => '10.00', 'discount' => '0.00', 'freight' => '0.00', 'grand' => '10.00'],
    'payment_snapshot' => ['method' => 'TEST_ONLY', 'amount' => '10.00'],
    'transport_snapshot' => ['mode' => 'TEST_ONLY'],
    'pending' => ['REGRA TEST_ONLY'],
];

foreach (['55' => 'PRÉVIA DANFE', '65' => 'PRÉVIA DANFC-e'] as $model => $title) {
    $fixture['totals']['model'] = $model;
    ob_start();
    renderFiscalPreview($fixture);
    $rendered = (string) ob_get_clean();
    foreach ([$title, 'EMITENTE TEST_ONLY', 'PRODUTO TEST_ONLY', '10.00', 'SEM VALOR FISCAL', 'Ainda não gerada', 'disabled'] as $expected) {
        if (!str_contains($rendered, $expected)) {
            throw new RuntimeException("Modelo {$model} não renderizou: {$expected}");
        }
    }
    if ($model === '65' && !str_contains($rendered, 'QR Code disponível')) {
        throw new RuntimeException('Modelo 65 não informa a indisponibilidade do QR Code.');
    }
}

echo "FiscalPreview OK\n";
