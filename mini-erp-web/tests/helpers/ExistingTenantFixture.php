<?php
declare(strict_types=1);
/** @return array{0:array{id:int,db:string},1:array{id:int,db:string}} */
function existingTenantPair():array
{
    $cfg=require __DIR__.'/../../config.php';$d=$cfg['db'];$pdo=new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$d['database']};charset=utf8mb4",$d['username'],$d['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);$rows=$pdo->query("SELECT id,db_name FROM tenants WHERE db_name REGEXP '^mini_erp_tenant_[1-9][0-9]*$' ORDER BY id LIMIT 2")->fetchAll();if(count($rows)<2)throw new RuntimeException('Fixture requires two registered tenant databases.');return[['id'=>(int)$rows[0]['id'],'db'=>(string)$rows[0]['db_name']],['id'=>(int)$rows[1]['id'],'db'=>(string)$rows[1]['db_name']]];
}
