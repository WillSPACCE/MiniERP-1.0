<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$page=(string)file_get_contents($root.'/public/index.php');
$css=(string)file_get_contents($root.'/public/assets/style.css');
function actionBarAssert(bool $condition,string $label):void{if(!$condition)throw new RuntimeException($label);}

preg_match('/<div class="order-action-bar"[\s\S]*?<\/div>\s*<div class="order-action-feedback"/',$page,$match);
$bar=$match[0]??'';
actionBarAssert($bar!=='','OrderActionBarUiTest bar');
foreach(['data-order-action="save"','data-order-action="note"','data-order-action="print"','Financeiro','Imprimir Pedido','data-order-new']as$contract)actionBarAssert(str_contains($bar,$contract),'OrderActionBarUiTest '.$contract);
actionBarAssert(substr_count($bar,'value="save_fiscal_order"')===1,'OrderActionBarNoDuplicateButtonsTest');
foreach(['Prévia DANFE','Concluir pedido','Salvar prévia interna','Importar de Outra Empresa']as$removed)actionBarAssert(!str_contains($bar,$removed),'removed '.$removed);
actionBarAssert(str_contains($bar,"if(\$tab==='entrada')")&&str_contains($bar,'Importar XML'),'OrderActionImportXmlEntryOnlyTest');
actionBarAssert(str_contains($bar,'Financeiro será integrado ao módulo de contas e estoque.')&&str_contains($bar,'disabled'),'OrderActionFinanceDisabledTest');
actionBarAssert(str_contains($page,"window.open('','_blank')")&&str_contains($page,"data.set('fiscal_action',fiscalAction)"),'OrderActionPrintNewTabContractTest');
actionBarAssert(str_contains($page,'Existem alterações não salvas. Deseja iniciar um novo pedido?'),'OrderActionNewDirtyStateTest');
actionBarAssert(str_contains($css,'.order-action-bar')&&str_contains($css,'.order-routine--primary')&&str_contains($css,'overflow-x: auto'),'responsive action bar CSS');
echo "OrderActionBarUiTest OK\n";
