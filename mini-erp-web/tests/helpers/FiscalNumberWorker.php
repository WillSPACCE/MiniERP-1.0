<?php
declare(strict_types=1);
require __DIR__.'/../../vendor/autoload.php';use MiniErp\Fiscal\FiscalNumberAllocator;
$cfg=require __DIR__.'/../../config.php';$d=$cfg['db'];$pdo=new PDO(sprintf('mysql:host=%s;port=%s;dbname=mini_erp_tenant_14;charset=utf8mb4',$d['host'],$d['port']),$d['username'],$d['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$result=(new FiscalNumberAllocator($pdo,(int)$argv[1]))->reserve((int)$argv[2],$argv[3],(int)$argv[4],(int)$argv[5],999);echo json_encode($result,JSON_THROW_ON_ERROR);
