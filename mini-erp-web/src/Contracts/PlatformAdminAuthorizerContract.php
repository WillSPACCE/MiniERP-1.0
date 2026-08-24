<?php

declare(strict_types=1);

namespace MiniErp\Contracts;

use MiniErp\Context\AuthenticatedPlatformAdmin;

interface PlatformAdminAuthorizerContract
{
    public function isAuthorized(AuthenticatedPlatformAdmin $identity): bool;
}
