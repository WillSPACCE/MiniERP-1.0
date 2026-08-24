<?php

declare(strict_types=1);

namespace MiniErp\Contracts;

interface PlatformTenantRepositoryContract
{
    public function slugExists(string $slug, ?int $exceptTenantId = null): bool;

    public function cnpjExists(string $cnpj, ?int $exceptTenantId = null): bool;

    /** @param array<string, string> $data */
    public function create(array $data): array;

    /** @param array<string, string> $data */
    public function update(int $tenantId, array $data): array;

    public function findById(int $tenantId): ?array;

    public function beginProvisioning(int $tenantId): bool;

    public function supportsSchemaVersion(): bool;

    public function completeProvisioning(int $tenantId, string $databaseName, string $schemaVersion): bool;
}
