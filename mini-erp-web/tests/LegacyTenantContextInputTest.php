<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Adapters/LegacyTenantContextInput.php';

use MiniErp\Adapters\LegacyTenantContextInput;

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

$input = new LegacyTenantContextInput(
    authenticatedUserId: 15,
    legacyTenantId: 7,
    legacyCompanyId: 11,
    currentCompanyId: 11,
    selectedTenantId: 7,
    slug: 'mercado-silva',
    isGlobalAdmin: false
);

assertSame(15, $input->getAuthenticatedUserId(), 'authenticated user id is preserved');
assertSame(7, $input->getLegacyTenantId(), 'legacy tenant id is preserved');
assertSame(11, $input->getLegacyCompanyId(), 'legacy company id is preserved');
assertSame(11, $input->getCurrentCompanyId(), 'current company id is preserved');
assertSame(7, $input->getSelectedTenantId(), 'selected tenant id is preserved');
assertSame('mercado-silva', $input->getSlug(), 'slug is preserved');
assertSame(false, $input->isGlobalAdmin(), 'global admin flag is preserved');

$inputWithOptional = new LegacyTenantContextInput(authenticatedUserId: 22);
assertSame(22, $inputWithOptional->getAuthenticatedUserId(), 'minimum valid input is accepted');
assertSame(null, $inputWithOptional->getLegacyTenantId(), 'optional tenant id is nullable');
assertSame(null, $inputWithOptional->getSelectedTenantId(), 'optional selected tenant is nullable');

try {
    new LegacyTenantContextInput(authenticatedUserId: 0);
    fwrite(STDERR, "ASSERTION FAILED: authenticatedUserId 0 should be rejected\n");
    exit(1);
} catch (InvalidArgumentException) {
    // expected
}

try {
    new LegacyTenantContextInput(authenticatedUserId: 10, legacyTenantId: 0);
    fwrite(STDERR, "ASSERTION FAILED: legacyTenantId 0 should be rejected\n");
    exit(1);
} catch (InvalidArgumentException) {
    // expected
}

try {
    new LegacyTenantContextInput(authenticatedUserId: 10, slug: '');
    fwrite(STDERR, "ASSERTION FAILED: blank slug should be rejected\n");
    exit(1);
} catch (InvalidArgumentException) {
    // expected
}

$reflection = new ReflectionClass(LegacyTenantContextInput::class);
$properties = array_map(static fn ($property) => $property->getName(), $reflection->getProperties());
assertTrue(!in_array('effectiveTenantId', $properties, true), 'effectiveTenantId must not exist');
assertTrue(!in_array('dbName', $properties, true), 'dbName must not exist');
assertTrue(!in_array('pdo', $properties, true), 'pdo must not exist');
assertTrue(!in_array('database', $properties, true), 'database must not exist');
assertTrue(!in_array('session', $properties, true), 'session must not exist');

try {
    $reflection->getProperty('authenticatedUserId')->setValue($input, 99);
    fwrite(STDERR, "ASSERTION FAILED: input should be immutable after construction\n");
    exit(1);
} catch (Error) {
    // expected
}

echo "LegacyTenantContextInput OK\n";
