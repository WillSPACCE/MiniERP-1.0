<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Context/TenantContext.php';
require_once __DIR__ . '/../src/Infrastructure/TenantConnectionResolver.php';

use MiniErp\Context\TenantContext;
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

$tenantA = new TenantContext(authenticatedUserId: 1, effectiveTenantId: 1, userTenantId: 1);
$tenantB = new TenantContext(authenticatedUserId: 2, effectiveTenantId: 5, userTenantId: 5);

$pdoA = (new TenantConnectionResolver())->resolve($tenantA);
$pdoB = (new TenantConnectionResolver())->resolve($tenantB);

$stmtA = $pdoA->query('SELECT DATABASE() AS current_db');
$stmtB = $pdoB->query('SELECT DATABASE() AS current_db');
$databaseA = (string) ($stmtA->fetch(PDO::FETCH_ASSOC)['current_db'] ?? '');
$databaseB = (string) ($stmtB->fetch(PDO::FETCH_ASSOC)['current_db'] ?? '');

assertSame('mini_erp_tenant_1', $databaseA, 'tenant 1 resolves to the expected database');
assertSame('mini_erp_tenant_5', $databaseB, 'tenant 5 resolves to the expected database');

$pdoAAgain = (new TenantConnectionResolver())->resolve($tenantA);
$databaseAAgain = (string) ($pdoAAgain->query('SELECT DATABASE() AS current_db')->fetch(PDO::FETCH_ASSOC)['current_db'] ?? '');
assertSame('mini_erp_tenant_1', $databaseAAgain, 'tenant A keeps its own database without global state pollution');

try {
    (new TenantConnectionResolver())->resolve(new TenantContext(authenticatedUserId: 10, effectiveTenantId: 999, userTenantId: 999));
    fwrite(STDERR, "ASSERTION FAILED: missing tenant id should fail\n");
    exit(1);
} catch (DomainException) {
    // expected
}

$stubResolver = new class extends TenantConnectionResolver {
    public function __construct()
    {
    }

    protected function fetchDbNameForTenantId(int $tenantId): string
    {
        return 'bad-db-name';
    }
};

try {
    $stubResolver->resolve(new TenantContext(authenticatedUserId: 11, effectiveTenantId: 5, userTenantId: 5));
    fwrite(STDERR, "ASSERTION FAILED: invalid db name should fail\n");
    exit(1);
} catch (InvalidArgumentException) {
    // expected
}

$source = file_get_contents(__DIR__ . '/../src/Infrastructure/TenantConnectionResolver.php');
assertTrue($source !== false, 'resolver file exists');
assertTrue(strpos($source, 'Database::setTenantDbName') === false, 'legacy global write is not used');
assertTrue(strpos($source, 'initializeSchema') === false, 'schema bootstrap is not triggered');
assertTrue(strpos($source, '$_SESSION') === false, 'resolver does not read session');
assertTrue(strpos($source, '$_POST') === false, 'resolver does not read post');
assertTrue(strpos($source, '$_GET') === false, 'resolver does not read get');

echo "TenantConnectionResolver OK\n";
