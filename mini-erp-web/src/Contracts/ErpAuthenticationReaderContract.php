<?php

declare(strict_types=1);

namespace MiniErp\Contracts;

interface ErpAuthenticationReaderContract
{
    public function findUserByEmail(string $email): ?array;

    public function findUserById(int $userId): ?array;

    public function findTenantBySlug(string $slug): ?array;

    public function findTenantById(int $tenantId): ?array;
}
