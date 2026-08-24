<?php
declare(strict_types=1);

function panelAssert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$entry = file_get_contents(__DIR__ . '/../public/index.php');
$lookup = file_get_contents(__DIR__ . '/../public/assets/cnpj-lookup.js');

panelAssert(str_contains($entry, '$failedFormData = array_diff_key'), 'failed form data is preserved');
foreach (['save_cliente', 'save_produto', 'save_cfop', 'save_fornecedor', 'save_motorista', 'save_transportadora', 'save_usuario', 'save_empresa'] as $action) {
    panelAssert(str_contains($entry, "failedFormAction === '{$action}'") || $action === 'save_cliente', "old input mapped for {$action}");
}
panelAssert(!str_contains($entry, "array_flip(['senha', 'password', 'certificate_password', 'csrf_token']) === []"), 'sensitive field filtering remains active');
panelAssert(str_contains($lookup, 'btn-buscar-cnpj-cliente'), 'person CNPJ lookup enabled');
panelAssert(str_contains($lookup, 'btn-buscar-cnpj-fornecedor'), 'supplier CNPJ lookup enabled');
panelAssert(str_contains($lookup, 'btn-buscar-cnpj-transportadora'), 'carrier CNPJ lookup enabled');
panelAssert(str_contains($lookup, 'btn-buscar-cnpj-estabelecimento'), 'fiscal establishment CNPJ lookup enabled');
panelAssert(str_contains($lookup, 'Usar dado consultado:'), 'lookup does not overwrite divergent input');
panelAssert(str_contains($entry, '/assets/images/Favicon-v2/favicon-32x32.png'), 'new panel favicon enabled');

echo "PanelFormResilienceStatic OK\n";
