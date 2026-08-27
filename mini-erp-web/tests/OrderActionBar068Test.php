<?php
declare(strict_types=1);
$root=dirname(__DIR__);$page=(string)file_get_contents($root.'/public/index.php');$css=(string)file_get_contents($root.'/public/assets/style.css');$repo=(string)file_get_contents($root.'/src/Repositories/FiscalOperationRepository.php');$action=(string)file_get_contents($root.'/public/fiscal_action.php');$xml=(string)file_get_contents($root.'/src/Fiscal/FiscalNfeXmlBuilder.php');
function a68(bool$condition,string$label):void{if(!$condition)throw new RuntimeException($label);}
preg_match('/<div class="order-action-bar"[\s\S]*?<\/div>\s*<div class="order-action-feedback"/',$page,$match);$bar=$match[0]??'';
a68($bar!==''&&substr_count($bar,'data-order-action="save"')===1&&substr_count($bar,'data-order-action="note"')===1,'OrderActionBarNoDuplicateTest');
a68(!str_contains($bar,'Concluir pedido')&&!str_contains($bar,'Salvar Pedido')&&!str_contains($bar,'Prévia DANFE'),'duplicate legacy buttons absent');
foreach(['position: sticky','bottom: 0','min-height: 64px','height: 52px','flex: 0 0 82px','overflow-x: auto']as$needle)a68(str_contains($css,$needle),'OrderActionBarCompactStickyResponsiveTest '.$needle);
a68(str_contains($css,'.pedido-form { padding-bottom: 76px; }'),'OrderActionBarDoesNotCoverFieldsTest');
a68(str_contains($bar,'data-fiscal-action="finalize"')&&str_contains($action,"\$action === 'finalize'")&&str_contains($action,'CreateInternalFiscalDocumentService'),'OrderFinalizeLegacyFlowRegressionTest / Nota orchestration');
a68(str_contains($bar,'value="save_fiscal_order"')&&str_contains($page,"tab=emitidos&highlight_order="),'OrderSaveOriginalFlowRegressionTest');
a68(!preg_match('/<input[^>]+name="operation_nature"/',$page)&&str_contains($page,'data-order-nature')&&str_contains($page,'data-order-cfop'),'OrderNatureByCfopTest');
foreach(['validatedOperationNature','ORDER_CFOP_REQUIRED','ORDER_CFOP_DIRECTION_INVALID','SELECT codigo,descricao FROM cfops']as$needle)a68(str_contains($repo,$needle),'OrderNatureBackendValidationTest '.$needle);
a68(str_contains($action,'validatedOperationNature')&&str_contains($xml,"'natOp'=>\$totals['operation_nature']"),'OrderNatureXmlTest');
a68(str_contains($page,'data-legacy-nature')&&str_contains($repo,'Compatibilidade: um pedido histórico'),'OrderNatureLegacyCompatibilityTest');
echo "OrderActionBar068 OK\n";
