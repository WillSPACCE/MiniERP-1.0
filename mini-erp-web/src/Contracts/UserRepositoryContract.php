<?php

declare(strict_types=1);

namespace MiniErp\Contracts;

interface UserRepositoryContract
{
    public function emailExistsForTenant(string $email, int $tenantId): bool;

    /**
     * @return array<string, mixed>
     */
    public function createForTenant(int $tenantId, array $userData): array;
}
