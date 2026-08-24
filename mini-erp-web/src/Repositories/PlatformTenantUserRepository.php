<?php

declare(strict_types=1);

namespace MiniErp\Repositories;

use MiniErp\Contracts\PlatformTenantUserRepositoryContract;
use PDO;

final class PlatformTenantUserRepository implements PlatformTenantUserRepositoryContract
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listForTenant(int $tenantId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome, email, role, status FROM usuarios WHERE tenant_id = :tenant_id ORDER BY nome, id');
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    public function findForTenant(int $tenantId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome, email, role, status FROM usuarios WHERE id = :id AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute(['id' => $userId, 'tenant_id' => $tenantId]);
        return $stmt->fetch() ?: null;
    }

    public function emailExists(string $email, ?int $exceptUserId = null): bool
    {
        $sql = 'SELECT id FROM usuarios WHERE LOWER(email) = LOWER(:email)';
        $params = ['email' => $email];
        if ($exceptUserId !== null) { $sql .= ' AND id <> :except_id'; $params['except_id'] = $exceptUserId; }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function createForTenant(int $tenantId, array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO usuarios (nome, email, senha, role, status, tenant_id, company_id, email_verified) VALUES (:nome, :email, :senha, :role, :status, :tenant_id, :company_id, 1)');
        $stmt->execute([
            'nome' => $data['nome'], 'email' => $data['email'], 'senha' => $data['senha'],
            'role' => $data['role'], 'status' => $data['status'], 'tenant_id' => $tenantId,
            'company_id' => $tenantId,
        ]);
        $created = $this->findForTenant($tenantId, (int) $this->pdo->lastInsertId());
        if ($created === null) throw new \DomainException('Usuário criado, mas não pôde ser relido com segurança.');
        return $created;
    }

    public function updateForTenant(int $tenantId, int $userId, array $data): bool
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET nome = :nome, email = :email, role = :role, status = :status WHERE id = :id AND tenant_id = :tenant_id');
        $stmt->execute(['nome' => $data['nome'], 'email' => $data['email'], 'role' => $data['role'], 'status' => $data['status'], 'id' => $userId, 'tenant_id' => $tenantId]);
        return $stmt->rowCount() === 1 || $this->findForTenant($tenantId, $userId) !== null;
    }

    public function setStatusForTenant(int $tenantId, int $userId, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET status = :status WHERE id = :id AND tenant_id = :tenant_id');
        $stmt->execute(['status' => $status, 'id' => $userId, 'tenant_id' => $tenantId]);
        return $stmt->rowCount() === 1;
    }

    public function setPasswordForTenant(int $tenantId, int $userId, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET senha = :senha WHERE id = :id AND tenant_id = :tenant_id');
        $stmt->execute(['senha' => $passwordHash, 'id' => $userId, 'tenant_id' => $tenantId]);
        return $stmt->rowCount() === 1;
    }
}
