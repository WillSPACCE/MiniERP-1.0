<?php
declare(strict_types=1);
function crudStaticAssert(bool $condition,string $message):void{if(!$condition){fwrite(STDERR,"ASSERTION FAILED: {$message}\n");exit(1);}}
$repository=file_get_contents(__DIR__.'/../app/Repository.php');$entry=file_get_contents(__DIR__.'/../public/index.php');
crudStaticAssert(str_contains($repository,'$data[\'fone_principal\'] ?? $data[\'telefone\']')&&str_contains($repository,'$data[\'telefone\'] ?? $fone1'),'telefone and fone_principal compatibility is explicit');
crudStaticAssert(str_contains($repository,"hasColumn('clientes', 'tenant_id')"),'client SQL adapts to physically isolated databases without tenant_id column');
crudStaticAssert(!str_contains(substr($repository,strpos($repository,'public function saveCliente'),strpos($repository,'public function deleteCliente')-strpos($repository,'public function saveCliente')),'Database::setTenantDbName'),'client save never switches database');
crudStaticAssert(str_contains($entry,'name="q"')&&str_contains($entry,'listClientes((string) ($_GET[\'q\']'),'existing client UI provides backend search');
crudStaticAssert(substr_count($entry,'name="csrf_token" value="<?= htmlspecialchars($_SESSION[\'erp_client_csrf\']')>=2,'save and delete are CSRF-protected');
crudStaticAssert(str_contains($entry,'client_saved=1')&&str_contains($entry,'client_deleted=1'),'client mutations use POST/Redirect/GET');
echo "ErpClientCrudStatic OK\n";
