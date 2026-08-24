<?php

declare(strict_types=1);

namespace MiniErp\Repositories;

use DomainException;
use MiniErp\Contracts\PlatformTenantRepositoryContract;
use PDO;
use PDOException;

final class PlatformTenantRepository implements PlatformTenantRepositoryContract
{
    public function __construct(private PDO $pdo)
    {
    }

    public function slugExists(string $slug, ?int $exceptTenantId = null): bool
    {
        $sql = 'SELECT id FROM tenants WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($exceptTenantId !== null) {
            $sql .= ' AND id <> :except_id';
            $params['except_id'] = $exceptTenantId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function cnpjExists(string $cnpj, ?int $exceptTenantId = null): bool
    {
        $sql = 'SELECT id FROM tenants WHERE cnpj = :cnpj';
        $params = ['cnpj' => $cnpj];
        if ($exceptTenantId !== null) {
            $sql .= ' AND id <> :except_id';
            $params['except_id'] = $exceptTenantId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function create(array $data): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO tenants (uuid, razao_social, nome_fantasia, cnpj, slug, status) VALUES (:uuid, :razao_social, :nome_fantasia, :cnpj, :slug, 'cadastrada')"
            );
            $stmt->execute([
                'uuid' => bin2hex(random_bytes(16)),
                'razao_social' => $data['razao_social'],
                'nome_fantasia' => $data['nome_fantasia'],
                'cnpj' => $data['cnpj'],
                'slug' => $data['slug'],
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new DomainException('Já existe uma empresa com o CNPJ, slug ou identificador informado.');
            }
            throw $exception;
        }

        $created = $this->findById((int) $this->pdo->lastInsertId());
        if ($created === null) {
            throw new DomainException('A empresa foi registrada, mas não pôde ser relida com segurança.');
        }
        return $created;
    }

    public function update(int $tenantId, array $data): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE tenants SET razao_social = :razao_social, nome_fantasia = :nome_fantasia, cnpj = :cnpj, slug = :slug WHERE id = :id'
            );
            $stmt->execute([
                'id' => $tenantId,
                'razao_social' => $data['razao_social'],
                'nome_fantasia' => $data['nome_fantasia'],
                'cnpj' => $data['cnpj'],
                'slug' => $data['slug'],
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new DomainException('Já existe outra empresa com o CNPJ ou slug informado.');
            }
            throw $exception;
        }

        $updated = $this->findById($tenantId);
        if ($updated === null) {
            throw new DomainException('Empresa não encontrada para edição.');
        }
        return $updated;
    }

    public function findById(int $tenantId): ?array
    {
        $schemaVersion = $this->supportsSchemaVersion() ? 'schema_version' : 'NULL AS schema_version';
        $stmt = $this->pdo->prepare(
            "SELECT id AS tenant_id, razao_social, nome_fantasia, cnpj, slug, status, blocked, db_name, created_at, updated_at, {$schemaVersion} FROM tenants WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $tenantId]);
        return $stmt->fetch() ?: null;
    }

    public function beginProvisioning(int $tenantId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE tenants SET status = 'provisionando' WHERE id = :id AND status = 'cadastrada' AND COALESCE(blocked, 0) = 0 AND (db_name IS NULL OR db_name = '')"
        );
        $stmt->execute(['id' => $tenantId]);
        return $stmt->rowCount() === 1;
    }

    public function supportsSchemaVersion(): bool
    {
        $stmt = $this->pdo->query("SHOW COLUMNS FROM tenants LIKE 'schema_version'");
        return (bool) $stmt->fetch();
    }

    public function completeProvisioning(int $tenantId, string $databaseName, string $schemaVersion): bool
    {
        if (!$this->supportsSchemaVersion()) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            "UPDATE tenants SET status = 'ativa', db_name = :db_name, schema_version = :schema_version WHERE id = :id AND status = 'provisionando' AND COALESCE(blocked, 0) = 0 AND (db_name IS NULL OR db_name = '')"
        );
        $stmt->execute(['id' => $tenantId, 'db_name' => $databaseName, 'schema_version' => $schemaVersion]);
        return $stmt->rowCount() === 1;
    }
}
