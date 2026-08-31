<?php
declare(strict_types=1);
session_start();
require_once __DIR__.'/../vendor/autoload.php';

$slug=strtolower(trim((string)($_POST['empresa']??'')));
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'||!hash_equals((string)($_SESSION['erp_login_csrf']??''),(string)($_POST['csrf_token']??''))){header('Location: /login.php?empresa='.rawurlencode($slug).'&registration_error=session');exit;}
try{
 $main=(new \MiniErp\Infrastructure\ControlPlaneConnectionFactory(__DIR__.'/../config.php'))->create();
 (new \MiniErp\Services\TenantAccessRegistrationService($main,__DIR__.'/../config.php'))->register($slug,['name'=>$_POST['nome']??'','email'=>$_POST['email']??'','phone'=>$_POST['telefone']??'','password'=>$_POST['senha']??'','provider'=>'password']);
 header('Location: /login.php?empresa='.rawurlencode($slug).'&registered=1');
}catch(DomainException|InvalidArgumentException $e){$_SESSION['registration_error']=$e->getMessage();header('Location: /login.php?empresa='.rawurlencode($slug).'&registration_error=1');}catch(Throwable $e){error_log('TENANT_REGISTRATION_FAILED '.get_class($e).' '.substr($e->getMessage(),0,300));header('Location: /login.php?empresa='.rawurlencode($slug).'&registration_error=server');}
exit;
