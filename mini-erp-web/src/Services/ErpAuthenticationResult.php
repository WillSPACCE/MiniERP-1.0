<?php

declare(strict_types=1);

namespace MiniErp\Services;

use MiniErp\Context\AuthenticatedTenantUser;
use MiniErp\Context\TenantContext;

final readonly class ErpAuthenticationResult
{
    public function __construct(
        public AuthenticatedTenantUser $identity,
        public TenantContext $tenantContext,
        public array $tenant
    ) {}
}
