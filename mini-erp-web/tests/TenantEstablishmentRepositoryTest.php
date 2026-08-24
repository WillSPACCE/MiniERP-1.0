<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Contracts/EstablishmentRepositoryContract.php';
require_once __DIR__ . '/../src/Repositories/TenantEstablishmentRepository.php';

use MiniErp\Repositories\TenantEstablishmentRepository;

function tenantEstablishmentAssert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "ASSERTION FAILED: {$message}\n");
        exit(1);
    }
}

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE establishments (id INTEGER PRIMARY KEY, tenant_id INTEGER NOT NULL, is_primary INTEGER NOT NULL DEFAULT 0, tax_id TEXT, status TEXT);');
$pdo->exec("INSERT INTO establishments (id, tenant_id, is_primary, tax_id, status) VALUES (1, 42, 0, '123', 'ativo');");
$pdo->exec("INSERT INTO establishments (id, tenant_id, is_primary, tax_id, status) VALUES (2, 42, 0, '456', 'ativo');");

$repo = new TenantEstablishmentRepository($pdo);
$establishment = $repo->findPrimaryForTenant(42);

tenantEstablishmentAssert($establishment !== null, 'should fall back to any establishment when no primary row exists');
tenantEstablishmentAssert((int) $establishment['id'] === 1, 'should prefer the first valid establishment in tenant scope');

echo "TenantEstablishmentRepository fallback OK\n";
