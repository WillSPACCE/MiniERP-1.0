<?php
declare(strict_types=1);

require_once __DIR__.'/../src/Repositories/DashboardRepository.php';

use MiniErp\Repositories\DashboardRepository;

function dashboardAssert(bool $condition,string $label):void{if(!$condition)throw new RuntimeException($label);echo$label." PASS\n";}

$pdo=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec("CREATE TABLE clientes(id INTEGER PRIMARY KEY,tenant_id INTEGER);CREATE TABLE produtos(id INTEGER PRIMARY KEY,tenant_id INTEGER,nome TEXT,estoque_atual NUMERIC,minimum_stock NUMERIC,unidade TEXT,status TEXT);CREATE TABLE fiscal_orders(id INTEGER PRIMARY KEY,tenant_id INTEGER,operation_type TEXT,commercial_status TEXT,operation_date TEXT,grand_total NUMERIC);");
$pdo->exec("INSERT INTO clientes VALUES(1,14),(2,14),(3,15);INSERT INTO produtos VALUES(1,14,'Baixo',2,3,'UN','ativo'),(2,14,'Normal',10,3,'KG','ativo'),(3,14,'Inativo',0,5,'UN','inativo'),(4,15,'Outro tenant',0,5,'UN','ativo');");
$insert=$pdo->prepare('INSERT INTO fiscal_orders VALUES(?,?,?,?,?,?)');
foreach([[1,14,'EXIT','SAVED','2026-08-21',100],[2,14,'EXIT','SAVED','2026-08-27',250],[3,14,'ENTRY','SAVED','2026-08-27',999],[4,14,'EXIT','CANCELLED','2026-08-27',888],[5,14,'EXIT','DRAFT','2026-08-27',777],[6,14,'EXIT','SAVED','2026-08-20',50],[7,15,'EXIT','SAVED','2026-08-27',5000]]as$row)$insert->execute($row);

$repo=new DashboardRepository($pdo,14,new DateTimeZone('America/Sao_Paulo'));
$data=$repo->read(new DateTimeImmutable('2026-08-27',new DateTimeZone('America/Sao_Paulo')));
dashboardAssert($data['clientes']===2&&$data['produtos']===3,'DashboardCustomerProductCountTest');
dashboardAssert($data['vendas']===3,'DashboardSalesCountTest');
dashboardAssert(abs($data['faturamento']-400.0)<0.001,'DashboardRevenueTest');
dashboardAssert($data['vendas']!==4&&$data['faturamento']<1000,'DashboardCancelledOrdersExcludedTest');
dashboardAssert($data['vendas']!==4&&$data['faturamento']<1000,'DashboardEntryOrdersExcludedTest');
dashboardAssert(count($data['sales_last_7_days'])===7&&$data['sales_last_7_days'][0]['date']==='2026-08-21'&&$data['sales_last_7_days'][6]['date']==='2026-08-27','DashboardLast7DaysTest');
dashboardAssert($data['sales_last_7_days'][0]['revenue']===100.0&&$data['sales_last_7_days'][6]['revenue']===250.0,'DashboardLast7DaysTimezoneTest');
dashboardAssert(abs(array_sum(array_column($data['sales_last_7_days'],'revenue'))-350.0)<0.001,'DashboardChartTotalTest');
dashboardAssert($data['stock_movements']['available']===false&&$data['stock_movements']['orders']===[],'DashboardStockMovementSourceTest');
dashboardAssert($data['estoque_baixo']===1&&$data['low_stock_products'][0]['nome']==='Baixo'&&(float)$data['low_stock_products'][0]['minimum_stock']===3.0,'DashboardLowStockTest');
dashboardAssert($data['vendas']===3&&$data['faturamento']===400.0,'DashboardTenantIsolationTest');

$ui=(string)file_get_contents(__DIR__.'/../public/index.php');
dashboardAssert(str_contains($ui,"\$dashboard['sales_by_day']")&&!str_contains($ui,"estoque_atual'] <= 5"),'DashboardCanonicalUiSourceTest');
