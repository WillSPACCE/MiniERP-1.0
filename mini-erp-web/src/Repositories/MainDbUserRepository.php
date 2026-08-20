<?php

declare(strict_types=1);

namespace MiniErp\Repositories;

use MiniErp\Contracts\UserRepositoryContract;
use PDO;
use Throwable;

final class MainDbUserRepository implements UserRepositoryContract
{
    private PDO $pdoMain;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config.php';
        $dbConf = $config['db'];
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConf['host'], $dbConf['port'], $dbConf['database']);
        $this->pdoMain = new PDO($dsn, $dbConf['username'], $dbConf['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    }

    public function emailExistsForTenant(string $email, int $tenantId): bool
    {
        $stmt = $this->pdoMain->prepare('SELECT id FROM usuarios WHERE email = :email AND tenant_id = :tid LIMIT 1');
        $stmt->execute(['email' => $email, 'tid' => $tenantId]);
        return (bool) $stmt->fetch();
    }

    public function createForTenant(int $tenantId, array $userData): array
    {
        // Ensure tenant_id in payload
        $userData['tenant_id'] = $tenantId;
        $fields = array_keys($userData);
        $placeholders = array_map(fn($f) => ':' . $f, $fields);
        $stmt = $this->pdoMain->prepare('INSERT INTO usuarios (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')');
        $stmt->execute($userData);
        $userData['id'] = (int)$this->pdoMain->lastInsertId();
        return $userData;
    }

    public function assignUserToCompanyExplicit(int $userId, int $tenantId, ?int $companyId = null): void
    {
        if ($companyId === null) {
            $companyId = $tenantId;
        }

        // Attempt update with tenant_id column present
        try {
            $stmt = $this->pdoMain->prepare('UPDATE usuarios SET company_id = :cid, tenant_id = :tid WHERE id = :id');
            $stmt->execute(['cid' => $companyId, 'tid' => $tenantId, 'id' => $userId]);
            return;
        } catch (Throwable $e) {
            // fallback: try without tenant_id column
        }

        $stmt = $this->pdoMain->prepare('UPDATE usuarios SET company_id = :cid WHERE id = :id');
        $stmt->execute(['cid' => $companyId, 'id' => $userId]);
    }
}
