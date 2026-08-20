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

function currentDatabase(
    \PDO $pdo
): string {
    $row = $pdo->query('SELECT DATABASE() AS current_db')->fetch(\PDO::FETCH_ASSOC);
    return (string) ($row['current_db'] ?? '');
}

$tenantAId = 1;
$tenantADBName = 'mini_erp_tenant_1';
$tenantBId = 5;
$tenantBDBName = 'mini_erp_tenant_5';

// Scenario 1: selectedTenantId cross-tenant must be rejected
$selectedMismatch = LegacyContextAdapter::fromLegacyState([
    'user_id' => 301,
    'tenant_id' => $tenantAId,
    'company_id' => $tenantAId,
    'current_company_id' => $tenantAId,
    'selected_tenant_id' => $tenantBId,
    'is_global_admin' => false,
]);

try {
    (new TenantContextResolver())->resolve($selectedMismatch);
    fwrite(STDERR, "ASSERTION FAILED: selectedTenantId cross-tenant should be rejected\n");
    exit(1);
} catch (\DomainException $exception) {
    echo 'Scenario 1 selectedTenantId: ' . $exception->getMessage() . PHP_EOL;
}

// Scenario 2: tenant/company conflict must be rejected before infrastructure
$tenantCompanyConflict = LegacyContextAdapter::fromLegacyState([
    'user_id' => 302,
    'tenant_id' => $tenantAId,
    'company_id' => $tenantBId,
    'current_company_id' => $tenantAId,
    'selected_tenant_id' => null,
    'is_global_admin' => false,
]);

try {
    (new TenantContextResolver())->resolve($tenantCompanyConflict);
    fwrite(STDERR, "ASSERTION FAILED: tenant/company conflict should be rejected\n");
    exit(1);
} catch (\DomainException $exception) {
    echo 'Scenario 2 tenant/company conflict: ' . $exception->getMessage() . PHP_EOL;
}

// Scenario 3: currentCompanyId mismatch must be rejected
$currentCompanyMismatch = LegacyContextAdapter::fromLegacyState([
    'user_id' => 303,
    'tenant_id' => $tenantAId,
    'company_id' => $tenantAId,
    'current_company_id' => $tenantBId,
    'selected_tenant_id' => null,
    'is_global_admin' => false,
]);

try {
    (new TenantContextResolver())->resolve($currentCompanyMismatch);
    fwrite(STDERR, "ASSERTION FAILED: currentCompanyId mismatch should be rejected\n");
    exit(1);
} catch (\DomainException $exception) {
    echo 'Scenario 3 currentCompanyId mismatch: ' . $exception->getMessage() . PHP_EOL;
}

// Scenario 4: slug alone cannot pick a tenant
$slugOnly = LegacyContextAdapter::fromLegacyState([
    'user_id' => 304,
    'slug' => 'tenant-5',
    'selected_tenant_id' => null,
    'is_global_admin' => false,
]);

try {
    (new TenantContextResolver())->resolve($slugOnly);
    fwrite(STDERR, "ASSERTION FAILED: slug alone should fail without a tenant source\n");
    exit(1);
} catch (\DomainException $exception) {
    echo 'Scenario 4 slug alone: ' . $exception->getMessage() . PHP_EOL;
}

// Scenario 5: external db_name is not part of the public API
$contextProps = array_map(static fn ($property) => $property->getName(), (new ReflectionClass(TenantContext::class))->getProperties());
$inputProps = array_map(static fn ($property) => $property->getName(), (new ReflectionClass(LegacyTenantContextInput::class))->getProperties());
assertTrue(!in_array('dbName', $contextProps, true), 'TenantContext does not expose dbName');
assertTrue(!in_array('dbName', $inputProps, true), 'LegacyTenantContextInput does not expose dbName');
assertTrue(!in_array('dsn', $contextProps, true), 'TenantContext does not expose dsn');
assertTrue(!in_array('pdo', $inputProps, true), 'LegacyTenantContextInput does not expose pdo');
assertTrue((new ReflectionMethod(TenantConnectionResolver::class, 'resolve'))->getParameters()[0]->getType() !== null && (new ReflectionMethod(TenantConnectionResolver::class, 'resolve'))->getParameters()[0]->getType()->getName() === TenantContext::class, 'Connection resolver is driven by TenantContext');
echo 'Scenario 5 external db_name: resolution is driven by TenantContext only.' . PHP_EOL;

// Scenario 6: tenant does not exist must fail without fallback
try {
    (new TenantConnectionResolver())->resolve(new TenantContext(authenticatedUserId: 305, effectiveTenantId: 99999, userTenantId: 99999));
    fwrite(STDERR, "ASSERTION FAILED: non-existent tenant should fail explicitly\n");
    exit(1);
} catch (\DomainException $exception) {
    echo 'Scenario 6 missing tenant: ' . $exception->getMessage() . PHP_EOL;
}

// Scenario 7: fallback to tenant 1 is not allowed automatically
$missingTenantState = LegacyContextAdapter::fromLegacyState([
    'user_id' => 306,
    'is_global_admin' => false,
]);

try {
    (new TenantContextResolver())->resolve($missingTenantState);
    fwrite(STDERR, "ASSERTION FAILED: missing tenant context should fail instead of falling back to tenant 1\n");
    exit(1);
} catch (\DomainException $exception) {
    echo 'Scenario 7 fallback tenant 1: ' . $exception->getMessage() . PHP_EOL;
}

// Scenario 8: admin bypass is rejected
$adminBypass = LegacyContextAdapter::fromLegacyState([
    'user_id' => 307,
    'tenant_id' => $tenantAId,
    'company_id' => $tenantAId,
    'current_company_id' => $tenantAId,
    'selected_tenant_id' => $tenantBId,
    'is_global_admin' => true,
]);

try {
    (new TenantContextResolver())->resolve($adminBypass);
    fwrite(STDERR, "ASSERTION FAILED: admin global should not bypass tenant enforcement\n");
    exit(1);
} catch (\DomainException $exception) {
    echo 'Scenario 8 admin global bypass: ' . $exception->getMessage() . PHP_EOL;
}

// Scenario 9: A -> B -> A sequence must preserve isolation
$contextA1 = (new TenantContextResolver())->resolve(LegacyContextAdapter::fromLegacyState([
    'user_id' => 308,
    'tenant_id' => $tenantAId,
    'company_id' => $tenantAId,
    'current_company_id' => $tenantAId,
    'selected_tenant_id' => null,
    'is_global_admin' => false,
]));
$connectionA1 = (new TenantConnectionResolver())->resolve($contextA1);
assertSame($tenantAId, $contextA1->getEffectiveTenantId(), 'A1 tenant is A');
assertSame($tenantADBName, currentDatabase($connectionA1), 'A1 database is tenant A');

$contextB = (new TenantContextResolver())->resolve(LegacyContextAdapter::fromLegacyState([
    'user_id' => 309,
    'tenant_id' => $tenantBId,
    'company_id' => $tenantBId,
    'current_company_id' => $tenantBId,
    'selected_tenant_id' => null,
    'is_global_admin' => false,
]));
$connectionB = (new TenantConnectionResolver())->resolve($contextB);
assertSame($tenantBId, $contextB->getEffectiveTenantId(), 'B tenant is B');
assertSame($tenantBDBName, currentDatabase($connectionB), 'B database is tenant B');

$contextA2 = (new TenantContextResolver())->resolve(LegacyContextAdapter::fromLegacyState([
    'user_id' => 310,
    'tenant_id' => $tenantAId,
    'company_id' => $tenantAId,
    'current_company_id' => $tenantAId,
    'selected_tenant_id' => null,
    'is_global_admin' => false,
]));
$connectionA2 = (new TenantConnectionResolver())->resolve($contextA2);
assertSame($tenantAId, $contextA2->getEffectiveTenantId(), 'A2 tenant is A');
assertSame($tenantADBName, currentDatabase($connectionA1), 'A1 stays at tenant A after B');
assertSame($tenantADBName, currentDatabase($connectionA2), 'A2 resolves to tenant A');
assertSame($tenantBDBName, currentDatabase($connectionB), 'B stays at tenant B');

echo 'Scenario 9 A->B->A: A1=' . currentDatabase($connectionA1) . '; B=' . currentDatabase($connectionB) . '; A2=' . currentDatabase($connectionA2) . PHP_EOL;

// Scenario 10: TenantContext must remain immutable
try {
    $reflection = new ReflectionClass(TenantContext::class);
    $property = $reflection->getProperty('effectiveTenantId');
    $property->setValue($contextA1, 999);
    fwrite(STDERR, "ASSERTION FAILED: TenantContext must be immutable after construction\n");
    exit(1);
} catch (\Error $exception) {
    echo 'Scenario 10 immutability: ' . $exception->getMessage() . PHP_EOL;
}

// Scenario 11: input/infrastructure boundaries must remain clean
$contextPropertyNames = array_map(static fn ($property) => $property->getName(), (new ReflectionClass(TenantContext::class))->getProperties());
$inputPropertyNames = array_map(static fn ($property) => $property->getName(), (new ReflectionClass(LegacyTenantContextInput::class))->getProperties());
assertTrue(!in_array('dbName', $contextPropertyNames, true), 'context cannot carry dbName');
assertTrue(!in_array('dsn', $contextPropertyNames, true), 'context cannot carry dsn');
assertTrue(!in_array('pdo', $contextPropertyNames, true), 'context cannot carry pdo');
assertTrue(!in_array('database', $inputPropertyNames, true), 'input cannot carry database');
assertTrue(!in_array('password', $inputPropertyNames, true), 'input cannot carry credentials');
assertTrue(!in_array('dbName', $inputPropertyNames, true), 'input cannot carry dbName');
echo 'Scenario 11 input boundaries: no db_name or infrastructure members are allowed.' . PHP_EOL;

// Scenario 12: valid A/B chain still works after negative tests
$validA = (new TenantContextResolver())->resolve(LegacyContextAdapter::fromLegacyState([
    'user_id' => 311,
    'tenant_id' => $tenantAId,
    'company_id' => $tenantAId,
    'current_company_id' => $tenantAId,
    'selected_tenant_id' => null,
    'is_global_admin' => false,
]));
$validB = (new TenantContextResolver())->resolve(LegacyContextAdapter::fromLegacyState([
    'user_id' => 312,
    'tenant_id' => $tenantBId,
    'company_id' => $tenantBId,
    'current_company_id' => $tenantBId,
    'selected_tenant_id' => null,
    'is_global_admin' => false,
]));
$validAConnection = (new TenantConnectionResolver())->resolve($validA);
$validBConnection = (new TenantConnectionResolver())->resolve($validB);
assertSame($tenantAId, $validA->getEffectiveTenantId(), 'valid A is still resolved');
assertSame($tenantADBName, currentDatabase($validAConnection), 'valid A still points to tenant A');
assertSame($tenantBId, $validB->getEffectiveTenantId(), 'valid B is still resolved');
assertSame($tenantBDBName, currentDatabase($validBConnection), 'valid B still points to tenant B');
echo 'Scenario 12 valid chain: A=' . currentDatabase($validAConnection) . '; B=' . currentDatabase($validBConnection) . PHP_EOL;

echo "TenantIsolation OK\n";
