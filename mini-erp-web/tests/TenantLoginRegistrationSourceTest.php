<?php
declare(strict_types=1);
$root=dirname(__DIR__);$login=(string)file_get_contents($root.'/public/login.php');$register=(string)file_get_contents($root.'/public/register.php');$oauth=(string)file_get_contents($root.'/public/oauth.php');$service=(string)file_get_contents($root.'/src/Services/TenantAccessRegistrationService.php');
function registrationAssert(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
registrationAssert(str_contains($login,'action="/register.php"')&&str_contains($login,'name="empresa"'),'tenant-bound normal form');
foreach(['facebook','google','linkedin'] as $provider)registrationAssert(str_contains($login,'provider='.$provider),'social '.$provider.' link');
registrationAssert(str_contains($register,"hash_equals")&&str_contains($register,"'provider'=>'password'"),'normal registration CSRF');
registrationAssert(str_contains($service,"status=\"pendente\"")&&str_contains($service,'mini_erp_tenant_'),'pending tenant isolation');
registrationAssert(str_contains($service,'INSERT INTO clientes')&&str_contains($service,'pessoa_id'),'person link');
registrationAssert(str_contains($oauth,"session_regenerate_id(true)")&&str_contains($oauth,"oauth_flow_"),'OAuth state and session rotation');
echo "TenantLoginRegistrationSource OK\n";
