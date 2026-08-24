<?php

declare(strict_types=1);

namespace MiniErp\Contracts;

use MiniErp\Context\AuthenticatedPlatformAdmin;

interface ControlPlaneReaderContract
{
    public function findActiveIdentityByUserId(int $userId): ?AuthenticatedPlatformAdmin;

    /** @return array{id: int, email: string, nome: string, senha: string}|null */
    public function findActiveAuthenticationRecordByEmail(string $email): ?array;

    /** @return array<int, array<string, mixed>> */
    public function listTenants(): array;
}
