<?php
declare(strict_types=1);$root=dirname(__DIR__);$ui=(string)file_get_contents($root.'/public/includes/master_data_configuration.php');$js=(string)file_get_contents($root.'/public/assets/master-data.js');
function pfPjAssert(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
foreach(['data-document-label','data-name-label','00.000.000/0000-00'] as $needle)pfPjAssert(str_contains($ui,$needle),'UI '.$needle);pfPjAssert(str_contains($js,'000.000.000-00'),'CPF placeholder');
foreach(["type==='PF'","type==='PJ'","button.hidden=!isPj","if(form.elements.person_type.value!=='PJ')return",'formatCpf','formatCnpj','finally{saving=false','button.textContent=button.dataset.normalText','if(saving)return','dialog.open||dialog.showModal()','md-modal-open','scrollIntoView'] as $needle)pfPjAssert(str_contains($js,$needle),'controller '.$needle);
pfPjAssert(substr_count($js,"fetch(`/ajax_cnpj.php")===1,'single shared CNPJ call');pfPjAssert(strpos($js,"if(form.elements.person_type.value!=='PJ')return")<strpos($js,"fetch(`/ajax_cnpj.php"),'PF guard before BrasilAPI');
echo "PeoplePfPjRecoveryTest OK\n";
