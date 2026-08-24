<?php

declare(strict_types=1);

$page = file_get_contents(__DIR__ . '/../public/plataforma/empresa-provisionar.php');
$service = file_get_contents(__DIR__ . '/../src/Services/ProvisionPlatformTenantService.php');
$infra = file_get_contents(__DIR__ . '/../src/Infrastructure/MariaDbTenantProvisioner.php');
$schema = file_get_contents(__DIR__ . '/../database/tenant-template/v1/schema.sql');
if ($page === false || $service === false || $infra === false || $schema === false) { fwrite(STDERR, "ASSERTION FAILED: T04 files missing\n"); exit(1); }
function provisioningSourceAssert(bool $condition, string $message): void { if (!$condition) { fwrite(STDERR, "ASSERTION FAILED: {$message}\n"); exit(1); } }

provisioningSourceAssert(strpos($page, "'POST'") !== false, 'execution is guarded by POST');
provisioningSourceAssert(strpos($page, 'hash_equals') !== false && strpos($page, 'csrf_token') !== false, 'POST requires CSRF');
provisioningSourceAssert(strpos($page, 'requireAuthorizedPlatformContext') !== false, 'PlatformAdmin is revalidated');
provisioningSourceAssert(strpos($page, "\$_GET['db_name']") === false && strpos($page, "\$_POST['db_name']") === false, 'db_name never comes from UI');
provisioningSourceAssert(strpos($page, "\$_GET['schema_version']") === false && strpos($page, "\$_POST['schema_version']") === false, 'schema version never comes from UI');
provisioningSourceAssert(strpos($service, 'PlatformTenantDatabaseName::fromTenantId') !== false, 'service derives database name');
provisioningSourceAssert(strpos($service, 'currentVersion()') !== false, 'service resolves current template version in backend');
provisioningSourceAssert(strpos(strtoupper($infra), 'CREATE DATABASE IF NOT EXISTS') === false, 'database conflict is never silently adopted');
provisioningSourceAssert(strpos(strtoupper($infra), 'DROP DATABASE') === false, 'no automatic destructive compensation');
provisioningSourceAssert(strpos($infra, 'seeds.sql') === false, 'provisioner never loads seeds');
provisioningSourceAssert(strpos(strtoupper($schema), 'INSERT INTO') === false, 'schema source contains no business data');
provisioningSourceAssert(strpos(strtoupper($schema), 'CREATE TABLE TENANTS') === false, 'template excludes control-plane tenants registry');
provisioningSourceAssert(strpos(strtoupper($schema), 'CREATE TABLE USUARIOS') === false, 'template excludes canonical authentication table');
provisioningSourceAssert(strpos($page . $service . $infra, 'CreateUserForTenant') === false, 'T04 creates no user');
provisioningSourceAssert(strpos($page . $service . $infra, "\$_SESSION['tenant_id'] =") === false, 'ERP tenant session is not changed');
provisioningSourceAssert(strpos($page . $service . $infra, 'TenantConnectionResolver') === false, 'provisioning does not enter tenant ERP context');

echo "PlatformProvisioningEntrypoint OK\n";
