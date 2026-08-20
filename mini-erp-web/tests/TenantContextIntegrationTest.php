<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Adapters/LegacyContextAdapter.php';
require_once __DIR__ . '/../src/Adapters/LegacyTenantContextInput.php';
require_once __DIR__ . '/../src/Context/TenantContext.php';
require_once __DIR__ . '/../src/Context/TenantContextResolver.php';
require_once __DIR__ . '/../src/Infrastructure/TenantConnectionResolver.php';

use MiniErp\Adapters\LegacyContextAdapter;
use MiniErp\Adapters\LegacyTenantContextInput;
use MiniErp\Context\TenantContext;
use MiniErp\Context\TenantContextResolver;
use MiniErp\Infrastructure\TenantConnectionResolver;

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "ASSERTION FAILED: {$message}\n");
        exit(1);
    }
}

function assertSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "ASSERTION FAILED: {$message} (expected=" . var_export($expected, true) . ", actual=" . var_export($actual, true) . ")\n");
        exit(1);
    }
}

function currentDatabase(PDO $pdo): string
{
    $row = $pdo->query('SELECT DATABASE() AS current_db')->fetch(PDO::FETCH_ASSOC);
    return (string) ($row['current_db'] ?? '');
}

$tenantAId = 1;
$tenantADBName = 'mini_erp_tenant_1';
$tenantBId = 5;
$tenantBDBName = 'mini_erp_tenant_5';

$legacyStateA = [
    'user_id' => 201,
    'tenant_id' => $tenantAId,
    'company_id' => $tenantAId,
    'current_company_id' => $tenantAId,
    'selected_tenant_id' => null,
    'is_global_admin' => false,
];

$legacyStateB = [
    'user_id' => 202,
    'tenant_id' => $tenantBId,
    'company_id' => $tenantBId,
    'current_company_id' => $tenantBId,
    'selected_tenant_id' => null,
    'is_global_admin' => false,
];

$inputA = LegacyContextAdapter::fromLegacyState($legacyStateA);
$contextA = (new TenantContextResolver())->resolve($inputA);
assertSame($tenantAId, $contextA->getEffectiveTenantId(), 'Tenant A resolves to tenant A');

$connectionA = (new TenantConnectionResolver())->resolve($contextA);
$currentDbA = currentDatabase($connectionA);
assertSame($tenantADBName, $currentDbA, 'Tenant A resolves to the registered tenant A database');

echo "Tenant A: context=" . $contextA->getEffectiveTenantId() . "; db=" . $currentDbA . PHP_EOL;

$inputB = LegacyContextAdapter::fromLegacyState($legacyStateB);
$contextB = (new TenantContextResolver())->resolve($inputB);
assertSame($tenantBId, $contextB->getEffectiveTenantId(), 'Tenant B resolves to tenant B');

$connectionB = (new TenantConnectionResolver())->resolve($contextB);
$currentDbB = currentDatabase($connectionB);
assertSame($tenantBDBName, $currentDbB, 'Tenant B resolves to the registered tenant B database');

echo "Tenant B: context=" . $contextB->getEffectiveTenantId() . "; db=" . $currentDbB . PHP_EOL;

$currentDbAAfterB = currentDatabase($connectionA);
$currentDbBAfterA = currentDatabase($connectionB);
assertSame($tenantADBName, $currentDbAAfterB, 'Connection A remains bound to tenant A after resolving tenant B');
assertSame($tenantBDBName, $currentDbBAfterA, 'Connection B remains bound to tenant B after resolving tenant A');

echo "Cross-check: connection A=" . $currentDbAAfterB . "; connection B=" . $currentDbBAfterA . PHP_EOL;

try {
    (new TenantConnectionResolver())->resolve(new TenantContext(authenticatedUserId: 999, effectiveTenantId: 99999, userTenantId: 99999));
    fwrite(STDERR, "ASSERTION FAILED: non-existent tenant must fail explicitly without fallback\n");
    exit(1);
} catch (DomainException $exception) {
    echo 'Missing tenant: ' . $exception->getMessage() . PHP_EOL;
}

$divergentContext = LegacyContextAdapter::fromLegacyState([
    'user_id' => 203,
    'tenant_id' => $tenantAId,
    'company_id' => $tenantAId,
    'current_company_id' => $tenantBId,
    'selected_tenant_id' => null,
    'is_global_admin' => false,
]);

try {
    (new TenantContextResolver())->resolve($divergentContext);
    fwrite(STDERR, "ASSERTION FAILED: divergent currentCompanyId must fail before connection\n");
    exit(1);
} catch (DomainException $exception) {
    echo 'Divergent context: ' . $exception->getMessage() . PHP_EOL;
}

$selectedMismatch = LegacyContextAdapter::fromLegacyState([
    'user_id' => 204,
    'tenant_id' => $tenantAId,
    'company_id' => $tenantAId,
    'current_company_id' => $tenantAId,
    'selected_tenant_id' => $tenantBId,
    'is_global_admin' => false,
]);

try {
    (new TenantContextResolver())->resolve($selectedMismatch);
    fwrite(STDERR, "ASSERTION FAILED: selectedTenantId mismatch must fail before connection\n");
    exit(1);
} catch (DomainException $exception) {
    echo 'Selected tenant mismatch: ' . $exception->getMessage() . PHP_EOL;
}

$connectionMethod = new ReflectionMethod(TenantConnectionResolver::class, 'resolve');
$parameterType = $connectionMethod->getParameters()[0]->getType();
assertTrue($parameterType !== null && $parameterType->getName() === TenantContext::class, 'TenantConnectionResolver accepts only TenantContext and not db_name');

$adapterMethod = new ReflectionMethod(LegacyContextAdapter::class, 'fromLegacyState');
assertTrue($adapterMethod->getNumberOfParameters() === 1, 'LegacyContextAdapter does not expose a db_name argument');

$tenantContextProperties = array_map(static fn ($property) => $property->getName(), (new ReflectionClass(TenantContext::class))->getProperties());
$inputProperties = array_map(static fn ($property) => $property->getName(), (new ReflectionClass(LegacyTenantContextInput::class))->getProperties());
assertTrue(!in_array('dbName', $tenantContextProperties, true), 'TenantContext does not expose dbName');
assertTrue(!in_array('dbName', $inputProperties, true), 'LegacyTenantContextInput does not expose dbName');

echo 'No db_name API: resolution is driven by TenantContext, not an external database name.' . PHP_EOL;

echo 'Used tenants: A=' . $tenantAId . '/' . $tenantADBName . '; B=' . $tenantBId . '/' . $tenantBDBName . PHP_EOL;

echo "TenantContextIntegration OK\n";
