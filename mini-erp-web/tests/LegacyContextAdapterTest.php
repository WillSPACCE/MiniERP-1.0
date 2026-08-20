<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Adapters/LegacyTenantContextInput.php';
require_once __DIR__ . '/../src/Adapters/LegacyContextAdapter.php';

use MiniErp\Adapters\LegacyContextAdapter;

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

$common = LegacyContextAdapter::fromLegacyState([
    'user_id' => 20,
    'tenant_id' => 5,
    'current_company_id' => 5,
]);
assertSame(20, $common->getAuthenticatedUserId(), 'common user id is mapped');
assertSame(5, $common->getLegacyTenantId(), 'legacy tenant id is mapped');
assertSame(5, $common->getCurrentCompanyId(), 'current company id is mapped');
assertSame(null, $common->getSelectedTenantId(), 'selected tenant is null by default');

$divergent = LegacyContextAdapter::fromLegacyState([
    'user_id' => 20,
    'tenant_id' => 5,
    'current_company_id' => 3,
]);
assertSame(5, $divergent->getLegacyTenantId(), 'legacy tenant is preserved even when divergent');
assertSame(3, $divergent->getCurrentCompanyId(), 'current company id is preserved even when divergent');

$admin = LegacyContextAdapter::fromLegacyState([
    'user_id' => 99,
    'tenant_id' => 10,
    'selected_tenant_id' => 7,
    'is_global_admin' => true,
]);
assertSame(7, $admin->getSelectedTenantId(), 'selected tenant id is transported without decision');
assertSame(true, $admin->isGlobalAdmin(), 'global admin signal is preserved as input');

$optional = LegacyContextAdapter::fromLegacyState([
    'user_id' => 12,
]);
assertSame(12, $optional->getAuthenticatedUserId(), 'minimum valid input is accepted');
assertSame(null, $optional->getLegacyTenantId(), 'legacy tenant is optional');
assertSame(null, $optional->getSelectedTenantId(), 'selected tenant is optional');
assertSame(null, $optional->getSlug(), 'slug is optional');

try {
    LegacyContextAdapter::fromLegacyState(['user_id' => 0]);
    fwrite(STDERR, "ASSERTION FAILED: zero user id should fail\n");
    exit(1);
} catch (InvalidArgumentException) {
    // expected
}

$adapterSource = file_get_contents(__DIR__ . '/../src/Adapters/LegacyContextAdapter.php');
assertTrue(strpos($adapterSource, '$_SESSION') === false, 'adapter does not read session globals');
assertTrue(strpos($adapterSource, '$_POST') === false, 'adapter does not read post globals');
assertTrue(strpos($adapterSource, '$_GET') === false, 'adapter does not read get globals');
assertTrue(strpos($adapterSource, 'Database') === false, 'adapter does not know Database');
assertTrue(strpos($adapterSource, 'PDO') === false, 'adapter does not know PDO');

$reflection = new ReflectionClass(LegacyContextAdapter::class);
assertTrue($reflection->isFinal(), 'adapter is intentionally final');


echo "LegacyContextAdapter OK\n";
