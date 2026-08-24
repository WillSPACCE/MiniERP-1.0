<?php
declare(strict_types=1);

function cnpjEntrypointAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$endpoint = file_get_contents(__DIR__ . '/../public/ajax_cnpj.php');
$entry = file_get_contents(__DIR__ . '/../public/index.php');
$repository = file_get_contents(__DIR__ . '/../app/Repository.php');

cnpjEntrypointAssert(str_contains($endpoint, 'CnpjLookupService'), 'AJAX uses lookup service');
cnpjEntrypointAssert(str_contains($endpoint, "\$_GET['cnpj']"), 'AJAX accepts CNPJ only through GET');
cnpjEntrypointAssert(str_contains($endpoint, 'AUTH_REQUIRED'), 'ERP endpoint is authenticated');
cnpjEntrypointAssert(str_contains($endpoint, 'CNPJ_RATE_LIMIT'), 'ERP endpoint is rate limited');
cnpjEntrypointAssert(!str_contains($endpoint, "'message' => \$e->getMessage()"), 'AJAX does not expose provider errors');
cnpjEntrypointAssert(str_contains($repository, 'Wrapper legado'), 'legacy repository compatibility is explicit');
cnpjEntrypointAssert(str_contains($entry, 'setConsultedValue'), 'company lookup preserves populated fields');
cnpjEntrypointAssert(str_contains($entry, 'Usar dado consultado:'), 'company lookup offers divergence action');
cnpjEntrypointAssert(str_contains($entry, 'Dados consultados automaticamente. Revise antes de salvar.'), 'company lookup asks for review');

echo "CnpjLookupEntrypointStatic OK\n";
