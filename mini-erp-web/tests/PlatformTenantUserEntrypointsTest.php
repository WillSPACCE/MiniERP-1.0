<?php

declare(strict_types=1);

$files=['empresa-usuarios.php','empresa-usuario-novo.php','empresa-usuario-editar.php','empresa-usuario-senha.php'];$source='';foreach($files as $file){$item=file_get_contents(__DIR__.'/../public/plataforma/'.$file);if($item===false){fwrite(STDERR,"ASSERTION FAILED: {$file} missing\n");exit(1);}$source.=$item;}
$repository=file_get_contents(__DIR__.'/../src/Repositories/PlatformTenantUserRepository.php');
function userSourceAssert(bool $condition,string $message):void{if(!$condition){fwrite(STDERR,"ASSERTION FAILED: {$message}\n");exit(1);}}
userSourceAssert(substr_count($source,"'POST'")>=4,'all write routes explicitly guard POST');
userSourceAssert(strpos($source,'requirePlatformUserCsrf')!==false,'write routes require shared CSRF validation');
userSourceAssert(strpos($source,"\$_POST['tenant_id']")===false&&strpos($source,"\$_POST['company_id']")===false&&strpos($source,"\$_POST['db_name']")===false,'scope never comes from payload');
userSourceAssert(strpos($source,"\$_SESSION['tenant_id'] =")===false&&strpos($source,"\$_SESSION['current_company_id'] =")===false,'ERP session is never changed');
userSourceAssert(strpos($source,'Database::setTenantDbName')===false&&strpos($source,'TenantConnectionResolver')===false,'tenant database is never selected');
userSourceAssert(strpos($source,'password_hash')===false,'password hashing stays in service, outside UI');
userSourceAssert(strpos($repository,'WHERE id = :id AND tenant_id = :tenant_id')!==false,'repository scopes targets by user and tenant');
userSourceAssert(strpos(strtoupper($repository),'DELETE FROM')===false,'T06 never deletes users');
echo "PlatformTenantUserEntrypoints OK\n";
