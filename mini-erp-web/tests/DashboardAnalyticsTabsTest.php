<?php
declare(strict_types=1);

require_once __DIR__.'/../src/Repositories/DashboardRepository.php';

use MiniErp\Repositories\DashboardRepository;

function analyticsAssert(bool $condition,string $label):void{if(!$condition)throw new RuntimeException($label);echo$label." PASS\n";}

$pdo=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec("CREATE TABLE clientes(id INTEGER PRIMARY KEY,tenant_id INTEGER,nome TEXT);
CREATE TABLE produtos(id INTEGER PRIMARY KEY,tenant_id INTEGER,nome TEXT,estoque_atual NUMERIC,minimum_stock NUMERIC,unidade TEXT,status TEXT);
CREATE TABLE fiscal_orders(id INTEGER PRIMARY KEY,tenant_id INTEGER,person_id INTEGER,operation_type TEXT,commercial_status TEXT,operation_date TEXT,grand_total NUMERIC,fiscal_model TEXT);
CREATE TABLE fiscal_order_items(id INTEGER PRIMARY KEY,order_id INTEGER,product_id INTEGER,quantity NUMERIC,unit_price NUMERIC,net_total NUMERIC);
CREATE TABLE fiscal_documents(id INTEGER PRIMARY KEY,tenant_id INTEGER,source_order_id INTEGER,status TEXT,totals_json TEXT,created_at TEXT);");
$pdo->exec("INSERT INTO clientes VALUES(1,14,'Cliente A'),(2,14,'Cliente B'),(3,15,'Intruso');
INSERT INTO produtos VALUES(1,14,'Produto A',1,2,'UN','ativo'),(2,14,'Produto B',8,2,'KG','ativo'),(3,15,'Produto X',0,5,'UN','ativo');
INSERT INTO fiscal_orders VALUES(1,14,1,'EXIT','SAVED','2026-08-25',100,'55'),(2,14,2,'EXIT','SAVED','2026-08-26',300,'65'),(3,14,1,'EXIT','CANCELLED','2026-08-26',900,'55'),(4,15,3,'EXIT','SAVED','2026-08-26',5000,'55');
INSERT INTO fiscal_order_items VALUES(1,1,1,2,25,50),(2,1,2,1,50,50),(3,2,1,3,100,300),(4,3,1,9,100,900),(5,4,3,50,100,5000);
INSERT INTO fiscal_documents VALUES(1,14,1,'AUTHORIZED','{\"model\":\"55\",\"grand\":\"100\"}','2026-08-25 10:00:00'),(2,14,2,'FISCAL_PENDING','{\"model\":\"65\",\"grand\":\"300\"}','2026-08-26 10:00:00'),(3,14,2,'REJECTED','{\"model\":\"65\",\"grand\":\"300\"}','2026-08-26 11:00:00'),(4,15,4,'AUTHORIZED','{\"model\":\"55\",\"grand\":\"5000\"}','2026-08-26 10:00:00');");

$repo=new DashboardRepository($pdo,14,new DateTimeZone('America/Sao_Paulo'));
$filters=['from'=>'2026-08-25','to'=>'2026-08-27','customer_id'=>0,'model'=>'','status'=>''];
$data=$repo->analytics($filters);
analyticsAssert($data['vendas']===2&&abs($data['faturamento']-400)<0.001&&abs($data['largest_sale']-300)<0.001,'DashboardOverviewTest');
analyticsAssert(abs($data['ticket_average']-200)<0.001,'DashboardTicketAverageTest');
analyticsAssert(count($data['sales_by_day'])===3&&$data['sales_by_day'][2]['revenue']===0.0,'DashboardSalesByDayTest');
analyticsAssert($data['top_products'][0]['nome']==='Produto A'&&(float)$data['top_products'][0]['quantity']===5.0,'DashboardTopProductsTest');
analyticsAssert(count($data['last_sold_items'])===3&&$data['last_sold_items'][0]['order_id']===2,'DashboardLastSoldItemsTest');
analyticsAssert($data['top_customers'][0]['customer_name']==='Cliente B','DashboardTopCustomersTest');
analyticsAssert(abs((float)$data['top_customers'][0]['revenue']-300)<0.001&&$data['best_customer']['orders_count']===1,'DashboardCustomerRevenueTest');
analyticsAssert($data['notes']['total']===3&&abs($data['notes']['fiscal_total']-100)<0.001,'DashboardNotesSummaryTest');
analyticsAssert($data['notes']['authorized']===1&&$data['notes']['pending']===1&&$data['notes']['rejected']===1,'DashboardNotesByStatusTest');
analyticsAssert($data['notes']['attention']===2,'DashboardFiscalAttentionTest');
analyticsAssert(array_sum(array_column($data['notes']['by_day'],'count'))===3,'DashboardNotesByDayTest');
analyticsAssert($data['stock']['sold_item_lines']===3&&(float)$data['stock']['sold_quantity']===6.0&&$data['stock']['movement']['available']===false,'DashboardStockSummaryTest');
analyticsAssert(count($data['last_sold_items'])===3,'DashboardLastStockSalesTest');
analyticsAssert($data['estoque_baixo']===1&&$data['low_stock_products'][0]['nome']==='Produto A','DashboardAnalyticsLowStockTest');

$customer=$repo->analytics([...$filters,'customer_id'=>1]);
analyticsAssert($customer['vendas']===1&&abs($customer['faturamento']-100)<0.001&&$customer['notes']['total']===1,'DashboardCustomerFilterTest');
$model=$repo->analytics([...$filters,'model'=>'65']);
analyticsAssert($model['vendas']===1&&$model['notes']['total']===2&&$model['notes']['by_model']['65']===2,'DashboardModelFilterTest');
$status=$repo->analytics([...$filters,'status'=>'authorized']);
analyticsAssert($status['notes']['total']===1&&$status['notes']['authorized']===1,'DashboardStatusFilterTest');
$date=$repo->analytics([...$filters,'from'=>'2026-08-26','to'=>'2026-08-26']);
analyticsAssert($date['vendas']===1&&abs($date['faturamento']-300)<0.001&&$date['notes']['total']===2,'DashboardDateFilterTest');
analyticsAssert($data['vendas']===2&&$data['notes']['total']===3&&(float)$data['stock']['sold_quantity']===6.0,'DashboardAnalyticsTenantIsolationTest');

$ui=(string)file_get_contents(__DIR__.'/../public/index.php');
foreach(['overview','sales','notes','customers','stock','financial']as$tab)analyticsAssert(str_contains($ui,"'{$tab}'"),'DashboardTab'.ucfirst($tab).'Test');
