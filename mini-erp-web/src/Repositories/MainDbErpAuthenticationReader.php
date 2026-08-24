<?php

declare(strict_types=1);

namespace MiniErp\Repositories;

use MiniErp\Contracts\ErpAuthenticationReaderContract;
use PDO;

final class MainDbErpAuthenticationReader implements ErpAuthenticationReaderContract
{
    public function __construct(private PDO $pdo) {}

    public function findUserByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, nome, email, senha, status, tenant_id FROM usuarios WHERE LOWER(email) = LOWER(:email) LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        return $statement->fetch() ?: null;
    }

    public function findUserById(int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, nome, email, role, avatar, status, tenant_id FROM usuarios WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $userId]);
        return $statement->fetch() ?: null;
    }

    public function findTenantBySlug(string $slug): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id AS tenant_id, nome_fantasia, razao_social, slug, status, blocked, db_name FROM tenants WHERE slug = :slug LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);
        return $statement->fetch() ?: null;
    }

    public function findTenantById(int $tenantId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id AS tenant_id, nome_fantasia, razao_social, slug, status, blocked, db_name FROM tenants WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $tenantId]);
        return $statement->fetch() ?: null;
    }
}
