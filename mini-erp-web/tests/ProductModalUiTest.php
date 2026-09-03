<?php
declare(strict_types=1);

function productModalAssert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$view = (string) file_get_contents(__DIR__ . '/../public/includes/master_data_configuration.php');
$css = (string) file_get_contents(__DIR__ . '/../public/assets/master-data-fixes.css');
$js = (string) file_get_contents(__DIR__ . '/../public/assets/master-data.js');

productModalAssert(str_contains($view, "mdEntity==='product'?'md-product-modal':''"), 'classe exclusiva do modal de produto');
productModalAssert(str_contains($view, 'role="dialog"') && str_contains($view, 'aria-modal="true"'), 'semântica acessível do modal');
productModalAssert(str_contains($css, '.md-product-modal{'), 'estilos isolados do produto');
productModalAssert(str_contains($css, 'max-height:calc(100dvh - 32px)') && str_contains($css, 'width:min(920px'), 'dimensões responsivas');
productModalAssert(str_contains($css, '.md-product-modal .md-form>.md-pane.active') && str_contains($css, 'overflow-y:auto'), 'scroll interno');
productModalAssert(str_contains($css, '.md-product-modal .md-form>footer') && str_contains($css, 'grid-template-rows:auto auto auto minmax(0,1fr) auto'), 'footer sempre reservado');
productModalAssert(str_contains($css, '@media(max-width:900px)') && str_contains($css, '@media(max-width:760px)'), 'breakpoints tablet e mobile');
productModalAssert(str_contains($js, "button.textContent='Salvando"), 'estado de salvamento');
productModalAssert(str_contains($js, 'revealField') && str_contains($js, 'targetPane'), 'erro revela guia e campo');
productModalAssert(str_contains($js, "confirm('Fechar sem salvar"), 'proteção de alterações não salvas');

echo "ProductModalUiTest OK\n";
