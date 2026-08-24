<?php
declare(strict_types=1);

use MiniErp\Fiscal\FiscalArtifactStorage;
use MiniErp\Repositories\{FiscalArtifactRepository,FiscalDocumentEventRepository};
require_once __DIR__ . '/../vendor/autoload.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$tenantId=(int)($_SESSION['erp_tenant_id']??$_SESSION['tenant_id']??0);$userId=(int)($_SESSION['erp_user_id']??$_SESSION['user_id']??0);
if($tenantId<1||$userId<1){http_response_code(401);exit('Sessão expirada.');}
try{
 $cfg=require __DIR__.'/../config.php';$d=$cfg['db'];$main=new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$d['database']};charset=utf8mb4",$d['username'],$d['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $q=$main->prepare('SELECT db_name FROM tenants WHERE id=?');$q->execute([$tenantId]);$db=(string)$q->fetchColumn();if(!preg_match('/^mini_erp_tenant_[1-9]\d*$/',$db))throw new RuntimeException('TENANT_NOT_FOUND');
 $pdo=new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$db};charset=utf8mb4",$d['username'],$d['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $artifactId=filter_input(INPUT_GET,'artifact_id',FILTER_VALIDATE_INT)?:0;$artifact=(new FiscalArtifactRepository($pdo,$tenantId))->findById($artifactId);if(!$artifact)throw new DomainException('ARTIFACT_NOT_FOUND');
 $storage=new FiscalArtifactStorage(__DIR__.'/../storage/fiscal/artifacts');$path=$storage->resolve((string)$artifact['storage_reference']);if(!is_file($path))throw new RuntimeException('ARTIFACT_FILE_MISSING');
 $actual=hash_file('sha256',$path);if(!hash_equals((string)$artifact['sha256'],$actual)){(new FiscalDocumentEventRepository($pdo,$tenantId))->append((int)$artifact['fiscal_document_id'],'ARTIFACT_INTEGRITY_FAILED','ARTIFACT','FAILED','ARTIFACT_INTEGRITY_FAILED','SHA-256 do XML divergente.',[], $userId);http_response_code(409);exit('ARTIFACT_INTEGRITY_FAILED');}
 $xml=file_get_contents($path);$mode=($_GET['mode']??'inline')==='download'?'attachment':'inline';$name='NFE-'.preg_replace('/\D/','',(string)$artifact['access_key']).'.xml';
 header('Content-Type: application/xml; charset=UTF-8');header('Content-Disposition: '.$mode.'; filename="'.$name.'"');header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');header('Content-Length: '.strlen((string)$xml));echo$xml;
}catch(DomainException){http_response_code(404);exit('Artifact não encontrado.');}catch(Throwable){http_response_code(422);exit('Não foi possível disponibilizar o XML.');}
