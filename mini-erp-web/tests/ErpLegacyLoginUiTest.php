<?php
declare(strict_types=1);
function loginUiAssert(bool $condition,string $message):void{if(!$condition){fwrite(STDERR,"ASSERTION FAILED: {$message}\n");exit(1);}}
$login=file_get_contents(__DIR__.'/../public/login.php');
foreach(['/assets/style.css','/assets/login.css','/assets/login.js','logo_login.png','login-shell','overlay-container','login-footer'] as $legacyAsset)loginUiAssert(str_contains($login,$legacyAsset),"legacy login asset/layout must remain: {$legacyAsset}");
loginUiAssert(str_contains($login,'$_GET[\'empresa\']')&&str_contains($login,'findTenantBySlug($tenantSlug)'),'displayed company is resolved from explicit MAIN slug');
loginUiAssert(str_contains($login,'mb_convert_case($storedName, MB_CASE_TITLE'),'stored company name is presented consistently without Default Tenant fallback');
loginUiAssert(str_contains($login,"'tenant_login'")&&str_contains($login,'ErpAuthenticationService'),'same styled form uses canonical authentication service');
loginUiAssert(!str_contains($login,'$_SESSION[\'tenant_id\']')&&!str_contains($login,'$_SESSION[\'current_company_id\']'),'login UI does not resolve company from legacy session');
echo "ErpLegacyLoginUi OK\n";
