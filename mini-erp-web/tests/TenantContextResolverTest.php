<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Adapters/LegacyTenantContextInput.php';
require_once __DIR__ . '/../src/Adapters/LegacyContextAdapter.php';
require_once __DIR__ . '/../src/Context/TenantContext.php';
require_once __DIR__ . '/../src/Context/TenantContextResolver.php';

use MiniErp\Adapters\LegacyTenantContextInput;
use MiniErp\Context\TenantContextResolver;

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

$resolved = (new TenantContextResolver())->resolve(new LegacyTenantContextInput(
    authenticatedUserId: 20,
    legacyTenantId: 5,
    legacyCompanyId: 5,
    currentCompanyId: 5,
    selectedTenantId: null,
    slug: null,
    isGlobalAdmin: false
));
assertSame(20, $resolved->getAuthenticatedUserId(), 'common user is preserved');
assertSame(5, $resolved->getUserTenantId(), 'common user tenant is resolved to legacy tenant');
assertSame(5, $resolved->getEffectiveTenantId(), 'effective tenant matches user tenant');

$compat = (new TenantContextResolver())->resolve(new LegacyTenantContextInput(
    authenticatedUserId: 21,
    legacyTenantId: null,
    legacyCompanyId: 5,
    currentCompanyId: null,
    selectedTenantId: null,
    slug: null,
    isGlobalAdmin: false
));
assertSame(5, $compat->getUserTenantId(), 'company compatibility produces tenant');
assertSame(5, $compat->getEffectiveTenantId(), 'company compatibility resolves effective tenant');

try {
    (new TenantContextResolver())->resolve(new LegacyTenantContextInput(
        authenticatedUserId: 22,
        legacyTenantId: 5,
        legacyCompanyId: 7,
        currentCompanyId: null,
        selectedTenantId: null,
        slug: null,
        isGlobalAdmin: false
    ));
    fwrite(STDERR, "ASSERTION FAILED: conflicting tenant and company should fail\n");
    exit(1);
} catch (DomainException|InvalidArgumentException) {
    // expected
}

try {
    (new TenantContextResolver())->resolve(new LegacyTenantContextInput(
        authenticatedUserId: 23,
        legacyTenantId: 5,
        legacyCompanyId: null,
        currentCompanyId: 7,
        selectedTenantId: null,
        slug: null,
        isGlobalAdmin: false
    ));
    fwrite(STDERR, "ASSERTION FAILED: current company divergence should fail\n");
    exit(1);
} catch (DomainException|InvalidArgumentException) {
    // expected
}

try {
    (new TenantContextResolver())->resolve(new LegacyTenantContextInput(
        authenticatedUserId: 24,
        legacyTenantId: 5,
        legacyCompanyId: null,
        currentCompanyId: 5,
        selectedTenantId: 7,
        slug: null,
        isGlobalAdmin: false
    ));
    fwrite(STDERR, "ASSERTION FAILED: selected tenant mismatch should fail for common user\n");
    exit(1);
} catch (DomainException|InvalidArgumentException) {
    // expected
}

try {
    (new TenantContextResolver())->resolve(new LegacyTenantContextInput(
        authenticatedUserId: 25,
        legacyTenantId: null,
        legacyCompanyId: null,
        currentCompanyId: null,
        selectedTenantId: null,
        slug: 'empresa-x',
        isGlobalAdmin: false
    ));
    fwrite(STDERR, "ASSERTION FAILED: slug alone should fail\n");
    exit(1);
} catch (DomainException|InvalidArgumentException) {
    // expected
}

try {
    (new TenantContextResolver())->resolve(new LegacyTenantContextInput(
        authenticatedUserId: 26,
        legacyTenantId: 5,
        legacyCompanyId: null,
        currentCompanyId: 5,
        selectedTenantId: 7,
        slug: null,
        isGlobalAdmin: true
    ));
    fwrite(STDERR, "ASSERTION FAILED: global admin without authorization should fail\n");
    exit(1);
} catch (DomainException|InvalidArgumentException) {
    // expected
}

$source = file_get_contents(__DIR__ . '/../src/Context/TenantContextResolver.php');
assertTrue($source !== false, 'resolver file exists');
assertTrue(strpos($source, '$_SESSION') === false, 'resolver does not read session');
assertTrue(strpos($source, 'PDO') === false, 'resolver does not know PDO');
assertTrue(strpos($source, 'Database') === false, 'resolver does not know Database');
assertTrue(strpos($source, 'Repository') === false, 'resolver does not know Repository');

echo "TenantContextResolver OK\n";
