<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Context/SelectedTenant.php';
require_once __DIR__ . '/../src/Context/AdministrativeContext.php';
require_once __DIR__ . '/../src/Contracts/UserRepositoryContract.php';
require_once __DIR__ . '/../src/Services/CreateUserForTenantRequest.php';
require_once __DIR__ . '/../src/Services/CreateUserForTenantService.php';

use MiniErp\Contracts\UserRepositoryContract;
use MiniErp\Context\AdministrativeContext;
use MiniErp\Context\SelectedTenant;
use MiniErp\Services\CreateUserForTenantRequest;
use MiniErp\Services\CreateUserForTenantService;

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

final class InMemoryUserRepository implements UserRepositoryContract
{
    private array $rows = [];

    public function emailExistsForTenant(string $email, int $tenantId): bool
    {
        foreach ($this->rows as $row) {
            if (strtolower((string) ($row['email'] ?? '')) === strtolower($email) && (int) ($row['tenant_id'] ?? 0) === $tenantId) {
                return true;
            }
        }

        return false;
    }

    public function createForTenant(int $tenantId, array $userData): array
    {
        $userData['id'] = count($this->rows) + 1;
        $userData['tenant_id'] = $tenantId;
        $this->rows[] = $userData;

        return $userData;
    }

    public function getRows(): array
    {
        return $this->rows;
    }
}

$repo = new InMemoryUserRepository();
$service = new CreateUserForTenantService($repo);
$_SESSION['tenant_id'] = 99;

$contextA = new AdministrativeContext(
    authenticatedAdminUserId: 10,
    selectedTenant: new SelectedTenant(authenticatedUserId: 10, selectedTenantId: 1, explicitlyAuthorized: true)
);

$createdA = $service->create($contextA, new CreateUserForTenantRequest(
    nome: 'Maria Silva',
    email: 'maria@tenant1.local',
    password: 'Secret123!',
    companyId: 15,
    role: 'user',
    status: 'ativo',
    cargo: 'funcionario'
));

assertSame(1, $createdA['tenant_id'], 'user created for tenant A');
assertSame(15, $createdA['company_id'], 'company_id preserved for compatibility');
assertTrue(!isset($createdA['adminUserId']), 'admin user id is not persisted as a tenant user attribute');
assertTrue(password_verify('Secret123!', (string) $createdA['senha']), 'password is stored securely');
assertSame(99, $_SESSION['tenant_id'], 'session tenant id remains unchanged for tenant A creation');

$contextB = new AdministrativeContext(
    authenticatedAdminUserId: 10,
    selectedTenant: new SelectedTenant(authenticatedUserId: 10, selectedTenantId: 5, explicitlyAuthorized: true)
);

$createdB = $service->create($contextB, new CreateUserForTenantRequest(
    nome: 'Joao Souza',
    email: 'joao@tenant5.local',
    password: 'StrongPass!2',
    companyId: 21,
    role: 'user',
    status: 'ativo'
));

assertSame(5, $createdB['tenant_id'], 'user created for tenant B');
assertSame(21, $createdB['company_id'], 'company_id is retained for tenant B');
assertSame(1, $createdA['tenant_id'], 'tenant A context remains A after tenant B creation');
assertSame(99, $_SESSION['tenant_id'], 'session tenant id remains unchanged after tenant B creation');

try {
    $service->create(null, new CreateUserForTenantRequest(
        nome: 'Erro',
        email: 'erro@example.com',
        password: 'Password123!'
    ));
    fwrite(STDERR, "ASSERTION FAILED: missing AdministrativeContext must fail\n");
    exit(1);
} catch (InvalidArgumentException) {
    // expected
}

$duplicateContext = new AdministrativeContext(
    authenticatedAdminUserId: 10,
    selectedTenant: new SelectedTenant(authenticatedUserId: 10, selectedTenantId: 1, explicitlyAuthorized: true)
);
try {
    $service->create($duplicateContext, new CreateUserForTenantRequest(
        nome: 'Duplicate',
        email: 'maria@tenant1.local',
        password: 'anotherPass!'
    ));
    fwrite(STDERR, "ASSERTION FAILED: duplicate email for tenant must fail\n");
    exit(1);
} catch (DomainException) {
    // expected
}

try {
    new CreateUserForTenantRequest(
        nome: '',
        email: 'invalid@example.com',
        password: 'Nope!'
    );
    fwrite(STDERR, "ASSERTION FAILED: invalid user data must fail\n");
    exit(1);
} catch (InvalidArgumentException) {
    // expected
}

try {
    new AdministrativeContext(
        authenticatedAdminUserId: 10,
        selectedTenant: new SelectedTenant(authenticatedUserId: 10, selectedTenantId: 7, explicitlyAuthorized: false)
    );
    fwrite(STDERR, "ASSERTION FAILED: unauthorized selected tenant must fail\n");
    exit(1);
} catch (DomainException) {
    // expected
}

$tenantActionContext = new AdministrativeContext(
    authenticatedAdminUserId: 10,
    selectedTenant: new SelectedTenant(authenticatedUserId: 10, selectedTenantId: 7, explicitlyAuthorized: true)
);
$tenantActionRow = $service->create($tenantActionContext, new CreateUserForTenantRequest(
    nome: 'Tenant Seven',
    email: 'tenant7@example.com',
    password: 'Pass123!',
    companyId: 77,
    role: 'user'
));
assertSame(7, $tenantActionRow['tenant_id'], 'target tenant is always the authorized selected tenant');
assertSame(77, $tenantActionRow['company_id'], 'compatibility company id is kept when explicitly provided');
assertSame(10, $contextA->getAuthenticatedAdminUserId(), 'admin remains authenticated admin');
assertSame(1, $contextA->getSelectedTenantId(), 'context A remains tenant A');
assertSame(5, $contextB->getSelectedTenantId(), 'context B remains tenant B');
assertSame(99, $_SESSION['tenant_id'], 'session state is not mutated by create-user action');

echo "CreateUserForTenantService OK\n";
