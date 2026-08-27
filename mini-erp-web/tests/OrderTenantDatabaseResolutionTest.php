<?php
declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';
use MiniErp\Repositories\IssuedOrdersRepository;
use MiniErp\Repositories\FiscalOperationRepository;

function tenantDbAssert(bool $condition,string $label):void{if(!$condition)throw new RuntimeException($label);}
$config=require __DIR__.'/../config.php';$db=$config['db'];$userId=(int)(getenv('REAL_ERP_USER_ID')?:9);
$main=new PDO("mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset=utf8mb4",$db['username'],$db['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$query=$main->prepare('SELECT u.tenant_id,u.company_id,t.db_name,t.nome_fantasia FROM usuarios u JOIN tenants t ON t.id=u.tenant_id WHERE u.id=? AND u.status=\'ativo\' LIMIT 1');$query->execute([$userId]);$context=$query->fetch();tenantDbAssert(is_array($context),'authenticated ERP context');
$tenantId=(int)$context['tenant_id'];$schema=(string)$context['db_name'];tenantDbAssert((int)$context['company_id']===$tenantId&&preg_match('/^mini_erp_tenant_[1-9]\d*$/',$schema)===1,'canonical tenant database');
$tenantPdo=new PDO("mysql:host={$db['host']};port={$db['port']};dbname={$schema};charset=utf8mb4",$db['username'],$db['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
tenantDbAssert((string)$tenantPdo->query('SELECT DATABASE()')->fetchColumn()===$schema,'SELECT DATABASE');
$orders=new FiscalOperationRepository($tenantPdo,$tenantId);$issued=new IssuedOrdersRepository($tenantPdo,$tenantId);tenantDbAssert($orders->pdo()===$tenantPdo&&is_array($issued->paginate([],1,20)['rows']),'save/list same PDO');
echo "OrderTenantDatabaseResolution OK user={$userId} tenant={$tenantId} schema={$schema} company={$context['nome_fantasia']}\n";
