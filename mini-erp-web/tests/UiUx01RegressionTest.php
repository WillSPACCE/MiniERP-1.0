<?php
declare(strict_types=1);
function ux(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$root=dirname(__DIR__);$layout=file_get_contents($root.'/public/plataforma/_layout.php');$erp=file_get_contents($root.'/public/index.php');$ui=file_get_contents($root.'/public/assets/app-ui.js');$css=file_get_contents($root.'/public/assets/app-ui.css');$fiscal=file_get_contents($root.'/public/plataforma/empresa-fiscal-central.php');$config=file_get_contents($root.'/public/assets/fiscal-config-ui.js');$state=file_get_contents($root.'/src/Services/FlashFormState.php');
ux(str_contains($layout,'favicon-32x32.png')&&str_contains($erp,'favicon-32x32.png'),'favicon global');
foreach(['showModal','app-modal-open','Existem alterações não salvas','aria-selected','ArrowRight','focus'] as $needle)ux(str_contains($ui,$needle),'modal/tabs '.$needle);
ux(str_contains($css,'.app-modal__body{overflow:auto')&&str_contains($css,'@media(max-width:720px)'),'scroll/responsive');
foreach(['geral','cfop','csc','icms','legacy','rtc','ready'] as $id)ux(str_contains($fiscal,'id="'.$id.'"'),'fiscal panel '.$id);
ux(str_contains($config,"['certificate','series']")&&str_contains($config,'data-app-tabs'),'certificate/series tabs');
ux(str_contains($state,'old_input')&&preg_match('/password|secret|csc/i',$state),'FormState/redaction');
ux(!str_contains($fiscal,'SQLSTATE'),'SQLSTATE leak');
ux(str_contains(file_get_contents($root.'/public/assets/cnpj-lookup.js'),'BrasilAPI'),'CNPJ component');
echo "UI-UX-01 OK\n";
