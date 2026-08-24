<?php

declare(strict_types=1);

namespace MiniErp\Contracts;

interface EstablishmentRepositoryContract
{
    public function schemaAvailable(): bool;

    public function findPrimaryForTenant(int $tenantId): ?array;

    public function savePrimaryForTenant(int $tenantId, array $data): array;
}
