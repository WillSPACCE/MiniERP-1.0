<?php
declare(strict_types=1);require __DIR__.'/../vendor/autoload.php';
use MiniErp\Services\PersonFiscalData;
function employeeAccessAssert(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$person=(new PersonFiscalData(['person_type'=>'PF','cpf_cnpj'=>'52998224725','state_registration_indicator'=>'9','tipo_pessoa'=>['funcionario']]))->toArray();
employeeAccessAssert($person['role_employee']===1,'employee role must be accepted');
$root=dirname(__DIR__);$ui=(string)file_get_contents($root.'/public/includes/master_data_configuration.php');$js=(string)file_get_contents($root.'/public/assets/master-data.js');$action=(string)file_get_contents($root.'/public/master_data_action.php');$service=(string)file_get_contents($root.'/src/Services/EmployeeAccessService.php');
foreach(['value="funcionario"','data-employee-access','Criar login','Trocar senha','Desativar login'] as $needle)employeeAccessAssert(str_contains($ui,$needle),'UI '.$needle);
foreach(['employee_access_create','employee_access_password','employee_access_status','renderAccess'] as $needle)employeeAccessAssert(str_contains($js.$action,$needle),'flow '.$needle);
foreach(['password_hash','tenant_id','assertActorCanManage','Você não pode desativar o próprio acesso'] as $needle)employeeAccessAssert(str_contains($service,$needle),'security '.$needle);
employeeAccessAssert(!str_contains($service,'SELECT *'),'password hash must not be returned');
echo "EmployeePersonAccessTest OK\n";
