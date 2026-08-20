<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Context/TenantContext.php';

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

$context = new TenantContext(15, 7, 7);
assertSame(15, $context->getAuthenticatedUserId(), 'authenticated user id is preserved');
assertSame(7, $context->getEffectiveTenantId(), 'effective tenant id is preserved');
assertSame(7, $context->getUserTenantId(), 'user tenant id is preserved');
assertTrue($context->toArray() === [
    'authenticatedUserId' => 15,
    'effectiveTenantId' => 7,
    'userTenantId' => 7,
], 'context exposes only the approved data set');

try {
    new TenantContext(0, 7, 7);
    fwrite(STDERR, "ASSERTION FAILED: invalid authenticatedUserId should fail early\n");
    exit(1);
} catch (InvalidArgumentException) {
    // expected
}

try {
    new TenantContext(15, 0, 0);
    fwrite(STDERR, "ASSERTION FAILED: invalid tenant id should fail early\n");
    exit(1);
} catch (InvalidArgumentException) {
    // expected
}

try {
    new TenantContext(15, 7, 9);
    fwrite(STDERR, "ASSERTION FAILED: mismatched user and effective tenant should fail early\n");
    exit(1);
} catch (InvalidArgumentException) {
    // expected
}

try {
    $reflection = new ReflectionClass(TenantContext::class);
    $property = $reflection->getProperty('authenticatedUserId');
    $property->setValue($context, 99);
    fwrite(STDERR, "ASSERTION FAILED: the object should be immutable after creation\n");
    exit(1);
} catch (Error) {
    // expected
}

$properties = (new ReflectionClass(TenantContext::class))->getProperties();
$propertyNames = array_map(static fn ($property) => $property->getName(), $properties);
assertTrue(!in_array('dbName', $propertyNames, true), 'dbName must not be present in the context');
assertTrue(!in_array('connection', $propertyNames, true), 'connection must not be present in the context');
assertTrue(!in_array('session', $propertyNames, true), 'session must not be present in the context');

echo "TenantContext OK\n";
