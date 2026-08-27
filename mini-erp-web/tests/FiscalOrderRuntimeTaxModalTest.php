<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$ui = (string) file_get_contents($root . '/public/index.php');
$js = (string) file_get_contents($root . '/public/assets/app.js');

function runtime_assert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

runtime_assert(str_contains($ui, '55 = NF-e / DANFE A4 · 65 = NFC-e / DANFC-e cupom'), 'field-help fiscal model missing');
runtime_assert(str_contains($js, 'closest(\'td\')'), 'tax modal click handler must use closest() for nested clicks');
runtime_assert(str_contains($js, 'window.PRODUCT_TAXES'), 'product taxes map must be read at runtime');

echo "FiscalOrderRuntimeTaxModal PASS\n";
