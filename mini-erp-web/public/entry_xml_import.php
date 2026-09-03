<?php
declare(strict_types=1);

use MiniErp\Services\NfeEntryXmlImportService;

require __DIR__ . '/../vendor/autoload.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$tenantId=(int)($_SESSION['erp_tenant_id']??0);$actorId=(int)($_SESSION['erp_user_id']??0);
if($tenantId<1||$actorId<1){http_response_code(401);echo json_encode(['success'=>false,'message'=>'Sessão expirada.']);exit;}
try{
    if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST')throw new RuntimeException('Método não permitido.');
    if(($_SERVER['HTTP_SEC_FETCH_SITE']??'same-origin')==='cross-site')throw new RuntimeException('Origem não permitida.');
    if(!hash_equals((string)($_SESSION['erp_fiscal_csrf']??''),(string)($_POST['csrf_token']??'')))throw new RuntimeException('Sessão fiscal expirada. Atualize a página.');
    if(!isset($_FILES['xml'])||($_FILES['xml']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('Não foi possível receber o arquivo XML.');
    $name=(string)($_FILES['xml']['name']??'nfe.xml');if(strtolower(pathinfo($name,PATHINFO_EXTENSION))!=='xml')throw new RuntimeException('Selecione um arquivo com extensão XML.');
    $xml=(string)file_get_contents((string)$_FILES['xml']['tmp_name']);
    $cfg=require __DIR__.'/../config.php';$db=$cfg['db'];$options=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC];
    $main=new PDO("mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset=utf8mb4",$db['username'],$db['password'],$options);
    $lookup=$main->prepare('SELECT db_name,nome_fantasia FROM tenants WHERE id=?');$lookup->execute([$tenantId]);$tenant=$lookup->fetch(PDO::FETCH_ASSOC)?:[];$database=(string)($tenant['db_name']??'');
    if(!preg_match('/^mini_erp_tenant_[1-9]\d*$/',$database))throw new RuntimeException('Empresa não encontrada.');
    $pdo=new PDO("mysql:host={$db['host']};port={$db['port']};dbname={$database};charset=utf8mb4",$db['username'],$db['password'],$options);
    $service=new NfeEntryXmlImportService($pdo);$analysis=$service->analyze($xml,$name);$action=(string)($_POST['xml_action']??'analyze');
    $analysis['catalog_import_allowed']=true;
    if($action==='analyze'){echo json_encode(['success'=>true,'analysis'=>$analysis],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
    if(!in_array($action,['catalog','import'],true)||($_POST['confirm_supplier']??'')!=='1')throw new RuntimeException('Confirme o fornecedor antes de importar.');
    if($action==='import'&&empty($analysis['invoice']['recipient_match']))throw new RuntimeException('Este XML pode ser usado para cadastro, mas não como entrada: o destinatário é outra empresa.');
    $partyType=$action==='catalog'?(string)($_POST['party_type']??'fornecedor'):'fornecedor';$result=$service->persist($analysis,$name,$partyType);error_log('ENTRY_XML_IMPORTED tenant='.$tenantId.' actor='.$actorId.' key='.$analysis['access_key'].' mode='.$action.' party='.$partyType.' products='.count($result['products']));
    $partyLabel=$partyType==='cliente'?'Cliente':'Fornecedor';$message=$action==='catalog'?"{$partyLabel}, produtos e impostos cadastrados. Nenhuma entrada ou movimentação de estoque foi criada.":'Fornecedor, produtos e impostos importados. Revise a entrada e clique em Gravar.';
    echo json_encode(['success'=>true,'mode'=>$action,'message'=>$message,'result'=>$result],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}catch(Throwable$error){http_response_code(422);error_log('ENTRY_XML_IMPORT_FAILED tenant='.$tenantId.' actor='.$actorId.' type='.get_class($error).' error='.$error->getMessage());echo json_encode(['success'=>false,'message'=>$error->getMessage()],JSON_UNESCAPED_UNICODE);}
