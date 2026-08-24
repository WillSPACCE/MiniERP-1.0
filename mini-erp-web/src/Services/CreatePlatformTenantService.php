<?php

declare(strict_types=1);

namespace MiniErp\Services;

use DomainException;
use MiniErp\Context\AuthenticatedPlatformAdmin;
use MiniErp\Contracts\PlatformAdminAuthorizerContract;
use MiniErp\Contracts\PlatformTenantRepositoryContract;

final class CreatePlatformTenantService
{
    public function __construct(
        private PlatformTenantRepositoryContract $repository,
        private PlatformAdminAuthorizerContract $authorizer
    ) {
    }

    public function create(AuthenticatedPlatformAdmin $actor, PlatformTenantData $data): array
    {
        if (!$this->authorizer->isAuthorized($actor)) {
            throw new DomainException('Control-plane access is not authorized.');
        }
        $payload = $data->toArray();
        if ($this->repository->slugExists($payload['slug'])) {
            throw new DomainException('Slug já cadastrado para outra empresa.');
        }
        if ($this->repository->cnpjExists($payload['cnpj'])) {
            throw new DomainException('CNPJ já cadastrado para outra empresa.');
        }
        return $this->repository->create($payload);
    }
}
