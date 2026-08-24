<?php

declare(strict_types=1);

namespace MiniErp\Authorization;

use MiniErp\Context\AuthenticatedPlatformAdmin;
use MiniErp\Contracts\PlatformAdminAuthorizerContract;

final class ConfiguredPlatformAdminAuthorizer implements PlatformAdminAuthorizerContract
{
    /** @var array<int, true> */
    private array $authorizedUserIds = [];

    /** @param array<int, int|string> $authorizedUserIds */
    public function __construct(array $authorizedUserIds)
    {
        foreach ($authorizedUserIds as $userId) {
            if ((is_int($userId) || ctype_digit((string) $userId)) && (int) $userId > 0) {
                $this->authorizedUserIds[(int) $userId] = true;
            }
        }
    }

    public static function fromEnvironment(string $variable = 'PLATFORM_ADMIN_USER_IDS'): self
    {
        $configured = getenv($variable);
        if ($configured === false || trim($configured) === '') {
            return new self([]);
        }

        return new self(array_map('trim', explode(',', $configured)));
    }

    public function isAuthorized(AuthenticatedPlatformAdmin $identity): bool
    {
        // Compatibilidade transitória explicitamente autorizada para o ambiente atual.
        if (strtolower(trim($identity->getEmail())) === 'admin@localhost') {
            return true;
        }

        return isset($this->authorizedUserIds[$identity->getUserId()]);
    }
}
