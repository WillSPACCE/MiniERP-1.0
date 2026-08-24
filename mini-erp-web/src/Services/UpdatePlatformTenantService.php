<?php

declare(strict_types=1);

namespace MiniErp\Services;

use DomainException;
use MiniErp\Context\AuthenticatedPlatformAdmin;
use MiniErp\Contracts\PlatformAdminAuthorizerContract;
use MiniErp\Contracts\PlatformTenantRepositoryContract;

final class UpdatePlatformTenantService
{
    public function __construct(
        private PlatformTenantRepositoryContract $repository,
        private PlatformAdminAuthorizerContract $authorizer
    ) {
    }

    public function update(AuthenticatedPlatformAdmin $actor, int $tenantId, PlatformTenantData $data): array
    {
        if (!$this->authorizer->isAuthorized($actor)) {
            throw new DomainException('Control-plane access is not authorized.');
        }
        if ($tenantId < 1 || $this->repository->findById($tenantId) === null) {
            throw new DomainException('Empresa não encontrada para edição.');
        }
        $payload = $data->toArray();
        if ($this->repository->slugExists($payload['slug'], $tenantId)) {
            throw new DomainException('Slug já cadastrado para outra empresa.');
        }
        if ($this->repository->cnpjExists($payload['cnpj'], $tenantId)) {
            throw new DomainException('CNPJ já cadastrado para outra empresa.');
        }
        return $this->repository->update($tenantId, $payload);
    }
}
