<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Context/AuthenticatedPlatformAdmin.php';
require_once __DIR__ . '/../src/Contracts/PlatformAdminAuthorizerContract.php';
require_once __DIR__ . '/../src/Contracts/PlatformTenantRepositoryContract.php';
require_once __DIR__ . '/../src/Contracts/TenantDatabaseProvisionerContract.php';
require_once __DIR__ . '/../src/Authorization/ConfiguredPlatformAdminAuthorizer.php';
require_once __DIR__ . '/../src/Services/PlatformTenantDatabaseName.php';
require_once __DIR__ . '/../src/Services/TenantSchemaTemplate.php';
require_once __DIR__ . '/../src/Services/ProvisionPlatformTenantService.php';

use MiniErp\Authorization\ConfiguredPlatformAdminAuthorizer;
use MiniErp\Context\AuthenticatedPlatformAdmin;
use MiniErp\Contracts\PlatformTenantRepositoryContract;
use MiniErp\Contracts\TenantDatabaseProvisionerContract;
use MiniErp\Services\PlatformTenantDatabaseName;
use MiniErp\Services\ProvisionPlatformTenantService;
use MiniErp\Services\TenantSchemaTemplate;

function provisionAssert(bool $condition, string $message): void
{
    if (!$condition) { fwrite(STDERR, "ASSERTION FAILED: {$message}\n"); exit(1); }
}

final class ProvisioningRepositoryFake implements PlatformTenantRepositoryContract
{
    public array $rows = [];
    public array $events = [];
    public bool $rejectBegin = false;
    public bool $rejectComplete = false;
    public function slugExists(string $slug, ?int $exceptTenantId = null): bool { return false; }
    public function cnpjExists(string $cnpj, ?int $exceptTenantId = null): bool { return false; }
    public function create(array $data): array { throw new LogicException('not used'); }
    public function update(int $tenantId, array $data): array { throw new LogicException('not used'); }
    public function findById(int $tenantId): ?array { return $this->rows[$tenantId] ?? null; }
    public function beginProvisioning(int $tenantId): bool {
        $this->events[] = 'main:begin';
        if ($this->rejectBegin) return false;
        $this->rows[$tenantId]['status'] = 'provisionando'; return true;
    }
    public bool $supportsVersion = true;
    public function supportsSchemaVersion(): bool { return $this->supportsVersion; }
    public function completeProvisioning(int $tenantId, string $databaseName, string $schemaVersion): bool {
        $this->events[] = 'main:complete';
        if ($this->rejectComplete) return false;
        $this->rows[$tenantId]['status'] = 'ativa'; $this->rows[$tenantId]['db_name'] = $databaseName; $this->rows[$tenantId]['schema_version'] = $schemaVersion; return true;
    }
}

final class ProvisionerFake implements TenantDatabaseProvisionerContract
{
    public bool $exists = false;
    public bool $failCreate = false;
    public bool $failInstall = false;
    public bool $valid = true;
    public array $events = [];
    public function databaseExists(string $databaseName): bool { $this->events[] = 'db:exists'; return $this->exists; }
    public function createDatabase(string $databaseName): void { $this->events[] = 'db:create'; if ($this->failCreate) throw new RuntimeException('create'); }
    public function installSchema(string $databaseName, string $schemaVersion): void { $this->events[] = 'db:schema:' . $schemaVersion; if ($this->failInstall) throw new RuntimeException('schema'); }
    public function validateSchema(string $databaseName, string $schemaVersion): bool { $this->events[] = 'db:validate:' . $schemaVersion; return $this->valid; }
}

$actor = new AuthenticatedPlatformAdmin(20, 'platform@example.test', 'Admin');
$authorized = new ConfiguredPlatformAdminAuthorizer([20]);
$template = new TenantSchemaTemplate(__DIR__ . '/../database/tenant-template');
provisionAssert(PlatformTenantDatabaseName::fromTenantId(14) === 'mini_erp_tenant_14', 'database naming uses canonical tenant id');
try { PlatformTenantDatabaseName::fromTenantId(0); provisionAssert(false, 'invalid tenant id rejected'); } catch (InvalidArgumentException) {}

$base = ['tenant_id' => 14, 'status' => 'cadastrada', 'blocked' => 0, 'db_name' => null];
$make = static function (array $row = []) use ($base, $authorized): array {
    $repository = new ProvisioningRepositoryFake(); $repository->rows[14] = array_merge($base, $row);
    $provisioner = new ProvisionerFake();
    return [$repository, $provisioner, new ProvisionPlatformTenantService($repository, $provisioner, $authorized, new TenantSchemaTemplate(__DIR__ . '/../database/tenant-template'))];
};

[$repository, $provisioner, $service] = $make();
$_SESSION['tenant_id'] = 71; $_SESSION['current_company_id'] = 81;
$result = $service->provision($actor, 14);
provisionAssert($result['db_name'] === 'mini_erp_tenant_14', 'external db_name is impossible and ignored by API');
provisionAssert($repository->rows[14]['status'] === 'ativa', 'success activates only after validation');
provisionAssert($repository->rows[14]['db_name'] === 'mini_erp_tenant_14', 'success stores derived database name');
provisionAssert($provisioner->events === ['db:exists', 'db:create', 'db:schema:v1', 'db:validate:v1'], 'physical order is deterministic and versioned');
provisionAssert($repository->rows[14]['schema_version'] === 'v1', 'installed version is persisted only on completion');
provisionAssert($repository->events === ['main:begin', 'main:complete'], 'MAIN completes only at the end');
provisionAssert($_SESSION['tenant_id'] === 71 && $_SESSION['current_company_id'] === 81, 'ERP session remains intact');

foreach ([
    ['row' => ['status' => 'ativa'], 'exists' => false, 'label' => 'invalid status'],
    ['row' => ['blocked' => 1], 'exists' => false, 'label' => 'blocked tenant'],
    ['row' => ['db_name' => 'mini_erp_tenant_14'], 'exists' => false, 'label' => 'already provisioned'],
    ['row' => [], 'exists' => true, 'label' => 'database conflict'],
] as $case) {
    [$repository, $provisioner, $service] = $make($case['row']); $provisioner->exists = $case['exists'];
    try { $service->provision($actor, 14); provisionAssert(false, $case['label'] . ' rejected'); } catch (DomainException) {}
    provisionAssert(!in_array('main:complete', $repository->events, true), $case['label'] . ' never completes MAIN');
}

$missingRepository = new ProvisioningRepositoryFake(); $missingProvisioner = new ProvisionerFake();
try { (new ProvisionPlatformTenantService($missingRepository, $missingProvisioner, $authorized, $template))->provision($actor, 999); provisionAssert(false, 'missing tenant rejected'); } catch (DomainException) {}
try { (new ProvisionPlatformTenantService($missingRepository, $missingProvisioner, new ConfiguredPlatformAdminAuthorizer([]), $template))->provision($actor, 14); provisionAssert(false, 'unauthorized actor rejected'); } catch (DomainException) {}

foreach (['failCreate', 'failInstall'] as $failure) {
    [$repository, $provisioner, $service] = $make(); $provisioner->{$failure} = true;
    try { $service->provision($actor, 14); provisionAssert(false, $failure . ' propagated safely'); } catch (DomainException) {}
    provisionAssert(!in_array('main:complete', $repository->events, true), $failure . ' does not activate');
}
[$repository, $provisioner, $service] = $make(); $provisioner->valid = false;
try { $service->provision($actor, 14); provisionAssert(false, 'schema validation failure rejected'); } catch (DomainException) {}
provisionAssert(!in_array('main:complete', $repository->events, true), 'invalid schema does not activate');

[$repository, $provisioner, $service] = $make(); $repository->supportsVersion = false;
try { $service->provision($actor, 14); provisionAssert(false, 'missing MAIN schema_version support rejected'); } catch (DomainException) {}
provisionAssert($provisioner->events === [], 'missing version persistence fails before physical database access');

echo "ProvisionPlatformTenantService OK\n";
