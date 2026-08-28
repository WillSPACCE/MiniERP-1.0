<?php
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';
use MiniErp\Authorization\PersistedPlatformAdminAuthorizer;
use MiniErp\Context\AuthenticatedPlatformAdmin;

function globalTechAssert(bool $condition,string $label):void{if(!$condition)throw new RuntimeException($label);echo$label." PASS\n";}

$identity=new AuthenticatedPlatformAdmin(77,'tech@example.test','Técnico','GLOBAL_TECH');
globalTechAssert((new PersistedPlatformAdminAuthorizer())->isAuthorized($identity),'GlobalTechPlatformAuthorizationTest');
$login=(string)file_get_contents(__DIR__.'/../public/login.php');$runtime=(string)file_get_contents(__DIR__.'/../public/index.php');$panel=(string)file_get_contents(__DIR__.'/../public/plataforma/tecnicos.php');$migrations=(string)file_get_contents(__DIR__.'/../src/Platform/MultiTenantMigrationService.php');$operations=(string)file_get_contents(__DIR__.'/../public/plataforma/operacoes-multitenant.php');$manager=(string)file_get_contents(__DIR__.'/../../servidor-manager/app/Program.cs');
globalTechAssert(str_contains($login,"['SUPER_ADMIN','GLOBAL_TECH']")&&str_contains($login,'GLOBAL_ERP_LOGIN'),'GlobalTechAnyTenantLoginTest');
globalTechAssert(str_contains($runtime,"erp_global_admin_id")&&str_contains($runtime,"TenantConnectionResolver")&&str_contains($runtime,"['SUPER_ADMIN','GLOBAL_TECH']"),'GlobalTechSessionRestoreTest');
globalTechAssert(!str_contains($login,'INSERT INTO')&&!str_contains($runtime,'user_tenant_access'),'GlobalTechFutureTenantAutomaticAccessTest');
globalTechAssert(str_contains($panel,"'GLOBAL_TECH'")&&str_contains($panel,'Somente SUPER_ADMIN'),'GlobalTechPanelCreationTest');
globalTechAssert(str_contains($migrations,"['SUPER_ADMIN','GLOBAL_TECH']")&&str_contains($operations,"['SUPER_ADMIN','GLOBAL_TECH']"),'GlobalTechMultiTenantOperationsTest');
globalTechAssert(str_contains($manager,'trycloudflare')&&str_contains($manager,'tunnel --no-autoupdate --url http://127.0.0.1:8000'),'PublicTunnelApplicationOnlyTest');
globalTechAssert(str_contains($manager,'FormClosed += (_, _) => tunnel.Stop()')&&str_contains($manager,'Abrir / copiar')&&str_contains($manager,'BaseUrl+"/plataforma/"'),'PublicTunnelLifecycleTest');
