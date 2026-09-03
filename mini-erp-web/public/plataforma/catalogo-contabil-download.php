<?php
declare(strict_types=1);
use MiniErp\Platform\AccountingCatalogExportService;
require_once __DIR__.'/_context.php';
[$main,,,$identity]=requireAuthorizedPlatformContext();require_once __DIR__.'/../../vendor/autoload.php';
try{
 if(!hash_equals((string)($_SESSION['platform_operations_csrf']??''),(string)($_GET['csrf_token']??'')))throw new RuntimeException('CSRF inválido.');
 $service=new AccountingCatalogExportService();$kind=(string)($_GET['kind']??'report');
 if($kind==='template'){$name=(string)($_GET['entity']??'');$table=$service->template($name);$filename='MODELO-IMPORTACAO-'.strtoupper($name).'.csv';}
 else{$tenantId=(int)($_GET['tenant_id']??0);$stmt=$main->prepare('SELECT db_name,nome_fantasia FROM tenants WHERE id=? AND blocked=0 LIMIT 1');$stmt->execute([$tenantId]);$tenant=$stmt->fetch(PDO::FETCH_ASSOC);if(!$tenant||!preg_match('/^mini_erp_tenant_[1-9]\d*$/',(string)$tenant['db_name']))throw new RuntimeException('Empresa ou banco dedicado inválido.');$cfg=require __DIR__.'/../../config.php';$db=$cfg['db'];$pdo=new PDO("mysql:host={$db['host']};port={$db['port']};dbname={$tenant['db_name']};charset=utf8mb4",$db['username'],$db['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);$name=(string)($_GET['report']??'');$table=$service->export($pdo,$name);$filename='CONTABILIDADE-'.strtoupper($name).'-TENANT-'.$tenantId.'.csv';}
 header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="'.$filename.'"');header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');echo$service->csv($table);
}catch(Throwable$e){http_response_code(422);header('Content-Type: text/plain; charset=UTF-8');echo$e->getMessage();}
