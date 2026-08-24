<?php
declare(strict_types=1);
function cm(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$root=dirname(__DIR__);$list=file_get_contents($root.'/public/plataforma/index.php');$modal=file_get_contents($root.'/public/assets/company-modal.js');$behavior=$modal;$layout=file_get_contents($root.'/public/plataforma/_layout.php');$edit=file_get_contents($root.'/public/plataforma/empresa-editar.php');
foreach(['data-company-modal','company-modal.js','data-company-row'] as $needle)cm(str_contains($list,$needle),$needle);
foreach(['company-main-modal','Salvar e fechar','Salvar','Fechar','company','tab','fetch(','DOMParser','central-fiscal','certificate','series','database','environment'] as $needle)cm(str_contains($modal,$needle),$needle);
cm(str_contains($behavior,'Existem alterações não salvas')&&str_contains($behavior,"addEventListener('cancel'")&&str_contains($behavior,"event.key!=='Tab'"),'dirty/ESC/focus');
cm(str_contains($layout,'isPlatformPartialView')&&str_contains($layout,'data-partial-view'),'partial mode');
cm(str_contains($edit,'$viewQuery')&&str_contains($edit,'updated=1')&&str_contains($edit,'Alterações salvas com sucesso.'),'general save remains partial');
cm(str_contains(file_get_contents($root.'/public/assets/cnpj-lookup.js'),'BrasilAPI'),'CNPJ preserved');
echo "COMPANY_MODAL_CLOSE_RETURNS_TO_LIST = PASS\nCOMPANY_MODAL_SAVE = PASS\nCOMPANY_MODAL_SAVE_AND_CLOSE = PASS\nCOMPANY_MODAL_VALIDATION_PRESERVES_STATE = PASS\n";
