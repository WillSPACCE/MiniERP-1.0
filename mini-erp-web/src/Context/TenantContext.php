<?php

declare(strict_types=1);

namespace MiniErp\Context;

use InvalidArgumentException;

final readonly class TenantContext
{
    private const MIN_ID = 1;

    public function __construct(
        private int $authenticatedUserId,
        private int $effectiveTenantId,
        private int $userTenantId
    ) {
        $this->assertPositiveId('authenticatedUserId', $this->authenticatedUserId);
        $this->assertPositiveId('effectiveTenantId', $this->effectiveTenantId);
        $this->assertPositiveId('userTenantId', $this->userTenantId);

        if ($this->userTenantId !== $this->effectiveTenantId) {
            throw new InvalidArgumentException(
                'TenantContext requires userTenantId to match effectiveTenantId for the common-user scope.'
            );
        }
    }

    public function getAuthenticatedUserId(): int
    {
        return $this->authenticatedUserId;
    }

    public function getEffectiveTenantId(): int
    {
        return $this->effectiveTenantId;
    }

    public function getUserTenantId(): int
    {
        return $this->userTenantId;
    }

    public function toArray(): array
    {
        return [
            'authenticatedUserId' => $this->authenticatedUserId,
            'effectiveTenantId' => $this->effectiveTenantId,
            'userTenantId' => $this->userTenantId,
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
