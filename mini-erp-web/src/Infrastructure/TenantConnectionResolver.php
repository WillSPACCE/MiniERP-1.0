<?php

declare(strict_types=1);

namespace MiniErp\Infrastructure;

use DomainException;
use InvalidArgumentException;
use MiniErp\Context\TenantContext;
use PDO;

class TenantConnectionResolver
{
    private string $configPath;

    public function __construct(?string $configPath = null)
    {
        $this->configPath = $configPath ?? __DIR__ . '/../../config.php';
    }

    public function resolve(TenantContext $context): PDO
    {
        $tenantId = $context->getEffectiveTenantId();
        $dbName = $this->fetchDbNameForTenantId($tenantId);
        $this->assertValidDatabaseName($dbName);

        return $this->createTenantPdo($dbName);
    }

    protected function fetchDbNameForTenantId(int $tenantId): string
    {
        $config = require $this->configPath;
        $dbConfig = $config['db'] ?? [];

        $this->assertConfigIsUsable($dbConfig);

        $serverDsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['database']
        );

        $controlPlanePdo = new PDO(
            $serverDsn,
            $dbConfig['username'],
            $dbConfig['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $stmt = $controlPlanePdo->prepare('SELECT db_name FROM tenants WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $tenantId]);
        $row = $stmt->fetch();

        if ($row === false || empty($row['db_name'])) {
            throw new DomainException(sprintf('No tenant record was found for effectiveTenantId %d.', $tenantId));
        }

        return (string) $row['db_name'];
    }

    private function createTenantPdo(string $dbName): PDO
    {
        $config = require $this->configPath;
        $dbConfig = $config['db'] ?? [];

        $this->assertConfigIsUsable($dbConfig);

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $dbConfig['host'],
            $dbConfig['port'],
            $dbName
        );

        return new PDO(
            $dsn,
            $dbConfig['username'],
            $dbConfig['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    private function assertConfigIsUsable(array $dbConfig): void
    {
        if (empty($dbConfig['host']) || empty($dbConfig['port']) || empty($dbConfig['database'])) {
            throw new InvalidArgumentException('The application database configuration is incomplete.');
        }
    }

    private function assertValidDatabaseName(string $dbName): void
    {
        if ($dbName === '' || !preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
            throw new InvalidArgumentException('Resolved tenant database name is invalid and cannot be used in a DSN.');
        }
    }
}
