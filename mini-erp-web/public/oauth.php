<?php
declare(strict_types=1);
session_start();
require_once __DIR__.'/../vendor/autoload.php';

$providers=[
 'google'=>['client'=>'MINI_ERP_GOOGLE_CLIENT_ID','secret'=>'MINI_ERP_GOOGLE_CLIENT_SECRET','authorize'=>'https://accounts.google.com/o/oauth2/v2/auth','token'=>'https://oauth2.googleapis.com/token','userinfo'=>'https://openidconnect.googleapis.com/v1/userinfo','scope'=>'openid profile email'],
 'linkedin'=>['client'=>'MINI_ERP_LINKEDIN_CLIENT_ID','secret'=>'MINI_ERP_LINKEDIN_CLIENT_SECRET','authorize'=>'https://www.linkedin.com/oauth/v2/authorization','token'=>'https://www.linkedin.com/oauth/v2/accessToken','userinfo'=>'https://api.linkedin.com/v2/userinfo','scope'=>'openid profile email'],
 'facebook'=>['client'=>'MINI_ERP_FACEBOOK_CLIENT_ID','secret'=>'MINI_ERP_FACEBOOK_CLIENT_SECRET','authorize'=>'https://www.facebook.com/v23.0/dialog/oauth','token'=>'https://graph.facebook.com/v23.0/oauth/access_token','userinfo'=>'https://graph.facebook.com/me?fields=id,name,email','scope'=>'public_profile email'],
];
$provider=strtolower((string)($_GET['provider']??''));$slug=strtolower(trim((string)($_GET['empresa']??'')));
$back=static function(string $slug,string $message):never{$_SESSION['oauth_error']=$message;header('Location: /login.php?empresa='.rawurlencode($slug).'&oauth_error=1');exit;};
if(!isset($providers[$provider])||$slug==='')$back($slug,'Abra o login pelo link correto da empresa.');
$cfg=$providers[$provider];$client=trim((string)getenv($cfg['client']));$secret=trim((string)getenv($cfg['secret']));
if($client===''||$secret==='')$back($slug,'Login com '.ucfirst($provider).' ainda não foi configurado pelo administrador da plataforma.');
$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';$host=(string)($_SERVER['HTTP_HOST']??'localhost');$redirect=$scheme.'://'.$host.'/oauth.php?provider='.rawurlencode($provider).'&empresa='.rawurlencode($slug);

if(empty($_GET['code'])){
 $state=bin2hex(random_bytes(32));$_SESSION['oauth_flow_'.$state]=['provider'=>$provider,'slug'=>$slug,'created'=>time()];
 $query=['client_id'=>$client,'redirect_uri'=>$redirect,'response_type'=>'code','scope'=>$cfg['scope'],'state'=>$state];
 header('Location: '.$cfg['authorize'].'?'.http_build_query($query,'','&',PHP_QUERY_RFC3986));exit;
}
$state=(string)($_GET['state']??'');$flow=$_SESSION['oauth_flow_'.$state]??null;unset($_SESSION['oauth_flow_'.$state]);
if(!is_array($flow)||!hash_equals((string)$flow['provider'],$provider)||!hash_equals((string)$flow['slug'],$slug)||(int)$flow['created']<time()-600)$back($slug,'A autorização expirou. Tente novamente.');
try{
 $post=http_build_query(['grant_type'=>'authorization_code','code'=>(string)$_GET['code'],'client_id'=>$client,'client_secret'=>$secret,'redirect_uri'=>$redirect]);
 $curl=curl_init($cfg['token']);curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$post,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>['Accept: application/json','Content-Type: application/x-www-form-urlencoded']]);$raw=curl_exec($curl);$http=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_close($curl);
 $token=json_decode((string)$raw,true);if($http<200||$http>=300||empty($token['access_token']))throw new RuntimeException('Token OAuth recusado.');
 $curl=curl_init($cfg['userinfo']);curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>['Accept: application/json','Authorization: Bearer '.$token['access_token']]]);$raw=curl_exec($curl);$http=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_close($curl);$profile=json_decode((string)$raw,true);
 $subject=(string)($profile['sub']??$profile['id']??'');$email=strtolower(trim((string)($profile['email']??'')));$name=trim((string)($profile['name']??''));
 if($http<200||$http>=300||$subject===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||$name==='')throw new RuntimeException('A rede social não forneceu nome e e-mail.');
 $main=(new \MiniErp\Infrastructure\ControlPlaneConnectionFactory(__DIR__.'/../config.php'))->create();$result=(new \MiniErp\Services\TenantAccessRegistrationService($main,__DIR__.'/../config.php'))->register($slug,['name'=>$name,'email'=>$email,'provider'=>$provider,'provider_subject'=>$subject]);
 if(!empty($result['active'])){session_regenerate_id(true);$_SESSION['erp_user_id']=(int)$result['user_id'];$_SESSION['erp_tenant_id']=(int)$result['tenant_id'];$_SESSION['erp_tenant_slug']=$slug;header('Location: /?page=dashboard');exit;}
 header('Location: /login.php?empresa='.rawurlencode($slug).'&registered=1');exit;
}catch(Throwable $e){error_log('OAUTH_FLOW_FAILED provider='.$provider.' type='.get_class($e).' message='.substr($e->getMessage(),0,250));$back($slug,'Não foi possível validar os dados de '.ucfirst($provider).'.');}
