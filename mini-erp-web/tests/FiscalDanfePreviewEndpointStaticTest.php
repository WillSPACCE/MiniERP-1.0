<?php
declare(strict_types=1);
$root=dirname(__DIR__);$endpoint=(string)file_get_contents($root.'/public/fiscal_danfe_preview.php');$ui=(string)file_get_contents($root.'/public/index.php');$js=(string)file_get_contents($root.'/public/assets/app.js');
function preview_assert(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
foreach(["erp_tenant_id","erp_user_id","order_id","FiscalDanfePreviewService","application/pdf","private, no-store"]as$needle)preview_assert(str_contains($endpoint,$needle),'endpoint missing '.$needle);
foreach(['tenant_id','db_name','xml_path','certificate_id']as$input)preview_assert(!str_contains($endpoint,"INPUT_GET,'$input'"),'forbidden input '.$input);
preview_assert(str_contains($endpoint,"WHERE id=?")&&str_contains($endpoint,"/^mini_erp_tenant_"),'tenant control-plane resolution');
preview_assert(str_contains($ui,'data-danfe-preview')&&str_contains($ui,'Prévia DANFE'),'UI action');preview_assert(str_contains($js,'Gerando prévia DANFE...')&&str_contains($js,"window.open('', '_blank')"),'immediate popup');
echo "FiscalDanfePreviewEndpointStatic OK\n";
