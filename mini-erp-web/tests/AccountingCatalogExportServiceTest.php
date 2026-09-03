<?php
declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';
use MiniErp\Platform\AccountingCatalogExportService;
function accountingAssert(bool$c,string$m):void{if(!$c)throw new RuntimeException($m);}
$pdo=new PDO('sqlite::memory:');$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$pdo->exec("CREATE TABLE produtos(codigo TEXT,nome TEXT,ncm TEXT,merchandise_origin TEXT,tax_benefit_code TEXT,unidade TEXT,cfop_padrao TEXT,status TEXT);CREATE TABLE tax_rule_versions(id INTEGER,priority INTEGER,rule_version INTEGER,status TEXT,fixture_kind TEXT,conditions_json TEXT,cfop TEXT,icms_json TEXT,ipi_json TEXT,pis_json TEXT,cofins_json TEXT);");
$pdo->exec("INSERT INTO produtos VALUES('001','MELAO','08071900','0','','UN','5102','ativo')");
$insert=$pdo->prepare("INSERT INTO tax_rule_versions VALUES(1,100,1,'ACTIVE','PRODUCTION',?,?,?,?,?,?)");$insert->execute([json_encode(['direction'=>'EXIT','model'=>'55','ncm'=>'08071900']),'5102',json_encode(['cst'=>'00','rate'=>'18.00']),json_encode(['cst'=>'50','rate'=>'0']),json_encode(['cst'=>'01','rate'=>'1.65']),json_encode(['cst'=>'01','rate'=>'7.60'])]);
$service=new AccountingCatalogExportService();$normal=$service->export($pdo,'normal');accountingAssert(count($normal['rows'])===1,'produto exportado');accountingAssert($normal['rows'][0][0]==='001'&&$normal['rows'][0][3]==='00'&&$normal['rows'][0][8]==='1.65','composição tributária');$csv=$service->csv($normal);accountingAssert(str_starts_with($csv,"\xEF\xBB\xBFsep=;"),'CSV Excel UTF-8');foreach(AccountingCatalogExportService::TEMPLATES as$entity)accountingAssert(count($service->template($entity)['headers'])>=5,'modelo '.$entity);
echo "AccountingCatalogExportServiceTest OK\n";
