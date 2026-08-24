<?php

declare(strict_types=1);

if (getenv('RUN_FISCAL_MARIADB_TESTS') !== '1') {
    echo "EstablishmentMariaDbIntegration SKIPPED (set RUN_FISCAL_MARIADB_TESTS=1)\n";
    exit(0);
}

require_once __DIR__ . '/../src/Contracts/EstablishmentRepositoryContract.php';
require_once __DIR__ . '/../src/Repositories/TenantEstablishmentRepository.php';
require_once __DIR__ . '/../src/Services/EstablishmentData.php';
require_once __DIR__ . '/../src/Services/EstablishmentService.php';
require_once __DIR__ . '/../src/Services/FiscalReadiness.php';

use MiniErp\Repositories\TenantEstablishmentRepository;
use MiniErp\Services\EstablishmentData;
use MiniErp\Services\EstablishmentService;
use MiniErp\Services\FiscalReadiness;

$config = require __DIR__ . '/../config.php';
$db = $config['db'];
$database = getenv('FISCAL_TEST_DATABASE') ?: '';
$tenantId = (int) (getenv('FISCAL_TEST_TENANT_ID') ?: 0);
if ($database !== 'mini_erp_tenant_14' || $tenantId !== 14) {
    fwrite(STDERR, "Refusing test: explicitly set FISCAL_TEST_DATABASE=mini_erp_tenant_14 and FISCAL_TEST_TENANT_ID=14.\n"); exit(1);
}
$pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $database), $db['username'], $db['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$assert = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
$fixture = ['tax_id'=>'TESTFISCAL1A01','legal_name'=>'FISCAL-01A FIXTURE - ROLLBACK','trade_name'=>'Fixture create','state_registration'=>'ISENTO-TESTE','st_registration'=>'','municipal_registration'=>'','cnae'=>'6201501','tax_regime_code'=>'3','street'=>'Rua Fixture','number'=>'101A','complement'=>'Rollback','district'=>'Teste','city_ibge_code'=>'3550308','city_name'=>'São Paulo','state'=>'SP','postal_code'=>'01001000','country_code'=>'1058','country_name'=>'BRASIL','phone'=>'1100000000','email'=>'fiscal01a@example.invalid','status'=>'ativo'];
$pdo->beginTransaction();
try {
    $service = new EstablishmentService(new TenantEstablishmentRepository($pdo));
    $created = $service->save($tenantId, new EstablishmentData($fixture));
    foreach ((new EstablishmentData($fixture))->toArray() as $field => $expected) $assert((string) $created[$field] === (string) $expected, "create round-trip diverged: {$field}");
    $updated = $service->save($tenantId, new EstablishmentData(array_merge($fixture, ['trade_name'=>'Fixture updated','tax_regime_code'=>'4','phone'=>'11999999999'])));
    $assert($updated['trade_name'] === 'Fixture updated' && $updated['tax_regime_code'] === '4', 'update/CRT round-trip failed');
    $assert($updated['city_ibge_code'] === '3550308' && $updated['fiscal_readiness'] === 'INCOMPLETE', 'IBGE/readiness round-trip failed');
    $assert($service->find(15) === null, 'tenant B read tenant A fixture');
    $assert((new FiscalReadiness())->evaluate($updated)['status'] === 'INCOMPLETE', 'readiness was promoted');
    echo "EstablishmentMariaDbIntegration OK (transaction rollback)\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
