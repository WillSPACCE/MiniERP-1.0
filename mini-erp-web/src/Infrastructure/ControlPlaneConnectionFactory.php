<?php

declare(strict_types=1);

namespace MiniErp\Infrastructure;

use DomainException;
use InvalidArgumentException;
use PDO;

final class ControlPlaneConnectionFactory
{
    public function __construct(private string $configPath)
    {
    }

    public function create(): PDO
    {
        $config = require $this->configPath;
        $db = $config['db'] ?? [];
        $database = (string) ($db['database'] ?? '');

        if (empty($db['host']) || empty($db['port']) || $database === '' || !array_key_exists('username', $db)) {
            throw new InvalidArgumentException('Control-plane database configuration is incomplete.');
        }

        self::assertControlPlaneDatabaseName($database);

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $db['host'],
            $db['port'],
            $database
        );

        return new PDO($dsn, (string) $db['username'], (string) ($db['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public static function assertControlPlaneDatabaseName(string $database): void
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            throw new InvalidArgumentException('Control-plane database name is invalid.');
        }

        if (preg_match('/_tenant_[0-9]+$/i', $database)) {
            throw new DomainException('A tenant database cannot be used as the control-plane database.');
        }
    }
}
