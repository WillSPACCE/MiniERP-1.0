<?php

declare(strict_types=1);

namespace MiniErp\Context;

use InvalidArgumentException;

final readonly class SelectedTenant
{
    private const MIN_ID = 1;

    public function __construct(
        private int $authenticatedUserId,
        private int $selectedTenantId,
        private bool $explicitlyAuthorized
    ) {
        $this->assertPositiveId('authenticatedUserId', $this->authenticatedUserId);
        $this->assertPositiveId('selectedTenantId', $this->selectedTenantId);
    }

    public function getAuthenticatedUserId(): int
    {
        return $this->authenticatedUserId;
    }

    public function getSelectedTenantId(): int
    {
        return $this->selectedTenantId;
    }

    public function isExplicitlyAuthorized(): bool
    {
        return $this->explicitlyAuthorized;
    }

    public function toArray(): array
    {
        return [
            'authenticatedUserId' => $this->authenticatedUserId,
            'selectedTenantId' => $this->selectedTenantId,
            'explicitlyAuthorized' => $this->explicitlyAuthorized,
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
