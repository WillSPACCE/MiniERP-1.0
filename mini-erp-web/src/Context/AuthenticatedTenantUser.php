<?php

declare(strict_types=1);

namespace MiniErp\Context;

use InvalidArgumentException;

final readonly class AuthenticatedTenantUser
{
    public function __construct(
        private int $userId,
        private int $tenantId,
        private string $name,
        private string $email
    ) {
        if ($userId < 1 || $tenantId < 1 || trim($name) === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A tenant user identity must be complete and valid.');
        }
    }

    public function getUserId(): int { return $this->userId; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
}
