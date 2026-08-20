<?php

declare(strict_types=1);

namespace MiniErp\Context;

use DomainException;
use InvalidArgumentException;
use MiniErp\Adapters\LegacyTenantContextInput;

final class TenantContextResolver
{
    public function resolve(LegacyTenantContextInput $input): TenantContext
    {
        $authenticatedUserId = $input->getAuthenticatedUserId();
        $legacyTenantId = $input->getLegacyTenantId();
        $legacyCompanyId = $input->getLegacyCompanyId();
        $currentCompanyId = $input->getCurrentCompanyId();
        $selectedTenantId = $input->getSelectedTenantId();
        $slug = $input->getSlug();
        $isGlobalAdmin = $input->isGlobalAdmin() ?? false;

        if ($isGlobalAdmin) {
            throw new DomainException('Global admin authorization is not supported by this resolver yet.');
        }

        if ($slug !== null && $legacyTenantId === null && $legacyCompanyId === null) {
            throw new DomainException('Slug alone is insufficient to resolve a tenant in this context.');
        }

        if ($legacyTenantId !== null && $legacyCompanyId !== null && $legacyTenantId !== $legacyCompanyId) {
            throw new DomainException('legacyTenantId and legacyCompanyId conflict and cannot both define the same user tenant.');
        }

        $userTenantId = $legacyTenantId ?? $legacyCompanyId;

        if ($userTenantId === null) {
            throw new DomainException('TenantContextResolver requires a tenant source for tenant-scoped operations.');
        }

        if ($selectedTenantId !== null && $selectedTenantId !== $userTenantId) {
            throw new DomainException('selectedTenantId must match the resolved user tenant for common-user operations.');
        }

        if ($currentCompanyId !== null && $currentCompanyId !== $userTenantId) {
            throw new DomainException('currentCompanyId diverges from the resolved tenant and is considered inconsistent.');
        }

        return new TenantContext(
            authenticatedUserId: $authenticatedUserId,
            effectiveTenantId: $userTenantId,
            userTenantId: $userTenantId
        );
    }
}
