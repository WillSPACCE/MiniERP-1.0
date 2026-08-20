<?php

declare(strict_types=1);

namespace MiniErp\Adapters;

use InvalidArgumentException;

final readonly class LegacyTenantContextInput
{
    public function __construct(
        private int $authenticatedUserId,
        private ?int $legacyTenantId = null,
        private ?int $legacyCompanyId = null,
        private ?int $currentCompanyId = null,
        private ?int $selectedTenantId = null,
        private ?string $slug = null,
        private ?bool $isGlobalAdmin = null
    ) {
        $this->assertPositiveId('authenticatedUserId', $this->authenticatedUserId);

        $this->assertOptionalPositiveId('legacyTenantId', $this->legacyTenantId);
        $this->assertOptionalPositiveId('legacyCompanyId', $this->legacyCompanyId);
        $this->assertOptionalPositiveId('currentCompanyId', $this->currentCompanyId);
        $this->assertOptionalPositiveId('selectedTenantId', $this->selectedTenantId);

        if ($this->slug !== null && trim($this->slug) === '') {
            throw new InvalidArgumentException('slug cannot be blank when provided.');
        }
    }

    public function getAuthenticatedUserId(): int
    {
        return $this->authenticatedUserId;
    }

    public function getLegacyTenantId(): ?int
    {
        return $this->legacyTenantId;
    }

    public function getLegacyCompanyId(): ?int
    {
        return $this->legacyCompanyId;
    }

    public function getCurrentCompanyId(): ?int
    {
        return $this->currentCompanyId;
    }

    public function getSelectedTenantId(): ?int
    {
        return $this->selectedTenantId;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function isGlobalAdmin(): ?bool
    {
        return $this->isGlobalAdmin;
    }

    public function toArray(): array
    {
        return [
            'authenticatedUserId' => $this->authenticatedUserId,
            'legacyTenantId' => $this->legacyTenantId,
            'legacyCompanyId' => $this->legacyCompanyId,
            'currentCompanyId' => $this->currentCompanyId,
            'selectedTenantId' => $this->selectedTenantId,
            'slug' => $this->slug,
            'isGlobalAdmin' => $this->isGlobalAdmin,
        ];
    }

    private function assertPositiveId(string $fieldName, int $value): void
    {
        if ($value < 1) {
            throw new InvalidArgumentException(sprintf('%s must be a positive integer greater than or equal to 1.', $fieldName));
        }
    }

    private function assertOptionalPositiveId(string $fieldName, ?int $value): void
    {
        if ($value !== null) {
            $this->assertPositiveId($fieldName, $value);
        }
    }
}
