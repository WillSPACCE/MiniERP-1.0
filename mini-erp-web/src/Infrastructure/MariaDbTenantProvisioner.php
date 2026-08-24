<?php

declare(strict_types=1);

namespace MiniErp\Infrastructure;

use MiniErp\Contracts\TenantDatabaseProvisionerContract;
use MiniErp\Services\TenantSchemaTemplate;
use PDO;
use RuntimeException;

final class MariaDbTenantProvisioner implements TenantDatabaseProvisionerContract
{
    /** @var list<string>|null */
    private ?array $expectedTables = null;

    public function __construct(
        private PDO $serverConnection,
        private array $databaseConfig,
        private TenantSchemaTemplate $schemaTemplate
    ) {
    }

    public function databaseExists(string $databaseName): bool
    {
        $this->assertDatabaseName($databaseName);
        $stmt = $this->serverConnection->prepare(
            'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :name LIMIT 1'
        );
        $stmt->execute(['name' => $databaseName]);
        return (bool) $stmt->fetchColumn();
    }

    public function createDatabase(string $databaseName): void
    {
        $this->assertDatabaseName($databaseName);
        if ($this->databaseExists($databaseName)) {
            throw new RuntimeException('O banco dedicado derivado já existe e não pode ser adotado automaticamente.');
        }
        $this->serverConnection->exec(
            "CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    public function installSchema(string $databaseName, string $schemaVersion): void
    {
        $pdo = $this->tenantConnection($databaseName);
        foreach ($this->schemaStatements($schemaVersion) as $statement) {
            $pdo->exec($statement);
        }
    }

    public function validateSchema(string $databaseName, string $schemaVersion): bool
    {
        $this->assertDatabaseName($databaseName);
        $stmt = $this->serverConnection->prepare(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = :name AND TABLE_TYPE = :type'
        );
        $stmt->execute(['name' => $databaseName, 'type' => 'BASE TABLE']);
        $actual = $stmt->fetchAll(PDO::FETCH_COLUMN);
        sort($actual);
        $expected = $this->expectedTables($schemaVersion);
        sort($expected);
        return $actual === $expected;
    }

    private function tenantConnection(string $databaseName): PDO
    {
        $this->assertDatabaseName($databaseName);
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $this->databaseConfig['host'],
            $this->databaseConfig['port'],
            $databaseName
        );
        return new PDO($dsn, $this->databaseConfig['username'], $this->databaseConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    /** @return list<string> */
    private function schemaStatements(string $schemaVersion): array
    {
        $sql = file_get_contents($this->schemaTemplate->schemaPathFor($schemaVersion));
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('Fonte estrutural vazia ou ilegível.');
        }
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? '';
        $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
        return array_values(array_filter(array_map('trim', $statements), static fn (string $item): bool => $item !== ''));
    }

    /** @return list<string> */
    private function expectedTables(string $schemaVersion): array
    {
        if ($this->expectedTables !== null) {
            return $this->expectedTables;
        }
        $sql = file_get_contents($this->schemaTemplate->schemaPathFor($schemaVersion));
        preg_match_all('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?([a-zA-Z0-9_]+)`?/i', (string) $sql, $matches);
        $this->expectedTables = array_values(array_unique($matches[1] ?? []));
        if ($this->expectedTables === []) {
            throw new RuntimeException('Nenhuma tabela esperada foi encontrada na fonte estrutural.');
        }
        return $this->expectedTables;
    }

    private function assertDatabaseName(string $databaseName): void
    {
        if (!preg_match('/^mini_erp_tenant_[1-9][0-9]*$/', $databaseName)) {
            throw new RuntimeException('Nome de banco dedicado inválido.');
        }
    }
}
