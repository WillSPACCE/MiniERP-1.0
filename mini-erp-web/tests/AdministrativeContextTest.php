<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Context/SelectedTenant.php';
require_once __DIR__ . '/../src/Context/AdministrativeContext.php';
require_once __DIR__ . '/../src/Context/TenantContext.php';
require_once __DIR__ . '/../src/Context/SelectedTenantResolver.php';

use MiniErp\Context\SelectedTenant;
use MiniErp\Context\TenantContext;

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

$selectedA = new SelectedTenant(authenticatedUserId: 10, selectedTenantId: 1, explicitlyAuthorized: true);
$contextA = new \MiniErp\Context\AdministrativeContext(authenticatedAdminUserId: 10, selectedTenant: $selectedA);
assertSame(10, $contextA->getAuthenticatedAdminUserId(), 'admin user id is preserved');
assertSame(1, $contextA->getSelectedTenantId(), 'tenant 1 is preserved for context A');
assertSame(1, $contextA->getSelectedTenant()->getSelectedTenantId(), 'selected tenant is retained');
assertTrue($contextA->getSelectedTenant()->isExplicitlyAuthorized(), 'selection is authorized');

try {
    new \MiniErp\Context\AdministrativeContext(authenticatedAdminUserId: 0, selectedTenant: $selectedA);
    fwrite(STDERR, "ASSERTION FAILED: invalid admin user id must fail\n");
    exit(1);
} catch (InvalidArgumentException) {
    // expected
}

try {
    new \MiniErp\Context\AdministrativeContext(authenticatedAdminUserId: 10, selectedTenant: new SelectedTenant(authenticatedUserId: 10, selectedTenantId: 5, explicitlyAuthorized: false));
    fwrite(STDERR, "ASSERTION FAILED: unauthorized selected tenant must fail\n");
    exit(1);
} catch (DomainException|InvalidArgumentException) {
    // expected
}

try {
    $contextA->authenticatedAdminUserId = 99;
    fwrite(STDERR, "ASSERTION FAILED: AdministrativeContext must be immutable\n");
    exit(1);
} catch (Error) {
    // expected
}

$selectedB = new SelectedTenant(authenticatedUserId: 10, selectedTenantId: 5, explicitlyAuthorized: true);
$contextB = new \MiniErp\Context\AdministrativeContext(authenticatedAdminUserId: 10, selectedTenant: $selectedB);
assertSame(5, $contextB->getSelectedTenantId(), 'context B points to tenant 5');
assertSame(1, $contextA->getSelectedTenantId(), 'context A remains tenant 1 after B creation');
assertSame(1, $contextA->getSelectedTenant()->getSelectedTenantId(), 'context A selected tenant remains unchanged');
assertSame(5, $contextB->getSelectedTenant()->getSelectedTenantId(), 'context B selected tenant remains unchanged');

$userTenantOne = new TenantContext(authenticatedUserId: 25, effectiveTenantId: 1, userTenantId: 1);
$adminContextForFive = new \MiniErp\Context\AdministrativeContext(authenticatedAdminUserId: 10, selectedTenant: $selectedB);
assertSame(1, $userTenantOne->getUserTenantId(), 'common TenantContext remains tenant 1');
assertSame(1, $userTenantOne->getEffectiveTenantId(), 'common TenantContext effective tenant remains 1');
assertSame(5, $adminContextForFive->getSelectedTenantId(), 'admin context selects tenant 5 without mutating the common tenant context');

$reflection = new ReflectionClass(\MiniErp\Context\AdministrativeContext::class);
$propertyNames = array_map(static fn ($property) => $property->getName(), $reflection->getProperties());
assertTrue(!in_array('dbName', $propertyNames, true), 'AdministrativeContext does not contain dbName');
assertTrue(!in_array('pdo', $propertyNames, true), 'AdministrativeContext does not contain PDO');
assertTrue(!in_array('dsn', $propertyNames, true), 'AdministrativeContext does not contain DSN');
assertTrue(!in_array('effectiveTenantId', $propertyNames, true), 'AdministrativeContext does not create an effective tenant field');
assertTrue(!in_array('role', $propertyNames, true), 'AdministrativeContext does not carry role');
assertTrue(!in_array('isGlobalAdmin', $propertyNames, true), 'AdministrativeContext does not use isGlobalAdmin for authorization');
assertTrue(!in_array('userTenantId', $propertyNames, true), 'AdministrativeContext does not model UserTenant as natural tenant state');

$selectedPropertyNames = array_map(static fn ($property) => $property->getName(), (new ReflectionClass(SelectedTenant::class))->getProperties());
assertTrue(!in_array('dbName', $selectedPropertyNames, true), 'SelectedTenant is not a DB container');
assertTrue(!in_array('pdo', $selectedPropertyNames, true), 'SelectedTenant is not a DB container');
assertTrue(!in_array('dsn', $selectedPropertyNames, true), 'SelectedTenant is not a DB container');
assertTrue(!in_array('userTenantId', $selectedPropertyNames, true), 'SelectedTenant does not represent natural user tenant');

$selectedTenantOne = new SelectedTenant(authenticatedUserId: 10, selectedTenantId: 1, explicitlyAuthorized: true);
$tenantOneContext = new \MiniErp\Context\AdministrativeContext(authenticatedAdminUserId: 10, selectedTenant: $selectedTenantOne);
assertSame(1, $tenantOneContext->getSelectedTenantId(), 'tenant 1 is accepted only as a selected tenant, not as a fallback authority');
assertSame(10, $tenantOneContext->getAuthenticatedAdminUserId(), 'admin stays authenticated admin, not user tenant owner');

$explicitlyAuthorized = new SelectedTenant(authenticatedUserId: 10, selectedTenantId: 1, explicitlyAuthorized: true);
$withLocalAdmin = new \MiniErp\Context\AdministrativeContext(authenticatedAdminUserId: 10, selectedTenant: $explicitlyAuthorized);
assertSame(1, $withLocalAdmin->getSelectedTenantId(), 'selected tenant is still tenant 1 when explicit');

$commonTenant = new TenantContext(authenticatedUserId: 30, effectiveTenantId: 1, userTenantId: 1);
$adminContextForTenantFive = new \MiniErp\Context\AdministrativeContext(authenticatedAdminUserId: 10, selectedTenant: $selectedB);
assertSame(1, $commonTenant->getUserTenantId(), 'common tenant context is preserved when admin selects a different tenant');
assertSame(5, $adminContextForTenantFive->getSelectedTenantId(), 'admin context can point to tenant 5 without touching common context');

echo "AdministrativeContext OK\n";
