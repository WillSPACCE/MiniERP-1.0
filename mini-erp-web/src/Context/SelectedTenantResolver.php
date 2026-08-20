<?php

declare(strict_types=1);

namespace MiniErp\Context;

use DomainException;
use InvalidArgumentException;
use MiniErp\Adapters\LegacyTenantContextInput;

final class SelectedTenantResolver
{
    public function resolve(int $authenticatedUserId, ?int $selectedTenantId, array $trustedTenantIds, bool $explicitlyAuthorized = false): SelectedTenant
    {
        $this->assertPositiveUserId($authenticatedUserId);

        if ($selectedTenantId === null) {
            throw new DomainException('No tenant selection was provided. Selection requires an explicit tenant_id.');
        }

        if ($selectedTenantId < 1) {
            throw new InvalidArgumentException('selectedTenantId must be a positive integer greater than or equal to 1.');
        }

        $trusted = $this->normalizeTrustedTenantIds($trustedTenantIds);
        if (!in_array($selectedTenantId, $trusted, true)) {
            throw new DomainException(sprintf('Selected tenant %d is not present in the trusted tenant registry.', $selectedTenantId));
        }

        if (!$explicitlyAuthorized) {
            throw new DomainException('Selection requires explicit administrative authorization; no implicit global fallback is accepted.');
        }

        return new SelectedTenant(
            authenticatedUserId: $authenticatedUserId,
            selectedTenantId: $selectedTenantId,
            explicitlyAuthorized: true
        );
    }

    public function fromLegacyInput(LegacyTenantContextInput $input, array $trustedTenantIds, bool $explicitlyAuthorized = false): SelectedTenant
    {
        $selectedTenantId = $input->getSelectedTenantId();
        return $this->resolve(
            authenticatedUserId: $input->getAuthenticatedUserId(),
            selectedTenantId: $selectedTenantId,
            trustedTenantIds: $trustedTenantIds,
            explicitlyAuthorized: $explicitlyAuthorized
        );
    }

    private function assertPositiveUserId(int $authenticatedUserId): void
    {
        if ($authenticatedUserId < 1) {
            throw new InvalidArgumentException('authenticatedUserId must be a positive integer greater than or equal to 1.');
        }
    }

    private function normalizeTrustedTenantIds(array $trustedTenantIds): array
    {
        $normalized = [];
        foreach ($trustedTenantIds as $tenantId) {
            if ($tenantId === null || $tenantId === '') {
                continue;
            }

            $value = (int) $tenantId;
            if ($value < 1) {
                continue;
            }

            $normalized[] = $value;
        }

        return array_values(array_unique($normalized));
    }
}
