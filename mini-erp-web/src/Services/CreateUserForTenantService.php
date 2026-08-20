<?php

declare(strict_types=1);

namespace MiniErp\Services;

use DomainException;
use InvalidArgumentException;
use MiniErp\Contracts\UserRepositoryContract;
use MiniErp\Context\AdministrativeContext;

final class CreateUserForTenantService
{
    public function __construct(
        private UserRepositoryContract $userRepository
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function create(?AdministrativeContext $administrativeContext, CreateUserForTenantRequest $request): array
    {
        if (!$administrativeContext instanceof AdministrativeContext) {
            throw new InvalidArgumentException('An authenticated AdministrativeContext is required before creating a user for a selected tenant.');
        }

        $targetTenantId = $administrativeContext->getSelectedTenantId();

        if ($this->userRepository->emailExistsForTenant($request->getEmail(), $targetTenantId)) {
            throw new DomainException(sprintf('A user already exists for email %s in tenant %d.', $request->getEmail(), $targetTenantId));
        }

        $passwordHash = password_hash($request->getPassword(), PASSWORD_DEFAULT);

        return $this->userRepository->createForTenant(
            $targetTenantId,
            $request->toPersistencePayload($targetTenantId, $passwordHash)
        );
    }
}
