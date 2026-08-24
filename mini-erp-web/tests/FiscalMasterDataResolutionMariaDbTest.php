<?php
declare(strict_types=1);

if (getenv('RUN_FISCAL_CONFIG_MARIADB_TEST') !== '1') {
    echo "FiscalMasterDataResolutionMariaDb SKIPPED\n";
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

use MiniErp\Fiscal\FiscalTaxContext;
use MiniErp\Fiscal\TaxRuleResolver;
use MiniErp\Repositories\FiscalOperationRepository;
use MiniErp\Repositories\MariaDbTaxRuleRepository;

function masterAssert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$config = require __DIR__ . '/../config.php';
$dbConfig = $config['db'];
$database = 'mini_erp_tenant_990036_test_only';
$server = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbConfig['host'], $dbConfig['port']), $dbConfig['username'], $dbConfig['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$server->exec("DROP DATABASE IF EXISTS `{$database}`");
$server->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

try {
    $pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConfig['host'], $dbConfig['port'], $database), $dbConfig['username'], $dbConfig['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    foreach (['20260821_create_versioned_tax_engine.sql', '20260824_create_company_fiscal_settings.sql'] as $migration) {
        $sql = (string) file_get_contents(__DIR__ . '/../migrations/' . $migration);
        foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\R|$)/', $sql) ?: [])) as $statement) $pdo->exec($statement);
    }
    $pdo->exec("CREATE TABLE cfops(codigo CHAR(4) PRIMARY KEY, descricao VARCHAR(100), status VARCHAR(10)); INSERT INTO cfops VALUES ('1102','Entrada interna','ativo'),('2102','Entrada interestadual','ativo'),('5102','Saida interna','ativo'),('6102','Saida interestadual','ativo')");
    $tenant = 990036;
    $establishment = 1;
    $insertCfop = $pdo->prepare('INSERT INTO establishment_cfop_defaults(tenant_id,establishment_id,operation_context,cfop) VALUES(?,?,?,?)');
    foreach (['ENTRY_INTERNAL'=>'1102','ENTRY_INTERSTATE'=>'2102','EXIT_INTERNAL'=>'5102','EXIT_INTERSTATE'=>'6102'] as $context => $cfop) $insertCfop->execute([$tenant,$establishment,$context,$cfop]);
    $pdo->prepare('INSERT INTO establishment_fiscal_settings(tenant_id,establishment_id,default_cst_csosn,final_consumer_cst_csosn) VALUES(?,?,?,?)')->execute([$tenant,$establishment,'00','00']);
    $insertIcms = $pdo->prepare('INSERT INTO establishment_icms_defaults(tenant_id,establishment_id,uf,internal_rate,cst_csosn,valid_from,active) VALUES(?,?,?,?,?,?,1)');
    $insertIcms->execute([$tenant,$establishment,'PR','19.000000','00','2026-01-01']);
    $insertIcms->execute([$tenant,$establishment,'SP','18.000000','00','2026-01-01']);
    $pdo->prepare("INSERT INTO establishment_legacy_tax_defaults(tenant_id,establishment_id,pis_output_cst,pis_output_rate,pis_input_cst,pis_input_rate,cofins_output_cst,cofins_output_rate,cofins_input_cst,cofins_input_rate,ipi_applicability) VALUES(?,?,'01',1.65,'50',1.65,'01',7.6,'50',7.6,'NOT_APPLICABLE')")->execute([$tenant,$establishment]);

    $rules = new MariaDbTaxRuleRepository($pdo, $tenant);
    $resolver = new TaxRuleResolver($rules);
    $operation = new FiscalOperationRepository($pdo, $tenant);
    $makeContext = static fn(string $direction, string $destination): FiscalTaxContext => new FiscalTaxContext($tenant,$establishment,'3','PR',$destination,'PJ','9','1058','01012100','','0','',$direction,'55','NORMAL',true,false,'1',new DateTimeImmutable('2026-08-24'),'2.0000','100.00');
    foreach ([['ENTRY','PR','1102'],['ENTRY','SP','2102'],['EXIT','PR','5102'],['EXIT','SP','6102']] as [$direction,$destination,$expected]) {
        masterAssert($operation->contextualCfop($establishment,$direction,'PR',$destination)===$expected, "contextual CFOP {$direction}/{$destination}");
        masterAssert($resolver->resolve($makeContext($direction,$destination))->cfop===$expected, "configuration fallback {$direction}/{$destination}");
    }
    masterAssert($operation->contextualCfop($establishment,'EXIT','PR','')===null, 'missing destination UF must not select a CFOP');

    $specific = new MiniErp\Fiscal\FiscalTaxRule(null,$tenant,'SPECIFIC_NCM',1,1,new DateTimeImmutable('2026-01-01'),null,'TEST_ONLY','1','2026-01-01',['ncm'=>'01012100','direction'=>'EXIT','destination_state'=>'PR'],'5101',['cst'=>'00','rate'=>'12'],[],['cst'=>'01','rate'=>'1.65'],['cst'=>'01','rate'=>'7.6'],[],[],'ACTIVE','TEST_ONLY');
    $rules->addVersion($specific);
    masterAssert($resolver->resolve($makeContext('EXIT','PR'))->cfop==='5101', 'specific rule must win over configuration fallback');
    echo "FiscalMasterDataResolutionMariaDb OK four-contexts precedence missing-UF\n";
} finally {
    $server->exec("DROP DATABASE IF EXISTS `{$database}`");
}
