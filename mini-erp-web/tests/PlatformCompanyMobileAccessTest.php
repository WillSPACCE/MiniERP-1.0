<?php
declare(strict_types=1);
function mobileAccessAssert(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$page=file_get_contents(__DIR__.'/../public/plataforma/index.php');$css=file_get_contents(__DIR__.'/../public/assets/platform.css');
foreach(['platform-company-table','mobile-company-login','company-name-login','Entrar na empresa','/plataforma/empresa-acao.php?id=','action=erp','csrf_token=','platform.css?v=mobile6']as$needle)mobileAccessAssert(str_contains($page,$needle),'page '.$needle);
foreach(['@media (max-width: 760px)','.platform-company-table thead { display: none; }','.mobile-company-login','min-height: 48px','.company-action-cell { display: none; }']as$needle)mobileAccessAssert(str_contains($css,$needle),'responsive '.$needle);
mobileAccessAssert(strpos($css,'.mobile-company-login { display: flex;')>strpos($css,'.mobile-company-login { display: none; }'),'mobile login must become visible inside the mobile breakpoint');
echo "PlatformCompanyMobileAccess OK\n";
