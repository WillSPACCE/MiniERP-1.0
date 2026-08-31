<?php
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../app/Database.php';

$config=require __DIR__.'/../config.php';
$db=$config['db'];
$pdo=new PDO("mysql:host={$db['host']};port={$db['port']};dbname=mini_erp_tenant_14;charset=utf8mb4",$db['username'],$db['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
Database::useResolvedTenantConnection($pdo);
$_SESSION=['erp_tenant_id'=>14,'erp_stock_csrf'=>'test'];
$_GET=['page'=>'estoque'];
$assetUrl=static fn(string $path):string=>'/assets/'.$path;
set_error_handler(static function(int $severity,string $message):never{throw new ErrorException($message,0,$severity);});
ob_start();
include __DIR__.'/../public/includes/stock_page.php';
$html=(string)ob_get_clean();
restore_error_handler();
if(!str_contains($html,'Produtos em estoque')||!str_contains($html,'stock-product'))throw new RuntimeException('Stock products were not rendered.');
echo "StockPageDefaultRequest OK\n";
