<?php
declare(strict_types=1);
if(getenv('RUN_FISCAL_MARIADB_TESTS')!=='1'){echo "ProductMariaDbIntegration SKIPPED\n";exit;}
require_once __DIR__.'/../app/Repository.php';
$c=require __DIR__.'/../config.php';$d=$c['db'];
$pdo=new PDO(sprintf('mysql:host=%s;port=%s;dbname=mini_erp_tenant_14;charset=utf8mb4',$d['host'],$d['port']),$d['username'],$d['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
if(session_status()===PHP_SESSION_NONE)session_start();
$_SESSION=['erp_user_id'=>999999,'erp_tenant_id'=>14,'user_id'=>999999,'tenant_id'=>14,'current_company_id'=>14];
$repo=new Repository($pdo,false);$pdo->beginTransaction();
try{
 $code='FISCAL04-ROLLBACK-'.bin2hex(random_bytes(3));
 $data=['nome'=>'FISCAL-04 FIXTURE ROLLBACK','codigo'=>$code,'ncm'=>'12345678','cest'=>'1234567','merchandise_origin'=>'0','extipi'=>'01','tax_benefit_code'=>'PR123','fci_number'=>'TEST-FCI','unidade'=>'CX','taxable_unit'=>'UN','conversion_factor'=>'12','gtin'=>'7891234567895','gtin_tributable'=>'SEM GTIN','cfop_padrao'=>'5102','categoria'=>'TESTE','cost_price'=>'10.5','preco'=>'20.5','estoque_atual'=>'7','minimum_stock'=>'2','status'=>'ativo'];
 $repo->saveProduto($data);$id=(int)$pdo->lastInsertId();$row=$repo->findProduto($id);
 foreach(['nome','codigo','ncm','cest','merchandise_origin','extipi','tax_benefit_code','fci_number','unidade','taxable_unit','gtin','gtin_tributable','cfop_padrao','categoria','status'] as $f)if((string)$row[$f]!== (string)$data[$f])throw new RuntimeException('round-trip '.$f);
 $data['id']=$id;$data['nome']='FISCAL-04 UPDATED';$data['ncm']='87654321';$repo->saveProduto($data);$updated=$repo->findProduto($id);if($updated['nome']!=='FISCAL-04 UPDATED'||$updated['ncm']!=='87654321')throw new RuntimeException('update');
 $repo->deleteProduto($id);if($repo->findProduto($id)['status']!=='inativo')throw new RuntimeException('inactivation');echo "ProductMariaDbIntegration OK (rollback)\n";
}finally{if($pdo->inTransaction())$pdo->rollBack();}
