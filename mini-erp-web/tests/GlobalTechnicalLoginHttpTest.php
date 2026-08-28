<?php
declare(strict_types=1);
ob_start();
if(getenv('RUN_GLOBAL_TECH_HTTP_TESTS')!=='1'){echo"GlobalTechnicalLoginHttp SKIPPED\n";exit;}
require __DIR__.'/../vendor/autoload.php';
use MiniErp\Infrastructure\ControlPlaneConnectionFactory;
use MiniErp\Repositories\PlatformAdminRepository;

function gtHttp(bool $condition,string $label):void{if(!$condition)throw new RuntimeException($label);echo$label." PASS\n";}
function gtRequest(string$url,string$sid,?array$post=null):array{$c=curl_init($url);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_COOKIE=>'PHPSESSID='.$sid,CURLOPT_TIMEOUT=>20]);if($post!==null)curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($post)]);$raw=(string)curl_exec($c);$status=(int)curl_getinfo($c,CURLINFO_RESPONSE_CODE);$header=(int)curl_getinfo($c,CURLINFO_HEADER_SIZE);curl_close($c);return['status'=>$status,'headers'=>substr($raw,0,$header),'body'=>substr($raw,$header)];}

$pdo=(new ControlPlaneConnectionFactory(__DIR__.'/../config.php'))->create();$tenant=$pdo->query("SELECT id,slug FROM tenants WHERE blocked=0 AND status IN ('ativo','ativa','active') AND db_name=CONCAT('mini_erp_tenant_',id) ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);gtHttp((bool)$tenant,'GlobalTechActiveTenantFixtureTest');
$email='global-tech-'.bin2hex(random_bytes(6)).'@example.test';$password='GlobalTech12345';$repository=new PlatformAdminRepository($pdo);$id=$repository->create('GLOBAL TECH TEST_ONLY',$email,password_hash($password,PASSWORD_DEFAULT),'GLOBAL_TECH','TEST_ONLY');$sid='globaltech'.bin2hex(random_bytes(8));$csrf=bin2hex(random_bytes(24));
try{
    session_id($sid);session_start();$_SESSION=['erp_login_csrf'=>$csrf];session_write_close();
    $base='http://127.0.0.1/MiniRP/mini-erp-web/public/';
    $login=gtRequest($base.'login.php?empresa='.rawurlencode((string)$tenant['slug']),$sid,['action'=>'tenant_login','csrf_token'=>$csrf,'email'=>$email,'senha'=>$password]);
    gtHttp($login['status']===302&&str_contains($login['headers'],'page=dashboard'),'GlobalTechAnyTenantLoginHttpTest status='.$login['status'].' response='.preg_replace('/\s+/',' ',strip_tags((string)$login['body'])));
    if(preg_match('/PHPSESSID=([^;]+)/',$login['headers'],$sessionMatch))$sid=$sessionMatch[1];
    $dashboard=gtRequest($base.'?page=dashboard',$sid);
    gtHttp($dashboard['status']===200&&str_contains($dashboard['body'],'Dashboard analítico'),'GlobalTechTenantRuntimeHttpTest status='.$dashboard['status'].' response='.mb_substr(preg_replace('/\s+/',' ',strip_tags((string)$dashboard['body'])),0,500));
    session_id($sid);@session_start();gtHttp((int)($_SESSION['erp_global_admin_id']??0)===$id&&(int)($_SESSION['erp_tenant_id']??0)===(int)$tenant['id'],'GlobalTechExplicitSessionScopeTest');session_write_close();
}finally{
    $pdo->prepare('DELETE FROM platform_admin_audit_log WHERE admin_id=?')->execute([$id]);$pdo->prepare('DELETE FROM platform_admin_users WHERE id=?')->execute([$id]);session_id($sid);@session_start();$_SESSION=[];session_destroy();
}
