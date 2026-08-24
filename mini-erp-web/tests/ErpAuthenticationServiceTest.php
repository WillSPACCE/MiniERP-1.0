<?php
declare(strict_types=1);
require_once __DIR__.'/../src/Contracts/ErpAuthenticationReaderContract.php';
require_once __DIR__.'/../src/Context/AuthenticatedTenantUser.php';
require_once __DIR__.'/../src/Context/TenantContext.php';
require_once __DIR__.'/../src/Adapters/LegacyTenantContextInput.php';
require_once __DIR__.'/../src/Context/TenantContextResolver.php';
require_once __DIR__.'/../src/Services/ErpAuthenticationResult.php';
require_once __DIR__.'/../src/Services/ErpAuthenticationService.php';
use MiniErp\Contracts\ErpAuthenticationReaderContract;
use MiniErp\Context\TenantContextResolver;
use MiniErp\Services\ErpAuthenticationService;
function erpAssert(bool $condition,string $message):void{if(!$condition){fwrite(STDERR,"ASSERTION FAILED: $message\n");exit(1);}}
final class FakeErpReader implements ErpAuthenticationReaderContract
{
    public array $users; public array $tenants;
    public function __construct(){
        $this->users=['a@test.local'=>['id'=>10,'nome'=>'User A','email'=>'a@test.local','senha'=>password_hash('Valid!123',PASSWORD_DEFAULT),'status'=>'ativo','tenant_id'=>14], 'inactive@test.local'=>['id'=>11,'nome'=>'Inactive','email'=>'inactive@test.local','senha'=>password_hash('Valid!123',PASSWORD_DEFAULT),'status'=>'inativo','tenant_id'=>14]];
        $this->tenants=['empresa-a'=>['tenant_id'=>14,'nome_fantasia'=>'Empresa A','razao_social'=>'Empresa A','slug'=>'empresa-a','status'=>'ativa','blocked'=>0,'db_name'=>'mini_erp_tenant_14'],'empresa-b'=>['tenant_id'=>15,'nome_fantasia'=>'Empresa B','razao_social'=>'Empresa B','slug'=>'empresa-b','status'=>'ativa','blocked'=>0,'db_name'=>'mini_erp_tenant_15'],'blocked'=>['tenant_id'=>16,'nome_fantasia'=>'Blocked','razao_social'=>'Blocked','slug'=>'blocked','status'=>'ativa','blocked'=>1,'db_name'=>'mini_erp_tenant_16'],'pending'=>['tenant_id'=>17,'nome_fantasia'=>'Pending','razao_social'=>'Pending','slug'=>'pending','status'=>'cadastrada','blocked'=>0,'db_name'=>null]];
    }
    public function findUserByEmail(string $email):?array{return $this->users[$email]??null;}
    public function findUserById(int $id):?array{foreach($this->users as $u)if($u['id']===$id)return $u;return null;}
    public function findTenantBySlug(string $slug):?array{return $this->tenants[$slug]??null;}
    public function findTenantById(int $id):?array{foreach($this->tenants as $t)if($t['tenant_id']===$id)return $t;return null;}
}
$reader=new FakeErpReader();$service=new ErpAuthenticationService($reader,new TenantContextResolver());$result=$service->authenticate('A@TEST.LOCAL','Valid!123','empresa-a');
erpAssert($result->identity->getUserId()===10,'valid MAIN identity authenticates');erpAssert($result->tenantContext->getEffectiveTenantId()===14,'tenant context is canonical');erpAssert($service->restore(10,14)->tenantContext->getEffectiveTenantId()===14,'session identity is revalidated');
foreach([['a@test.local','wrong','empresa-a'],['a@test.local','Valid!123','empresa-b'],['inactive@test.local','Valid!123','empresa-a'],['a@test.local','Valid!123','blocked'],['a@test.local','Valid!123','pending'],['missing@test.local','Valid!123','empresa-a']] as $case){try{$service->authenticate(...$case);erpAssert(false,'invalid, cross-tenant, inactive, blocked and unprovisioned access must fail');}catch(DomainException){}}
echo "ErpAuthenticationService OK\n";
