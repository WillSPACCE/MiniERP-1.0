<?php
declare(strict_types=1);
if(getenv('RUN_TENANT_REGISTRATION_TESTS')!=='1'){echo "TenantAccessRegistration SKIPPED\n";exit;}
require __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../app/Database.php';
require_once __DIR__.'/../app/Repository.php';
use MiniErp\Infrastructure\ControlPlaneConnectionFactory;
use MiniErp\Services\TenantAccessRegistrationService;

$main=(new ControlPlaneConnectionFactory(__DIR__.'/../config.php'))->create();
$tenant=$main->query("SELECT id,slug,db_name FROM tenants WHERE id=14 AND blocked=0 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if(!$tenant)throw new RuntimeException('Tenant 14 unavailable');
$stamp=bin2hex(random_bytes(5));$email='registration-'.$stamp.'@example.invalid';$userId=0;$personId=0;
$cfg=require __DIR__.'/../config.php';$db=$cfg['db'];$tenantPdo=new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',$db['host'],$db['port'],$tenant['db_name']),$db['username'],$db['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
try{
 $result=(new TenantAccessRegistrationService($main,__DIR__.'/../config.php'))->register((string)$tenant['slug'],['name'=>'REGISTRATION TEST_ONLY','email'=>$email,'phone'=>'41999999999','password'=>'Registration123!','provider'=>'password']);
 $userId=(int)$result['user_id'];$personId=(int)$result['person_id'];
 $central=$main->query('SELECT status,role,tenant_id,pessoa_id FROM usuarios WHERE id='.$userId)->fetch(PDO::FETCH_ASSOC);
 $local=$tenantPdo->query('SELECT status,role,pessoa_id FROM usuarios WHERE email='.$tenantPdo->quote($email))->fetch(PDO::FETCH_ASSOC);
 $person=$tenantPdo->query('SELECT nome,email,fone_principal FROM clientes WHERE id='.$personId)->fetch(PDO::FETCH_ASSOC);
 if(($central['status']??'')!=='pendente'||(int)$central['tenant_id']!==14||(int)$central['pessoa_id']!==$personId)throw new RuntimeException('central pending/link');
 if(($local['status']??'')!=='pendente'||(int)$local['pessoa_id']!==$personId)throw new RuntimeException('tenant pending/link');
 if(($person['email']??'')!==$email||($person['fone_principal']??'')!=='41999999999')throw new RuntimeException('person registration');
 $localUserId=(int)$tenantPdo->query('SELECT id FROM usuarios WHERE email='.$tenantPdo->quote($email))->fetchColumn();
 if(session_status()===PHP_SESSION_NONE)session_start();$_SESSION['erp_tenant_id']=14;$_SESSION['erp_user_id']=(int)$main->query("SELECT id FROM usuarios WHERE tenant_id=14 AND status='ativo' ORDER BY id LIMIT 1")->fetchColumn();
 (new Repository($tenantPdo,false))->approveUsuario($localUserId,'admin');
 $approved=$main->query('SELECT status,role,email_verified FROM usuarios WHERE id='.$userId)->fetch(PDO::FETCH_ASSOC);
 if(($approved['status']??'')!=='ativo'||($approved['role']??'')!=='admin'||(int)($approved['email_verified']??0)!==1)throw new RuntimeException('approval synchronization');
 echo "TenantAccessRegistration OK\n";
}finally{
 $main->prepare('DELETE FROM user_oauth_identities WHERE user_id=?')->execute([$userId]);$main->prepare('DELETE FROM usuarios WHERE tenant_id=14 AND email=?')->execute([$email]);
 $tenantPdo->prepare('DELETE FROM usuarios WHERE email=?')->execute([$email]);if($personId)$tenantPdo->prepare('DELETE FROM clientes WHERE id=?')->execute([$personId]);
 unset($_SESSION['erp_tenant_id'],$_SESSION['erp_user_id'],$_SESSION['tenant_id'],$_SESSION['user_id']);
}
