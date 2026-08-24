<?php

declare(strict_types=1);

namespace MiniErp\Services;

use DomainException;
use MiniErp\Adapters\LegacyTenantContextInput;
use MiniErp\Context\AuthenticatedTenantUser;
use MiniErp\Context\TenantContextResolver;
use MiniErp\Contracts\ErpAuthenticationReaderContract;

final class ErpAuthenticationService
{
    public function __construct(
        private ErpAuthenticationReaderContract $reader,
        private TenantContextResolver $contextResolver
    ) {}

    public function authenticate(string $email, string $password, string $slug): ErpAuthenticationResult
    {
        $email = strtolower(trim($email));
        $slug = strtolower(trim($slug));
        $tenant = $this->reader->findTenantBySlug($slug);
        $user = $email !== '' ? $this->reader->findUserByEmail($email) : null;

        if (!$this->tenantIsAvailable($tenant)
            || $user === null
            || !password_verify($password, (string) ($user['senha'] ?? ''))
            || strtolower(trim((string) ($user['status'] ?? ''))) !== 'ativo'
            || (int) ($user['tenant_id'] ?? 0) !== (int) $tenant['tenant_id']) {
            throw new DomainException('Credenciais inválidas ou empresa indisponível.');
        }

        $userId = (int) $user['id'];
        $tenantId = (int) $tenant['tenant_id'];
        $context = $this->contextResolver->resolve(new LegacyTenantContextInput(
            authenticatedUserId: $userId,
            legacyTenantId: $tenantId,
            slug: (string) $tenant['slug']
        ));
        $identity = new AuthenticatedTenantUser($userId, $tenantId, (string) $user['nome'], (string) $user['email']);

        return new ErpAuthenticationResult($identity, $context, $tenant);
    }

    public function restore(int $userId, int $tenantId): ErpAuthenticationResult
    {
        $tenant = $this->reader->findTenantById($tenantId);
        if (!$this->tenantIsAvailable($tenant)) {
            throw new DomainException('Sessão ERP inválida ou empresa indisponível.');
        }

        $user = $this->reader->findUserById($userId);
        return $this->restoreFromRecords($userId, $tenantId, $user, $tenant);
    }

    public function restoreFromRecords(int $userId, int $tenantId, ?array $user, ?array $tenant): ErpAuthenticationResult
    {
        if ($userId < 1 || $tenantId < 1 || !$this->tenantIsAvailable($tenant)
            || $user === null || (int) ($user['id'] ?? 0) !== $userId
            || (int) ($user['tenant_id'] ?? 0) !== $tenantId
            || strtolower(trim((string) ($user['status'] ?? ''))) !== 'ativo') {
            throw new DomainException('Sessão ERP inválida ou empresa indisponível.');
        }
        $context = $this->contextResolver->resolve(new LegacyTenantContextInput($userId, $tenantId));
        return new ErpAuthenticationResult(
            new AuthenticatedTenantUser($userId, $tenantId, (string) $user['nome'], (string) $user['email']),
            $context,
            $tenant
        );
    }

    private function tenantIsAvailable(?array $tenant): bool
    {
        if ($tenant === null || (int) ($tenant['tenant_id'] ?? 0) < 1 || !empty($tenant['blocked'])) return false;
        if (!in_array(strtolower(trim((string) ($tenant['status'] ?? ''))), ['ativa', 'ativo', 'active'], true)) return false;
        $expected = 'mini_erp_tenant_' . (int) $tenant['tenant_id'];
        return hash_equals($expected, trim((string) ($tenant['db_name'] ?? '')));
    }
}
