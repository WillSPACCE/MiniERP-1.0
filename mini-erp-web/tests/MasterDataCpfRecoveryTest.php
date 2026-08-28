<?php
declare(strict_types=1);
function cpfRecoveryAssert(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$action=file_get_contents(__DIR__.'/../public/master_data_action.php');$js=file_get_contents(__DIR__.'/../public/assets/master-data.js');$ui=file_get_contents(__DIR__.'/../public/includes/master_data_configuration.php');
cpfRecoveryAssert(str_contains($action,"\$_POST['document']=\$canonicalDocument")&&str_contains($action,"\$_POST['cpf_cnpj']=\$canonicalDocument"),'CPF aliases must use the same canonical value');
cpfRecoveryAssert(str_contains($action,'$document=$canonicalDocument'),'duplicate check must use the persisted CPF');
cpfRecoveryAssert(str_contains($js,'responsePayload')&&str_contains($js,'Seus dados continuam no formul'),'non-JSON failures must preserve and explain form state');
cpfRecoveryAssert(str_contains($js,'revealField')&&str_contains($js,'corrigir os dados ou fechar esta janela'),'failed submissions must remain recoverable');
cpfRecoveryAssert(str_contains($ui,'master-data.js?v=7')&&str_contains($ui,'master-data-fixes.css?v=5'),'browser cache must receive the fix');
echo "MasterDataCpfRecovery OK\n";
