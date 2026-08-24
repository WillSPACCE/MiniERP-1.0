<?php

declare(strict_types=1);

$create = file_get_contents(__DIR__ . '/../public/plataforma/empresa-nova.php');
$edit = file_get_contents(__DIR__ . '/../public/plataforma/empresa-editar.php');
$repository = file_get_contents(__DIR__ . '/../src/Repositories/PlatformTenantRepository.php');

if ($create === false || $edit === false || $repository === false) {
    fwrite(STDERR, "ASSERTION FAILED: PLATFORM-01-T02 source files must exist\n");
    exit(1);
}

function tenantSourceAssert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "ASSERTION FAILED: {$message}\n");
        exit(1);
    }
}

foreach ([$create, $edit] as $entrypoint) {
    tenantSourceAssert(strpos($entrypoint, "'POST'") !== false, 'write entrypoint accepts POST explicitly');
    tenantSourceAssert(strpos($entrypoint, 'hash_equals') !== false, 'write entrypoint validates CSRF');
    tenantSourceAssert(strpos($entrypoint, 'requireAuthorizedPlatformContext') !== false, 'write entrypoint revalidates PlatformAdmin');
    tenantSourceAssert(strpos($entrypoint, 'TenantConnectionResolver') === false, 'write entrypoint never selects tenant database');
    tenantSourceAssert(strpos($entrypoint, 'Database::setTenantDbName') === false, 'write entrypoint never mutates tenant connection');
    tenantSourceAssert(strpos($entrypoint, "\$_SESSION['tenant_id'] =") === false, 'write entrypoint does not mutate tenant session');
    tenantSourceAssert(strpos($entrypoint, "\$_SESSION['current_company_id'] =") === false, 'write entrypoint does not mutate company session');
}

$upperRepository = strtoupper($repository);
$provisioningStart = strpos($repository, 'public function beginProvisioning');
tenantSourceAssert($provisioningStart !== false, 'T04 provisioning boundary is explicit');
$t02Repository = substr($repository, 0, $provisioningStart);
tenantSourceAssert(strpos($upperRepository, 'CREATE DATABASE') === false, 'repository never creates a database');
tenantSourceAssert(strpos($upperRepository, 'ALTER ') === false, 'repository never alters schema');
tenantSourceAssert(strpos($upperRepository, 'DROP ') === false, 'repository never drops structures');
tenantSourceAssert(strpos($t02Repository, 'db_name =') === false, 'T02 create/edit methods never write db_name');
tenantSourceAssert(strpos($t02Repository, 'tenant_id =') === false, 'T02 create/edit methods never write tenant_id');
tenantSourceAssert(strpos($repository, 'company_id') === false, 'repository does not introduce company_id');

echo "PlatformTenantEntrypoints OK\n";
