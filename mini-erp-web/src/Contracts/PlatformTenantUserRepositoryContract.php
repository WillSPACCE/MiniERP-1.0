<?php

declare(strict_types=1);

namespace MiniErp\Contracts;

interface PlatformTenantUserRepositoryContract
{
    public function listForTenant(int $tenantId): array;
    public function findForTenant(int $tenantId, int $userId): ?array;
    public function emailExists(string $email, ?int $exceptUserId = null): bool;
    public function createForTenant(int $tenantId, array $data): array;
    public function updateForTenant(int $tenantId, int $userId, array $data): bool;
    public function setStatusForTenant(int $tenantId, int $userId, string $status): bool;
    public function setPasswordForTenant(int $tenantId, int $userId, string $passwordHash): bool;
}
