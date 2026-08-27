<?php
declare(strict_types=1);

if (getenv('RUN_FISCAL_MARIADB_TESTS') !== '1') {
    echo "OrderPartyRefreshMariaDb SKIPPED\n";
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use MiniErp\Fiscal\TaxRuleResolver;
use MiniErp\Repositories\FiscalOperationRepository;
use MiniErp\Repositories\MariaDbTaxRuleRepository;
use MiniErp\Services\FiscalDanfePreviewService;
use MiniErp\Services\CreateInternalFiscalDocumentService;

function partyAssert(bool $condition, string $label): void
{
    if (!$condition) throw new RuntimeException($label);
}

function partyCleanup(string $directory): void
{
    $tempPrefix = str_replace('\\', '/', sys_get_temp_dir()) . '/minierp-party-refresh-';
    $normalized = str_replace('\\', '/', $directory);
    if (!str_starts_with($normalized, $tempPrefix) || !is_dir($directory)) return;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $entry) $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    rmdir($directory);
}

$config = require __DIR__ . '/../config.php';
$db = $config['db'];
$tenantId = 14;
$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname=mini_erp_tenant_{$tenantId};charset=utf8mb4",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
);
foreach (glob(sys_get_temp_dir() . '/minierp-party-refresh-*', GLOB_ONLYDIR) ?: [] as $oldCache) partyCleanup($oldCache);
$cache = sys_get_temp_dir() . '/minierp-party-refresh-' . bin2hex(random_bytes(6));
$pdo->beginTransaction();
try {
    $customers = $pdo->query('SELECT * FROM clientes ORDER BY id LIMIT 2')->fetchAll();
    $product = $pdo->query("SELECT id,preco FROM produtos WHERE COALESCE(merchandise_origin,'')<>'' AND COALESCE(ncm,'')<>'' ORDER BY id DESC LIMIT 1")->fetch();
    if (count($customers) < 2 || !$product) throw new RuntimeException('Fixture A/B/product unavailable');
    $pdo->exec("INSERT INTO transportadoras(nome,nome_fantasia,cpf_cnpj,inscricao_estadual,email,telefone,cep,logradouro,numero,complemento,bairro,municipio,uf,cidade,status) VALUES('TRANSPORTADORA T TEST ONLY','T TEST ONLY','12345678000195','123456789','','','01001000','RUA TESTE','100','','CENTRO','SAO PAULO','SP','SAO PAULO','ativo')");
    $carrier = $pdo->query('SELECT * FROM transportadoras WHERE id=LAST_INSERT_ID()')->fetch();
    $pdo->exec("INSERT INTO motoristas(nome,cpf,cnh,categoria_cnh,vencimento_cnh,telefone,status) VALUES('MOTORISTA TEST ONLY PROMPT 066','52998224725','TEST066CNH','D','2030-12-31','','ativo')");
    $driver = $pdo->query('SELECT * FROM motoristas WHERE id=LAST_INSERT_ID()')->fetch();
    $repository = new FiscalOperationRepository($pdo, $tenantId);
    $header = ['tipo'=>'saida','establishment_id'=>5,'cliente_id'=>$customers[0]['id'],'data_venda'=>date('Y-m-d'),'operation_nature'=>'TEST_ONLY','fiscal_model'=>'55','purpose'=>'NORMAL','final_consumer'=>1,'presence_indicator'=>'1','payment_method'=>'01','freight_mode'=>'9'];
    $items = [['produto_id'=>$product['id'],'quantidade'=>'1','preco_unitario'=>$product['preco']]];
    $repository->assertOrderParties($header);
    $orderId = $repository->saveOrderWithTransport(0, $header, $items, 999);
    $service = new FiscalDanfePreviewService($pdo, $repository, new TaxRuleResolver(new MariaDbTaxRuleRepository($pdo, $tenantId)), $cache);
    $first = $service->render($orderId);

    $header['cliente_id'] = $customers[1]['id'];
    $header['transportadora_id'] = $carrier['id'];
    $header['motorista_id'] = $driver['id'];
    $header['freight_mode'] = '0';
    $header += ['vehicle_plate'=>'ABC1D23','vehicle_state'=>'SP','vehicle_rntc'=>'TEST066','volume_quantity'=>'2','volume_species'=>'CAIXA','volume_brand'=>'TEST_ONLY','volume_numbering'=>'1-2','gross_weight'=>'12,500','net_weight'=>'11,800'];
    $repository->assertOrderParties($header);
    $repository->saveOrderWithTransport($orderId, $header, $items, 999);

    $readBack = (new FiscalOperationRepository($pdo, $tenantId))->order($orderId);
    partyAssert((int)$readBack['person_id'] === (int)$customers[1]['id'], 'OrderCustomerReadBackTest');
    partyAssert((int)$readBack['carrier_id'] === (int)$carrier['id'], 'OrderCarrierReadBackTest');
    $transportReadBack=(new FiscalOperationRepository($pdo,$tenantId))->orderWithTransport($orderId);partyAssert((int)$transportReadBack['driver_id']===(int)$driver['id']&&(int)$transportReadBack['volume_quantity']===2&&(string)$transportReadBack['gross_weight']==='12.500','OrderDriverPersistenceTest / OrderVolumePersistenceTest');
    $second = (new FiscalDanfePreviewService($pdo, new FiscalOperationRepository($pdo, $tenantId), new TaxRuleResolver(new MariaDbTaxRuleRepository($pdo, $tenantId)), $cache))->render($orderId);
    partyAssert((int)$second['snapshot']['recipient']['id'] === (int)$customers[1]['id'], 'OrderCustomerSnapshotRefreshTest');
    partyAssert((int)$second['snapshot']['carrier']['id'] === (int)$carrier['id'], 'OrderCarrierSnapshotRefreshTest');
    partyAssert((int)$second['snapshot']['order']['driver_id']===(int)$driver['id']&&(int)$second['snapshot']['order']['volume_quantity']===2,'FiscalSnapshotDriverTest / volume snapshot');
    partyAssert(str_contains($second['xml'], '<dest>') && str_contains($second['xml'], htmlspecialchars((string)$customers[1]['nome'], ENT_XML1)), 'FiscalPreviewCustomerRefreshTest');
    partyAssert(str_contains($second['xml'], '<transporta>') && str_contains($second['xml'], htmlspecialchars((string)$carrier['nome'], ENT_XML1)), 'FiscalPreviewCarrierRefreshTest');
    foreach(['<veicTransp>','<placa>ABC1D23</placa>','<vol>','<qVol>2</qVol>','<esp>CAIXA</esp>','<marca>TEST_ONLY</marca>','<nVol>1-2</nVol>','<pesoL>11.800</pesoL>','<pesoB>12.500</pesoB>']as$xmlToken)partyAssert(str_contains($second['xml'],$xmlToken),'FiscalVolumeXmlTest '.$xmlToken);
    partyAssert(!str_contains($second['xml'],'MOTORISTA TEST ONLY PROMPT 066'),'FiscalDriverXmlApplicabilityTest: driver must not create non-standard NF-e tag');
    partyAssert($first['snapshot_checksum'] !== $second['snapshot_checksum'] && $second['cache'] === 'MISS', 'FiscalPreviewCombinedPartyRefreshTest / cache invalidation');
    partyAssert(str_starts_with($second['bytes'], '%PDF'), 'FiscalDanfeCustomerRefreshTest / FiscalDanfeCarrierRefreshTest');
    partyAssert($service->render($orderId)['cache'] === 'HIT', 'stable preview cache hit');
    $internal=(new CreateInternalFiscalDocumentService($repository,new TaxRuleResolver(new MariaDbTaxRuleRepository($pdo,$tenantId))))->create($orderId,hash('sha256','PROMPT066-'.$orderId),999);$persisted=$repository->document((int)$internal['id']);partyAssert((int)($persisted['transport_snapshot']['driver']['id']??0)===(int)$driver['id']&&($persisted['transport_snapshot']['volume']['gross_weight']??'')==='12.500','FiscalSnapshotDriverTest / persisted volume snapshot');

    $pdo->exec("INSERT INTO transportadoras(nome,nome_fantasia,cpf_cnpj,inscricao_estadual,email,telefone,cep,logradouro,numero,complemento,bairro,municipio,uf,cidade,status) VALUES('TRANSPORTADORA T2 TEST ONLY','T2 TEST ONLY','98765432000198','987654321','','','01001000','RUA DOIS','200','','CENTRO','SAO PAULO','SP','SAO PAULO','ativo')");
    $carrier2 = $pdo->query('SELECT * FROM transportadoras WHERE id=LAST_INSERT_ID()')->fetch();
    $header['transportadora_id'] = $carrier2['id'];
    $repository->assertOrderParties($header);
    $repository->saveOrderWithTransport($orderId, $header, $items, 999);
    $replaced = $service->render($orderId);
    partyAssert($replaced['cache'] === 'MISS' && str_contains($replaced['xml'], htmlspecialchars((string)$carrier2['nome'], ENT_XML1)), 'FiscalPreviewCarrierReplaceTest');

    $header['transportadora_id'] = '';
    $repository->assertOrderParties($header);
    $repository->saveOrderWithTransport($orderId, $header, $items, 999);
    $removed = $service->render($orderId);
    partyAssert($removed['cache'] === 'MISS' && !str_contains($removed['xml'], '<transporta>'), 'FiscalPreviewCarrierRemovalTest');
    $header['fiscal_model'] = '65';
    $repository->saveOrderWithTransport($orderId, $header, $items, 999);
    $model65 = $service->render($orderId);
    partyAssert($model65['model'] === '65' && str_starts_with($model65['bytes'], '%PDF') && !str_contains($model65['xml'], '<transporta>'), 'model 65 regression');

    try {
        $foreignCustomer = (int)$pdo->query('SELECT COALESCE(MAX(id),0)+1000000 FROM clientes')->fetchColumn();
        $repository->assertOrderParties(array_replace($header, ['cliente_id'=>$foreignCustomer]));
        throw new RuntimeException('cross-tenant/unowned customer accepted');
    } catch (RuntimeException $e) {
        partyAssert($e->getMessage() === 'ORDER_CUSTOMER_NOT_OWNED', 'IDOR customer');
    }
    echo "OrderPartyRefreshMariaDb OK (A->B, carrier add/remove, XML, cache, IDOR; rollback)\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    partyCleanup($cache);
}
