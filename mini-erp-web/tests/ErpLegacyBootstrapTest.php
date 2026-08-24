<?php
declare(strict_types=1);
require_once __DIR__.'/../src/Contracts/ErpAuthenticationReaderContract.php';
require_once __DIR__.'/../src/Context/AuthenticatedTenantUser.php';
require_once __DIR__.'/../src/Context/TenantContext.php';
require_once __DIR__.'/../src/Adapters/LegacyTenantContextInput.php';
require_once __DIR__.'/../src/Adapters/ErpLegacyBootstrap.php';
require_once __DIR__.'/../src/Context/TenantContextResolver.php';
require_once __DIR__.'/../src/Infrastructure/TenantConnectionResolver.php';
require_once __DIR__.'/../src/Services/ErpAuthenticationResult.php';
require_once __DIR__.'/../src/Services/ErpAuthenticationService.php';
use MiniErp\Adapters\ErpLegacyBootstrap;
use MiniErp\Context\TenantContext;
use MiniErp\Context\TenantContextResolver;
use MiniErp\Contracts\ErpAuthenticationReaderContract;
use MiniErp\Infrastructure\TenantConnectionResolver;
use MiniErp\Services\ErpAuthenticationService;
function bridgeAssert(bool $value,string $message):void{if(!$value){fwrite(STDERR,"ASSERTION FAILED: $message\n");exit(1);}}
final class BridgeReader implements ErpAuthenticationReaderContract
{
    public function findUserByEmail(string $email):?array{return null;}
    public function findUserById(int $id):?array{return $id===9?['id'=>9,'nome'=>'Willyan','email'=>'w@test.local','role'=>'admin','avatar'=>'','status'=>'ativo','tenant_id'=>14]:null;}
    public function findTenantBySlug(string $slug):?array{return null;}
    public function findTenantById(int $id):?array{return $id===14?['tenant_id'=>14,'nome_fantasia'=>'Willyan Info','razao_social'=>'Willyan Info','slug'=>'willyaninfo','status'=>'ativa','blocked'=>0,'db_name'=>'mini_erp_tenant_14']:null;}
}
final class BridgeConnections extends TenantConnectionResolver
{
    public array $contexts=[];
    public function resolve(TenantContext $context):PDO{$this->contexts[]=$context;$pdo=new PDO('sqlite::memory:');$pdo->sqliteCreateFunction('DATABASE',static fn():string=>'mini_erp_tenant_'.$context->getEffectiveTenantId());return $pdo;}
}
$reader=new BridgeReader();$connections=new BridgeConnections();$bridge=new ErpLegacyBootstrap($reader,new ErpAuthenticationService($reader,new TenantContextResolver()),$connections);
$session=['erp_user_id'=>9,'erp_tenant_id'=>14,'tenant_id'=>999,'current_company_id'=>999,'platform_user_id'=>77];$result=$bridge->bootstrap($session);
bridgeAssert($result['database']==='mini_erp_tenant_14','resolved PDO belongs to tenant 14');bridgeAssert($session['user_id']===9&&$session['tenant_id']===14,'legacy keys derive only from secure ERP identity');bridgeAssert(!isset($session['current_company_id']),'current_company_id is removed as authority');bridgeAssert($session['platform_user_id']===77,'platform session remains untouched');bridgeAssert($connections->contexts[0]->getEffectiveTenantId()===14,'resolver receives canonical TenantContext');
foreach([['erp_user_id'=>9,'erp_tenant_id'=>15],['erp_user_id'=>0,'erp_tenant_id'=>14],['erp_user_id'=>9]] as $invalid){try{$bridge->bootstrap($invalid);bridgeAssert(false,'missing or cross-tenant session must fail');}catch(DomainException|InvalidArgumentException){}}
echo "ErpLegacyBootstrap OK\n";
