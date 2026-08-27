<?php
declare(strict_types=1);
$root=dirname(__DIR__);$ui=(string)file_get_contents($root.'/public/index.php');$actionSource=(string)file_get_contents($root.'/public/fiscal_action.php');$service=(string)file_get_contents($root.'/src/Services/FiscalDanfePreviewService.php');
function model_field_assert(bool$ok,string$message):void{if(!$ok)throw new RuntimeException($message);}
foreach(['Modelo fiscal pretendido','55 — NF-e / DANFE A4','65 — NFC-e / DANFC-e cupom','id="fiscal-model-select"','id="fiscal-preview-submit"']as$needle)model_field_assert(str_contains($ui,$needle),'UI model field '.$needle);
model_field_assert(str_contains($ui,"\$editingOrder['fiscal_model'] ?? \$previewCompanyModel"),'saved choice precedes company default');
foreach(["['55','65']","\$action === 'preview'","'model_source'=>'EXPLICIT'","'preserve_page'=>true"]as$needle)model_field_assert(str_contains($actionSource,$needle),'preview POST contract '.$needle);
foreach(['resolveWithSource','model_source','FiscalTaxContext']as$needle)model_field_assert(str_contains($service,$needle),'preview context '.$needle);
echo "FiscalOrderModelField PASS\n";
