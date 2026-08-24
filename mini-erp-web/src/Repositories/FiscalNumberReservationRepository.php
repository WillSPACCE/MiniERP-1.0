<?php
declare(strict_types=1);

namespace MiniErp\Repositories;

use PDO;
use PDOException;
use RuntimeException;

final class FiscalNumberReservationRepository
{
    public function __construct(
        private PDO $pdo,
        private int $tenantId,
    ) {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fiscal_number_reservations WHERE tenant_id = :tenant_id AND id = :id LIMIT 1');
        $stmt->execute(['tenant_id' => $this->tenantId, 'id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findByDocumentVersion(int $documentId, int $version): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fiscal_number_reservations WHERE tenant_id = :tenant_id AND fiscal_document_id = :document_id AND fiscal_document_version = :version LIMIT 1');
        $stmt->execute([
            'tenant_id' => $this->tenantId,
            'document_id' => $documentId,
            'version' => $version,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findByAccessKey(string $accessKey): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fiscal_number_reservations WHERE tenant_id = :tenant_id AND access_key = :access_key LIMIT 1');
        $stmt->execute(['tenant_id' => $this->tenantId, 'access_key' => $accessKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findByIdempotencyKey(string $key): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fiscal_number_reservations WHERE tenant_id = :tenant_id AND idempotency_key = :key LIMIT 1');
        $stmt->execute(['tenant_id' => $this->tenantId, 'key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function create(array $row): array
    {
        $payload = [
            'tenant_id' => $this->tenantId,
            'establishment_id' => (int) ($row['establishment_id'] ?? 0),
            'fiscal_document_id' => (int) ($row['fiscal_document_id'] ?? 0),
            'fiscal_document_version' => (int) ($row['fiscal_document_version'] ?? 1),
            'fiscal_series_id' => isset($row['fiscal_series_id']) ? (int) $row['fiscal_series_id'] : null,
            'model' => (string) ($row['model'] ?? '55'),
            'series' => (int) ($row['series'] ?? 0),
            'number' => (int) ($row['number'] ?? 0),
            'environment' => (int) ($row['environment'] ?? 2),
            'cnf' => isset($row['cnf']) && trim((string) $row['cnf']) !== '' ? trim((string) $row['cnf']) : null,
            'access_key' => isset($row['access_key']) && trim((string) $row['access_key']) !== '' ? trim((string) $row['access_key']) : null,
            'status' => strtoupper((string) ($row['status'] ?? 'RESERVED')),
            'idempotency_key' => isset($row['idempotency_key']) && trim((string) $row['idempotency_key']) !== '' ? trim((string) $row['idempotency_key']) : null,
            'created_by' => (int) ($row['created_by'] ?? 0),
        ];

        if ($payload['establishment_id'] <= 0 || $payload['fiscal_document_id'] <= 0 || $payload['series'] < 0 || $payload['number'] <= 0) {
            throw new RuntimeException('reservation payload invalid');
        }

        $sql = 'INSERT INTO fiscal_number_reservations (
                    tenant_id, establishment_id, fiscal_document_id, fiscal_document_version,
                    fiscal_series_id, model, series, number, environment, cnf, access_key,
                    status, idempotency_key, created_by
                ) VALUES (
                    :tenant_id, :establishment_id, :fiscal_document_id, :fiscal_document_version,
                    :fiscal_series_id, :model, :series, :number, :environment, :cnf, :access_key,
                    :status, :idempotency_key, :created_by
                )';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($payload);
        } catch (PDOException $e) {
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'duplicate') || str_contains($msg, 'unique')) {
                throw new RuntimeException('duplicate reservation: ' . $e->getMessage(), 0, $e);
            }
            throw $e;
        }

        $id = (int) $this->pdo->lastInsertId();
        $record = $this->findById($id);
        if ($record === null) {
            throw new RuntimeException('reservation was not persisted');
        }

        return $record;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE fiscal_number_reservations SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE tenant_id = :tenant_id AND id = :id');
        $stmt->execute([
            'status' => strtoupper(trim($status)),
            'tenant_id' => $this->tenantId,
            'id' => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function updateRuntimeMetadata(int $id, array $values): bool
    {
        $updates = ['updated_at = CURRENT_TIMESTAMP'];
        $params = ['tenant_id' => $this->tenantId, 'id' => $id];

        foreach ($values as $key => $value) {
            $updates[] = sprintf('`%s` = :%s', $key, $key);
            $params[$key] = $value;
        }

        $sql = 'UPDATE fiscal_number_reservations SET ' . implode(', ', $updates) . ' WHERE tenant_id = :tenant_id AND id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }
}
