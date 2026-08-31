<?php
declare(strict_types=1);

use MiniErp\Fiscal\PrivateCertificateStorage;
use MiniErp\Repositories\{FiscalConfigurationRepository,TenantEstablishmentRepository};

require __DIR__.'/../vendor/autoload.php';
if(session_status()!==PHP_SESSION_ACTIVE)session_start();

$tenant=(int)($_SESSION['erp_tenant_id']??0);
$user=(int)($_SESSION['erp_user_id']??0);
$token=(string)($_GET['token']??'');
if($tenant<1||$user<1||!hash_equals((string)($_SESSION['erp_establishment_csrf']??''),$token)){
 http_response_code(403);exit('Download nao autorizado.');
}

try{
 $cfg=require __DIR__.'/../config.php';$d=$cfg['db'];
 $main=new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$d['database']};charset=utf8mb4",$d['username'],$d['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $q=$main->prepare('SELECT db_name FROM tenants WHERE id=?');$q->execute([$tenant]);$db=(string)$q->fetchColumn();
 if(!preg_match('/^mini_erp_tenant_[1-9]\d*$/',$db))throw new RuntimeException('Empresa invalida.');
 $pdo=new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$db};charset=utf8mb4",$d['username'],$d['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 $est=(new TenantEstablishmentRepository($pdo))->findPrimaryForTenant($tenant);
 if(!$est)throw new RuntimeException('Estabelecimento nao encontrado.');
 $certificate=(new FiscalConfigurationRepository($pdo,$tenant))->latestCertificate((int)$est['id']);
 if(!$certificate)throw new RuntimeException('Certificado nao encontrado.');
 $content=(new PrivateCertificateStorage(__DIR__.'/../storage/fiscal/certificates'))->read((string)$certificate['storage_reference']);
 $name=preg_replace('/[^A-Za-z0-9._-]/','_',basename((string)($certificate['file_name']?:'certificado.pfx')))?:'certificado.pfx';
 header_remove('Content-Type');header('Content-Type: application/x-pkcs12');header('Content-Length: '.strlen($content));
 header('Content-Disposition: attachment; filename="'.$name.'"');header('Cache-Control: no-store, private');header('X-Content-Type-Options: nosniff');
 echo $content;
}catch(Throwable $e){error_log('ERP_CERTIFICATE_DOWNLOAD_FAILED tenant='.$tenant.' actor='.$user.' type='.get_class($e));http_response_code(404);echo 'Certificado indisponivel.';}
