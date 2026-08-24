<?php
declare(strict_types=1);
function entryAssert(bool $condition,string $message):void{if(!$condition){fwrite(STDERR,"ASSERTION FAILED: {$message}\n");exit(1);}}
$login=file_get_contents(__DIR__.'/../public/login.php');$compatLogin=file_get_contents(__DIR__.'/../public/erp/login.php');$runtime=file_get_contents(__DIR__.'/../public/index.php');$erpEntry=file_get_contents(__DIR__.'/../public/erp/index.php');$logout=file_get_contents(__DIR__.'/../public/erp/logout.php');$list=file_get_contents(__DIR__.'/../public/plataforma/index.php');$detail=file_get_contents(__DIR__.'/../public/plataforma/empresa.php');
entryAssert(str_contains($login,'ErpAuthenticationService')&&str_contains($login,'TenantConnectionResolver'),'styled legacy login delegates authentication and connection validation');
entryAssert(str_contains($login,'$_SESSION[\'erp_user_id\']')&&str_contains($login,'$_SESSION[\'erp_tenant_id\']'),'official login creates dedicated ERP identity');
entryAssert(!str_contains($login,'$_POST[\'tenant_id\']')&&!str_contains($login,'$_POST[\'company_id\']')&&!str_contains($login,'$_POST[\'db_name\']'),'tenant and database cannot come from POST');
entryAssert(str_contains($login,"header('Location: /?page=dashboard')"),'valid login redirects to existing dashboard');
entryAssert(str_contains($runtime,'ErpLegacyBootstrap')&&str_contains($runtime,'Database::useResolvedTenantConnection'),'legacy ERP receives context-resolved connection at one boundary');
entryAssert(str_contains($erpEntry,"'/?page=dashboard'")&&!str_contains($erpEntry,'<aside'),'the /erp entrypoint renders no parallel ERP');
entryAssert(str_contains($compatLogin,"'/login.php'")&&!str_contains($compatLogin,'<form'),'old T10A URL only redirects to styled login');
entryAssert(str_contains($logout,'$_SESSION[\'tenant_id\']')&&!str_contains($logout,'session_destroy'),'logout removes compatibility keys without destroying platform session');
entryAssert(str_contains($list,'/login.php?empresa=')&&str_contains($detail,'/login.php?empresa='),'platform buttons target styled login directly');
echo "ErpEntrypointIsolation OK\n";
