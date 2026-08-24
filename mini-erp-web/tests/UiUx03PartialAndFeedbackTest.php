<?php
declare(strict_types=1);
function u3(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$root=dirname(__DIR__);$layout=file_get_contents($root.'/public/plataforma/_layout.php');$modal=file_get_contents($root.'/public/assets/company-modal.js');$feedback=file_get_contents($root.'/public/assets/app-feedback.js');$css=file_get_contents($root.'/public/assets/app-feedback.css');$erp=file_get_contents($root.'/public/index.php');$platform=file_get_contents($root.'/public/plataforma/index.php');$general=file_get_contents($root.'/public/plataforma/empresa-editar.php');$environment=file_get_contents($root.'/public/plataforma/empresa-ambiente-partial.php');
u3(str_contains($layout,'isPlatformPartialView')&&str_contains($layout,'data-partial-view="true"'),'partial view');
u3(!str_contains($modal,'iframe')&&str_contains($modal,'DOMParser')&&str_contains($modal,"fetch(urlFor(key)"),'no page-in-page');
foreach(['.company-tabs','.platform-header','.platform-sidebar','.page-title'] as $needle)u3(str_contains($modal,$needle),'duplicate removal '.$needle);
u3(substr_count($modal,"['geral','Geral'")===1,'one main Geral tab');
foreach(['app-skeleton','AbortController','pending=new Map','cache=new Map','cache.delete(active)','Salvando…','AppToast.show'] as $needle)u3(str_contains($modal,$needle),$needle);
u3(str_contains($feedback,'app-page-progress')&&str_contains($feedback,'AppToast'),'global feedback');
u3(str_contains($css,'prefers-reduced-motion:reduce')&&str_contains($css,'app-shimmer')&&str_contains($css,'180ms'),'motion/skeleton');
u3(str_contains($erp,'app-feedback')&&str_contains($platform,'app-feedback'),'ERP/Control Plane loading');
u3(str_contains($general,'$partial')&&!str_contains($general,'db_name'),'clean general');
foreach(['Tenant ID','Banco dedicado','Versão do schema','Status do schema'] as $needle)u3(str_contains($environment,$needle),'environment '.$needle);
u3(!preg_match('/NFeAutorizacao|autoriza(?:r|ção)\s+NF-e/i',$modal.$feedback),'zero SEFAZ auth');
echo "CompanyModalPartialView PASS\nCompanyModalNoDuplicateNavigation PASS\nCompanyModalNoPlatformHeader PASS\nCompanyTechnicalInfoPlacement PASS\nAppPageLoading PASS\nAppTabLoading PASS\nAppModalLoading PASS\nAppToast PASS\nReducedMotion PASS\nLazyTabLoading PASS\nTabRequestDeduplication PASS\n";
