<?php
declare(strict_types=1);

function orderBrowserAssert(bool $condition,string $label):void{if(!$condition)throw new RuntimeException($label);echo$label." PASS\n";}

$page=(string)file_get_contents(__DIR__.'/../public/index.php');
$feedback=(string)file_get_contents(__DIR__.'/../public/assets/app-feedback.js');
$css=(string)file_get_contents(__DIR__.'/../public/assets/style.css');

orderBrowserAssert(str_contains($page,'name="action" value="save_fiscal_order"'),'OrderSaveSubmitActionContractTest');
orderBrowserAssert(str_contains($feedback,"button.closest('[data-order-action-bar]')"),'OrderSaveGlobalSubmitConflictRegressionTest');
orderBrowserAssert(str_contains($page,"querySelector('[data-order-label]')")&&!str_contains($page,"fiscalPreviewSubmit.textContent="),'OrderPreviewButtonMarkupPreservedTest');
orderBrowserAssert(str_contains($page,'tab=emitidos&highlight_order=')&&str_contains($page,'Ele já está disponível na lista operacional abaixo.'),'OrderSaveVisibleInOrdersListTest');
orderBrowserAssert(str_contains($css,'.order-action-bar__buttons')&&str_contains($css,'.order-routine--fiscal')&&str_contains($css,'.order-routine--preview'),'OrderActionBarVisualRefinementTest');
