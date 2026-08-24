<?php
declare(strict_types=1);
function uiAssert(bool $value,string $message):void{if(!$value){fwrite(STDERR,"ASSERTION FAILED: $message\n");exit(1);}}
$legacy=file_get_contents(__DIR__.'/../public/index.php');$erpEntry=file_get_contents(__DIR__.'/../public/erp/index.php');$login=file_get_contents(__DIR__.'/../public/login.php');$compatLogin=file_get_contents(__DIR__.'/../public/erp/login.php');
foreach(['Dashboard','PEDIDOS','CADASTRO','Pessoas','Produtos','Fornecedores','Motoristas','Transportadoras','CONFIGURAÇÃO'] as $label)uiAssert(str_contains($legacy,$label),"legacy UI must retain $label");
uiAssert(str_contains($legacy,'ErpLegacyBootstrap')&&str_contains($legacy,'$secureErpRuntime[\'user\']'),'legacy dashboard uses the restored MAIN identity');
uiAssert(str_contains($login,'/?page=dashboard')&&str_contains($erpEntry,'/?page=dashboard')&&str_contains($compatLogin,"'/login.php'"),'tenant entrypoints converge on styled login and existing dashboard');
uiAssert(!str_contains($erpEntry,'<aside')&&!str_contains($erpEntry,'TenantErpReadRepository'),'parallel ERP visual was removed');
echo "ErpLegacyUiRegression OK\n";
