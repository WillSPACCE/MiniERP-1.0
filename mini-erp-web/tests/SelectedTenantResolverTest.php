<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Adapters/LegacyTenantContextInput.php';
require_once __DIR__ . '/../src/Adapters/LegacyContextAdapter.php';
require_once __DIR__ . '/../src/Context/TenantContext.php';
require_once __DIR__ . '/../src/Context/SelectedTenant.php';
require_once __DIR__ . '/../src/Context/SelectedTenantResolver.php';

use MiniErp\Adapters\LegacyContextAdapter;
use MiniErp\Adapters\LegacyTenantContextInput;
use MiniErp\Context\SelectedTenant;
use MiniErp\Context\SelectedTenantResolver;
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

$trustedTenantIds = [5, 7, 9];
$resolved = (new SelectedTenantResolver())->resolve(42, 7, $trustedTenantIds, true);
assertSame(42, $resolved->getAuthenticatedUserId(), 'authenticated user id is preserved');
assertSame(7, $resolved->getSelectedTenantId(), 'selected tenant id is preserved');
assertTrue($resolved->isExplicitlyAuthorized(), 'selection is explicit');

$input = LegacyContextAdapter::fromLegacyState([
    'user_id' => 43,
    'selected_tenant_id' => 9,
    'tenant_id' => 3,
    'company_id' => 3,
    'current_company_id' => 3,
    'is_global_admin' => false,
]);
$fromInput = (new SelectedTenantResolver())->fromLegacyInput($input, $trustedTenantIds, true);
assertSame(43, $fromInput->getAuthenticatedUserId(), 'input user id is preserved');
assertSame(9, $fromInput->getSelectedTenantId(), 'input selected tenant id is used');

try {
    (new SelectedTenantResolver())->resolve(44, null, $trustedTenantIds, true);
    fwrite(STDERR, "ASSERTION FAILED: missing selected tenant should fail\n");
    exit(1);
} catch (DomainException|InvalidArgumentException) {
    // expected
}

try {
    (new SelectedTenantResolver())->resolve(44, 0, $trustedTenantIds, true);
    fwrite(STDERR, "ASSERTION FAILED: invalid tenant id should fail\n");
    exit(1);
} catch (DomainException|InvalidArgumentException) {
    // expected
}

try {
    (new SelectedTenantResolver())->resolve(44, 6, $trustedTenantIds, true);
    fwrite(STDERR, "ASSERTION FAILED: unregistered tenant should fail\n");
    exit(1);
} catch (DomainException|InvalidArgumentException) {
    // expected
}

try {
    (new SelectedTenantResolver())->resolve(44, 7, $trustedTenantIds, false);
    fwrite(STDERR, "ASSERTION FAILED: implicit selection without explicit authorization should fail\n");
    exit(1);
} catch (DomainException|InvalidArgumentException) {
    // expected
}

try {
    (new SelectedTenantResolver())->resolve(44, 1, [5, 7], true);
    fwrite(STDERR, "ASSERTION FAILED: tenant 1 fallback must not be accepted as implicit default\n");
    exit(1);
} catch (DomainException|InvalidArgumentException) {
    // expected
}

$userNaturalTenant = new TenantContext(authenticatedUserId: 80, effectiveTenantId: 5, userTenantId: 5);
assertSame(5, $userNaturalTenant->getUserTenantId(), 'common user tenant remains natural tenant');
assertSame(5, $userNaturalTenant->getEffectiveTenantId(), 'common tenant context remains common');
assertTrue((new SelectedTenantResolver())->resolve(80, 7, [5, 7], true)->getSelectedTenantId() === 7, 'selected tenant is separate from user tenant');

$reflectionSelected = new ReflectionClass(SelectedTenant::class);
$selectedPropertyNames = array_map(static fn ($property) => $property->getName(), $reflectionSelected->getProperties());
assertTrue(!in_array('dbName', $selectedPropertyNames, true), 'SelectedTenant does not carry dbName');
assertTrue(!in_array('dsn', $selectedPropertyNames, true), 'SelectedTenant does not carry dsn');
assertTrue(!in_array('pdo', $selectedPropertyNames, true), 'SelectedTenant does not carry pdo');

$reflectionContext = new ReflectionClass(TenantContext::class);
$contextPropertyNames = array_map(static fn ($property) => $property->getName(), $reflectionContext->getProperties());
assertTrue(!in_array('selectedTenantId', $contextPropertyNames, true), 'TenantContext remains separate and is not mutated by selection');

$userTenantA = new TenantContext(authenticatedUserId: 90, effectiveTenantId: 5, userTenantId: 5);
$selectedA = (new SelectedTenantResolver())->resolve(90, 5, [5, 9], true);
$selectedB = (new SelectedTenantResolver())->resolve(90, 9, [5, 9], true);
assertSame(5, $selectedA->getSelectedTenantId(), 'selection A stays on tenant A');
assertSame(9, $selectedB->getSelectedTenantId(), 'selection B stays on tenant B');
assertSame(5, $userTenantA->getUserTenantId(), 'user tenant A is not overwritten by selection B');

echo "SelectedTenantResolver OK\n";
