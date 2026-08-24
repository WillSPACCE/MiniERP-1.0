<?php

declare(strict_types=1);

namespace MiniErp\Services;

use DomainException;
use MiniErp\Contracts\EstablishmentRepositoryContract;

final class EstablishmentService
{
    public function __construct(private EstablishmentRepositoryContract $repository) {}
    public function find(int $tenantId): ?array { return $this->repository->findPrimaryForTenant($tenantId); }
    public function save(int $tenantId, EstablishmentData $data): array
    {
        if (!$this->repository->schemaAvailable()) throw new DomainException('Schema fiscal indisponível. A migration FISCAL-01 deve ser aplicada explicitamente neste tenant.');
        return $this->repository->savePrimaryForTenant($tenantId, $data->toArray());
    }
}
