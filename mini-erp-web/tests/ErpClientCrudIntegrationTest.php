<?php
declare(strict_types=1);
require_once __DIR__.'/../app/Database.php';
require_once __DIR__.'/../app/Repository.php';
require_once __DIR__.'/../src/Context/TenantContext.php';
require_once __DIR__.'/../src/Infrastructure/TenantConnectionResolver.php';
use MiniErp\Context\TenantContext;
use MiniErp\Infrastructure\TenantConnectionResolver;
function clientAssert(bool $condition,string $message):void{if(!$condition)throw new RuntimeException("ASSERTION FAILED: {$message}");}
$resolver=new TenantConnectionResolver(__DIR__.'/../config.php');
$pdo=$resolver->resolve(new TenantContext(9,14,14));
clientAssert($pdo->query('SELECT DATABASE()')->fetchColumn()==='mini_erp_tenant_14','tenant 14 connection must target its dedicated database');
if(session_status()===PHP_SESSION_NONE)session_start();
$_SESSION=['erp_user_id'=>9,'erp_tenant_id'=>14,'user_id'=>9,'tenant_id'=>14,'platform_user_id'=>1];
$repo=new Repository($pdo,false);$marker='ERPCRUD01_'.bin2hex(random_bytes(5));$email=strtolower($marker).'@example.test';$id=0;
$cleanup=$pdo->prepare('DELETE FROM clientes WHERE email = :email');
try{
    $cleanup->execute(['email'=>$email]);$pdo->beginTransaction();
    try{$repo->saveCliente(['nome'=>'','cpf_cnpj'=>'52998224725']);clientAssert(false,'missing required name must fail before INSERT');}catch(InvalidArgumentException){}
    try{$repo->saveCliente(['nome'=>'Telefone inválido','cpf_cnpj'=>'52998224725','telefone'=>'123','cep'=>'01001000','logradouro'=>'Rua']);clientAssert(false,'invalid primary phone must fail before INSERT');}catch(InvalidArgumentException){}
    $repo->saveCliente(['nome'=>$marker,'email'=>$email,'cpf_cnpj'=>'52998224725','telefone'=>'11987654321','cep'=>'01001000','logradouro'=>'Praça da Sé','numero'=>'100','bairro'=>'Sé','cidade'=>'São Paulo','codigo_ibge'=>'3550308','estado'=>'SP','status'=>'ativo']);
    $row=$pdo->prepare('SELECT * FROM clientes WHERE email = :email');$row->execute(['email'=>$email]);$created=$row->fetch();clientAssert($created!==false,'INSERT must persist in tenant database');$id=(int)$created['id'];clientAssert($created['telefone']==='11987654321'&&$created['fone_principal']==='11987654321','telefone must map explicitly to fone_principal');
    clientAssert($repo->findCliente($id)!==null,'created client must be readable after reload');clientAssert(count($repo->listClientes($marker))===1,'search must find persisted client');
    $repo->saveCliente(['id'=>$id,'nome'=>$marker.'_EDITADO','email'=>$email,'cpf_cnpj'=>'52998224725','fone_principal'=>'11999998888','cep'=>'01001000','logradouro'=>'Praça da Sé','numero'=>'101','bairro'=>'Sé','cidade'=>'São Paulo','codigo_ibge'=>'3550308','estado'=>'SP','status'=>'ativo']);
    $updated=$repo->findCliente($id);clientAssert(($updated['nome']??'')===$marker.'_EDITADO'&&($updated['fone_principal']??'')==='11999998888','UPDATE must be physically persisted');
    $_SESSION['tenant_id']=1;try{$repo->findCliente($id);clientAssert(false,'tampered compatibility tenant must fail');}catch(RuntimeException){}$_SESSION['tenant_id']=14;
    $pdoB=$resolver->resolve(new TenantContext(2,5,5));$checkB=$pdoB->prepare('SELECT COUNT(*) FROM clientes WHERE email = :email');$checkB->execute(['email'=>$email]);clientAssert((int)$checkB->fetchColumn()===0,'client marker must not exist in tenant B');
    $repo->deleteCliente($id);$check=$pdo->prepare('SELECT status FROM clientes WHERE id = :id');$check->execute(['id'=>$id]);clientAssert($check->fetchColumn()==='inativo','delete action must safely inactivate the person');
    $pdo->rollBack();clientAssert($_SESSION['erp_user_id']===9&&$_SESSION['erp_tenant_id']===14&&$_SESSION['platform_user_id']===1,'ERP and Platform sessions remain intact');
    echo "ErpClientCrudIntegration OK tenant=14 database=mini_erp_tenant_14 SQL=INSERT,SELECT,UPDATE,SELECT,INACTIVATE,SELECT rollback=confirmed\n";
}catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();throw $error;}finally{$cleanup->execute(['email'=>$email]);}
