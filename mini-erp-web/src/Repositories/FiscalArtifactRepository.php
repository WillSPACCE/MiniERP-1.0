<?php
declare(strict_types=1);

namespace MiniErp\Repositories;

use PDO;
use PDOException;
use RuntimeException;

final class FiscalArtifactRepository
{
    public function __construct(
        private PDO $pdo,
        private int $tenantId,
    ) {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fiscal_artifacts WHERE tenant_id = :tenant_id AND id = :id LIMIT 1');
        $stmt->execute(['tenant_id' => $this->tenantId, 'id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findByDocumentVersion(int $documentId, int $version, ?string $artifactType = null): ?array
    {
        $sql = 'SELECT * FROM fiscal_artifacts WHERE tenant_id = :tenant_id AND fiscal_document_id = :document_id AND fiscal_document_version = :version';
        $params = ['tenant_id' => $this->tenantId, 'document_id' => $documentId, 'version' => $version];

        if ($artifactType !== null && trim($artifactType) !== '') {
            $sql .= ' AND artifact_type = :artifact_type';
            $params['artifact_type'] = strtoupper(trim($artifactType));
        }

        $sql .= ' ORDER BY id DESC LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findByReservationId(int $reservationId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fiscal_artifacts WHERE tenant_id = :tenant_id AND number_reservation_id = :reservation_id LIMIT 1');
        $stmt->execute(['tenant_id' => $this->tenantId, 'reservation_id' => $reservationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findByAccessKey(string $accessKey): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fiscal_artifacts WHERE tenant_id = :tenant_id AND access_key = :access_key LIMIT 1');
        $stmt->execute(['tenant_id' => $this->tenantId, 'access_key' => $accessKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findValidArtifact(int $documentId, int $version, string $artifactType, array $allowedStatuses = ['XSD_VALID_OFFLINE', 'SIGNED', 'READY']): ?array
    {
        $placeholders = implode(',', array_fill(0, count($allowedStatuses), '?'));
        $sql = 'SELECT * FROM fiscal_artifacts WHERE tenant_id = ? AND fiscal_document_id = ? AND fiscal_document_version = ? AND artifact_type = ? AND status IN (' . $placeholders . ') ORDER BY id DESC LIMIT 1';
        $params = [$this->tenantId, $documentId, $version, strtoupper(trim($artifactType))];
        foreach ($allowedStatuses as $status) {
            $params[] = strtoupper(trim((string) $status));
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
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
            'certificate_id' => isset($row['certificate_id']) ? (int) $row['certificate_id'] : null,
            'number_reservation_id' => isset($row['number_reservation_id']) ? (int) $row['number_reservation_id'] : null,
            'model' => (string) ($row['model'] ?? '55'),
            'environment' => (int) ($row['environment'] ?? 2),
            'series' => (int) ($row['series'] ?? 0),
            'number' => (int) ($row['number'] ?? 0),
            'access_key' => trim((string) ($row['access_key'] ?? '')),
            'artifact_type' => strtoupper(trim((string) ($row['artifact_type'] ?? 'NFE'))),
            'status' => strtoupper(trim((string) ($row['status'] ?? 'XSD_VALID_OFFLINE'))),
            'schema_package' => trim((string) ($row['schema_package'] ?? 'nfe')),
            'schema_version' => trim((string) ($row['schema_version'] ?? '010e-v1.02')),
            'schema_checksum' => trim((string) ($row['schema_checksum'] ?? '')),
            'storage_reference' => trim((string) ($row['storage_reference'] ?? '')),
            'sha256' => trim((string) ($row['sha256'] ?? '')),
            'size_bytes' => (int) ($row['size_bytes'] ?? 0),
            'created_by' => (int) ($row['created_by'] ?? 0),
        ];

        if ($payload['establishment_id'] <= 0 || $payload['fiscal_document_id'] <= 0 || $payload['access_key'] === '' || $payload['storage_reference'] === '') {
            throw new RuntimeException('artifact payload invalid');
        }

        $sql = 'INSERT INTO fiscal_artifacts (
                    tenant_id, establishment_id, fiscal_document_id, fiscal_document_version,
                    certificate_id, number_reservation_id, model, environment, series, number,
                    access_key, artifact_type, status, schema_package, schema_version, schema_checksum,
                    storage_reference, sha256, size_bytes, created_by
                ) VALUES (
                    :tenant_id, :establishment_id, :fiscal_document_id, :fiscal_document_version,
                    :certificate_id, :number_reservation_id, :model, :environment, :series, :number,
                    :access_key, :artifact_type, :status, :schema_package, :schema_version, :schema_checksum,
                    :storage_reference, :sha256, :size_bytes, :created_by
                )';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($payload);
        } catch (PDOException $e) {
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'duplicate') || str_contains($msg, 'unique')) {
                throw new RuntimeException('duplicate artifact: ' . $e->getMessage(), 0, $e);
            }
            throw $e;
        }

        $id = (int) $this->pdo->lastInsertId();
        $record = $this->findById($id);
        if ($record === null) {
            throw new RuntimeException('artifact was not persisted');
        }

        return $record;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE fiscal_artifacts SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE tenant_id = :tenant_id AND id = :id');
        $stmt->execute([
            'status' => strtoupper(trim($status)),
            'tenant_id' => $this->tenantId,
            'id' => $id,
        ]);
        return $stmt->rowCount() > 0;
    }
}
