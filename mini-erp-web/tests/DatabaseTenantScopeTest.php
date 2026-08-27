<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Database.php';

function tenantScopeAssert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "ASSERTION FAILED: {$message}\n");
        exit(1);
    }
}

$ref = new ReflectionClass(Database::class);
$prop = $ref->getProperty('tenantDbName');
$prop->setAccessible(true);

Database::setTenantDbName('mini_erp_tenant_5');
Database::withTenantDbName('mini_erp_tenant_14', function () use ($prop): void {
    tenantScopeAssert($prop->getValue() === 'mini_erp_tenant_14', 'scoped tenant override is active inside callback');
});

tenantScopeAssert($prop->getValue() === 'mini_erp_tenant_5', 'scoped tenant override does not leak outside callback');

echo "DatabaseTenantScope OK\n";
