<?php
declare(strict_types=1);
function stableButtonAssert(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$js=file_get_contents(__DIR__.'/../public/assets/master-data.js');$css=file_get_contents(__DIR__.'/../public/assets/master-data-fixes.css');
stableButtonAssert(str_contains($js,'button.hidden=false')&&str_contains($js,'Consulta somente CNPJ'),'document lookup button must keep a stable position for PF');
stableButtonAssert(str_contains($css,'.md-form>footer .btn')&&str_contains($css,'visibility:visible!important'),'footer actions must remain visible');
stableButtonAssert(str_contains($css,'flex-direction:column')&&str_contains($css,'flex:1 1 auto')&&str_contains($css,'min-height:67px'),'form body must reserve footer space');
echo "MasterDataStableButtons OK\n";
