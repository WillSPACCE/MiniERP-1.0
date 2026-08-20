<?php

declare(strict_types=1);

namespace MiniErp\Context;

use DomainException;
use InvalidArgumentException;

final readonly class AdministrativeContext
{
    private const MIN_ID = 1;

    public function __construct(
        private int $authenticatedAdminUserId,
        private SelectedTenant $selectedTenant
    ) {
        $this->assertPositiveId('authenticatedAdminUserId', $this->authenticatedAdminUserId);

        if (!$this->selectedTenant->isExplicitlyAuthorized()) {
            throw new DomainException(
                'AdministrativeContext requires a SelectedTenant that has already passed explicit authorization validation.'
            );
        }
    }

    public function getAuthenticatedAdminUserId(): int
    {
        return $this->authenticatedAdminUserId;
    }

    public function getSelectedTenant(): SelectedTenant
    {
        return $this->selectedTenant;
    }

    public function getSelectedTenantId(): int
    {
        return $this->selectedTenant->getSelectedTenantId();
    }

    public function toArray(): array
    {
        return [
            'authenticatedAdminUserId' => $this->authenticatedAdminUserId,
            'selectedTenant' => $this->selectedTenant->toArray(),
        ];
    }

    private function assertPositiveId(string $fieldName, int $value): void
    {
        if ($value < self::MIN_ID) {
            throw new InvalidArgumentException(
                sprintf('%s must be a positive integer greater than or equal to %d.', $fieldName, self::MIN_ID)
            );
        }
    }
}
