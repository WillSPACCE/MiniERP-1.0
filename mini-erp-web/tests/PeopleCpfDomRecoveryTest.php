<?php
declare(strict_types=1);
$root=dirname(__DIR__);$js=(string)file_get_contents($root.'/public/assets/master-data.js');$validator=(string)file_get_contents($root.'/public/assets/cpf-validator.js');$css=(string)file_get_contents($root.'/public/assets/master-data-fixes.css');$view=(string)file_get_contents($root.'/public/includes/master_data_configuration.php');$action=(string)file_get_contents($root.'/public/master_data_action.php');
function dom50(bool$ok,string$message):void{if(!$ok)throw new RuntimeException($message);}
foreach(['MiniErpCpfValidator','replace(/\D/g','cpf.length!==11','/^(\d)\1{10}$/']as$needle)dom50(str_contains($validator,$needle),'validator '.$needle);dom50(strpos($view,'cpf-validator.js')<strpos($view,'master-data.js'),'validator loads before form');
foreach(['payload.fields?.cpf_cnpj','invalidate(documentField','documentField.focus()','finally{saving=false','button.disabled=false','button.textContent=button.dataset.normalText','dialog.open||dialog.showModal()','documentField?.addEventListener(\'input\',()=>documentError(\'\'))']as$needle)dom50(str_contains($js,$needle),'recovery '.$needle);
foreach(['grid-template-rows:auto auto auto minmax(0,1fr) auto','.md-form>.md-pane.active','overflow-y:auto','.md-form>footer','position:relative','body.md-modal-open']as$needle)dom50(str_contains($css,$needle),'stable modal/footer '.$needle);
foreach(["['code']='CPF_INVALID'","['fields']=['cpf_cnpj'=>'CPF inválido.']",'http_response_code($validation?422:404)',"'ok'=>false"]as$needle)dom50(str_contains($action,$needle),'422 contract '.$needle);
dom50(str_contains($js,"type==='PF'&&!cpfValid")&&str_contains($js,"type==='PJ'&&!cnpjValid"),'PF and PJ routes remain separate');
echo "PeopleCpfDomRecoveryTest OK\n";
