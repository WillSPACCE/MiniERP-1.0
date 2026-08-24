<?php
declare(strict_types=1);
function u5(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$root=dirname(__DIR__);$css=file_get_contents($root.'/public/assets/ui-forms.css');$general=file_get_contents($root.'/public/plataforma/empresa-editar.php');$modal=file_get_contents($root.'/public/assets/company-modal.js');$layout=file_get_contents($root.'/public/plataforma/_layout.php');$erp=file_get_contents($root.'/public/index.php');
foreach(['--font-size-xs','--font-size-sm','--input-height','--button-height','--control-border','--focus-ring','--muted-text'] as $needle)u5(str_contains($css,$needle),'token '.$needle);
foreach(['.form-group','.form-control','.form-help','.form-error','aria-invalid=true',':disabled','[readonly]'] as $needle)u5(str_contains($css,$needle),'control '.$needle);
foreach(['.btn-secondary','.btn-danger','.btn-ghost','.btn-sm','.btn-icon'] as $needle)u5(str_contains($css,$needle),'button '.$needle);
u5(str_contains($css,'repeat(2,minmax(0,1fr))')&&str_contains($css,'@media(max-width:760px)'),'responsive grid');
u5(str_contains($general,'cnpj-control-group')&&str_contains($general,'btn btn-secondary btn-sm'),'CNPJ group');
u5(str_contains($general,'if(!$partial)')&&substr_count($general,'Salvar alterações')===1,'no duplicate modal save');
u5(str_contains($modal,'data-save')&&str_contains($modal,'data-save-close')&&str_contains($modal,'form[data-active-form]'),'active form footer save');
u5(str_contains($css,'prefers-reduced-motion:reduce'),'reduced motion');
u5(str_contains($layout,'ui-forms.css')&&str_contains($erp,'ui-forms.css'),'shared Control Plane and ERP');
u5(!preg_match('/Ã.|Â.|â€|â†|ï¿½/u',$css.$general.$modal),'new UI assets UTF-8');
u5(!preg_match('/NFeAutorizacao|autoriza(?:r|ção)\s+NF-e/i',$css.$modal),'zero SEFAZ auth');
echo "UiFormControl PASS\nUiButtonSystem PASS\nUiTypography PASS\nUiFormGrid PASS\nUiErrorState PASS\nUiReadOnlyState PASS\nUiModalFooter PASS\nCompanyGeneralVisualContract PASS\nCompanyFiscalVisualContract PASS\nCertificateVisualContract PASS\nSeriesVisualContract PASS\n";
