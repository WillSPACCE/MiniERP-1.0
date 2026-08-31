<?php
declare(strict_types=1);

use MiniErp\Repositories\StockRepository;

require __DIR__.'/../vendor/autoload.php';
if(session_status()!==PHP_SESSION_ACTIVE)session_start();
$tenant=(int)($_SESSION['erp_tenant_id']??$_SESSION['tenant_id']??0);$user=(int)($_SESSION['erp_user_id']??$_SESSION['user_id']??0);
if($tenant<1||$user<1){http_response_code(401);exit('Sessão expirada.');}
if(!hash_equals((string)($_SESSION['erp_stock_csrf']??''),(string)($_POST['csrf_token']??''))){http_response_code(403);exit('Sessão do estoque expirada.');}
if(($_SERVER['HTTP_SEC_FETCH_SITE']??'same-origin')==='cross-site'){http_response_code(403);exit('Origem não permitida.');}
try{
 $cfg=require __DIR__.'/../config.php';$d=$cfg['db'];$main=new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$d['database']};charset=utf8mb4",$d['username'],$d['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$s=$main->prepare('SELECT db_name FROM tenants WHERE id=?');$s->execute([$tenant]);$db=(string)$s->fetchColumn();if(!preg_match('/^mini_erp_tenant_[1-9]\d*$/',$db))throw new RuntimeException('Empresa inválida.');$pdo=new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$db};charset=utf8mb4",$d['username'],$d['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);$stock=new StockRepository($pdo,$tenant);$action=(string)($_POST['stock_action']??'');
 if($action==='location')$stock->createLocation((string)($_POST['name']??''),(string)($_POST['code']??''));
 elseif($action==='create_lot')$stock->createLot((int)($_POST['product_id']??0),(int)($_POST['location_id']??0),(string)($_POST['lot_code']??''),(string)($_POST['quantity']??''),(string)($_POST['manufactured_at']??'')?:null,(string)($_POST['expires_at']??'')?:null,(string)($_POST['reason']??''),$user);
 elseif($action==='receive')$stock->receive((int)($_POST['product_id']??0),(int)($_POST['location_id']??0),(string)($_POST['lot_code']??''),(string)($_POST['quantity']??''),(string)($_POST['reason']??''),$user,'MANUAL_ENTRY',null);
 elseif($action==='pending_lot')$stock->createPendingLot((int)($_POST['location_id']??0),(string)($_POST['lot_code']??''),(string)($_POST['quantity']??''),(string)($_POST['manufactured_at']??'')?:null,(string)($_POST['expires_at']??'')?:null,(string)($_POST['source_document']??''),(string)($_POST['notes']??''),$user);
 elseif($action==='link_pending')$stock->linkPendingLot((int)($_POST['pending_id']??0),(int)($_POST['product_id']??0),$user);
 elseif($action==='block')$stock->setBlocked((int)($_POST['lot_id']??0),($_POST['blocked']??'1')==='1',(string)($_POST['reason']??''),$user);
 elseif($action==='adjust')$stock->adjust((int)($_POST['lot_id']??0),(string)($_POST['adjustment_type']??''),(string)($_POST['quantity']??''),(string)($_POST['reason']??''),$user);
 elseif($action==='transfer')$stock->transfer((int)($_POST['lot_id']??0),(int)($_POST['destination_location_id']??0),(string)($_POST['quantity']??''),(string)($_POST['reason']??''),$user);
 else throw new RuntimeException('Ação de estoque inválida.');
 $_SESSION['stock_flash']=['type'=>'success','message'=>'Movimentação de estoque concluída e registrada no histórico.'];header('Location: /?page=estoque');
}catch(Throwable$e){error_log('STOCK_ACTION_FAILED tenant='.$tenant.' actor='.$user.' type='.get_class($e));$_SESSION['stock_flash']=['type'=>'error','message'=>$e->getMessage()];header('Location: /?page=estoque');}
