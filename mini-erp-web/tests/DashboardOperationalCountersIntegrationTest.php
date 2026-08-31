<?php
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use MiniErp\Repositories\DashboardRepository;

$config=require __DIR__.'/../config.php';
$db=$config['db'];
$pdo=new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname=mini_erp_tenant_14;charset=utf8mb4",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
);
$dashboard=(new DashboardRepository($pdo,14))->analytics([
    'from'=>'2020-01-01','to'=>'2099-12-31','customer_id'=>0,'model'=>'','status'=>'',
]);

foreach(['clientes','produtos','issued_orders','stock_balance','estoque_baixo','notes'] as $key){
    if(!array_key_exists($key,$dashboard))throw new RuntimeException('Missing dashboard counter: '.$key);
}
if($dashboard['stock_balance']<0)throw new RuntimeException('Invalid stock balance.');
if(!isset($dashboard['notes']['rejected']))throw new RuntimeException('Missing rejected notes counter.');

echo "DashboardOperationalCountersIntegration OK\n";
