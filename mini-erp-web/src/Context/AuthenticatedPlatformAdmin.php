<?php

declare(strict_types=1);

namespace MiniErp\Context;

use InvalidArgumentException;

final readonly class AuthenticatedPlatformAdmin
{
    public function __construct(
        private int $userId,
        private string $email,
        private string $name,
        private string $role = 'SUPER_ADMIN'
    ) {
        if ($this->userId < 1) {
            throw new InvalidArgumentException('Platform admin user id must be a positive integer.');
        }

        if (trim($this->email) === '') {
            throw new InvalidArgumentException('Platform admin email is required.');
        }

        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Platform admin name is required.');
        }
        if (!in_array($this->role, ['SUPER_ADMIN', 'SUPPORT', 'DATABASE_ADMIN', 'AUDITOR'], true)) { throw new InvalidArgumentException('Platform admin role is invalid.'); }
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }
    public function getRole(): string { return $this->role; }
}
