<?php

declare(strict_types=1);

namespace MiniErp\Services;

use InvalidArgumentException;

final class PlatformTenantDatabaseName
{
    public static function fromTenantId(int $tenantId): string
    {
        if ($tenantId < 1) {
            throw new InvalidArgumentException('Tenant ID inválido para provisionamento.');
        }

        return 'mini_erp_tenant_' . $tenantId;
    }
}
