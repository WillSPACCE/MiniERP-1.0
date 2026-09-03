<?php
declare(strict_types=1);
function fiscalRequiredAssert(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$root=dirname(__DIR__);
$master=(string)file_get_contents($root.'/public/assets/master-data.js');
$masterCss=(string)file_get_contents($root.'/public/assets/master-data-fixes.css');
$app=(string)file_get_contents($root.'/public/assets/app.js');
$style=(string)file_get_contents($root.'/public/assets/style.css');
$platform=(string)file_get_contents($root.'/public/assets/platform.css');
foreach(['document','nome','cep','logradouro','numero','bairro','municipio','uf','codigo_ibge','state_registration_indicator']as$field)fiscalRequiredAssert(str_contains($master,"'{$field}'"),'destinatário '.$field);
foreach(['codigo','nome','ncm','merchandise_origin','unidade','taxable_unit']as$field)fiscalRequiredAssert(str_contains($master,"'{$field}'"),'produto '.$field);
foreach(['operation_nature','fiscal_model','purpose','presence_indicator','cliente_id','documento','cfop_id']as$field)fiscalRequiredAssert(str_contains($app,$field),'pedido '.$field);
fiscalRequiredAssert(str_contains($masterCss,'label.md-required')&&str_contains($masterCss,'content:"XML"'),'marcação de cadastro');
fiscalRequiredAssert(str_contains($style,'.sefaz-required')&&str_contains($style,'.sefaz-required-legend'),'marcação do pedido');
fiscalRequiredAssert(str_contains($platform,'.establishment-form label:has([required])')&&str_contains($platform,'XML SEFAZ'),'marcação do emitente');
echo "FiscalRequiredFieldUiTest OK\n";
