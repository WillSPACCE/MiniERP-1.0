<?php

declare(strict_types=1);

namespace MiniErp\Services;

use DomainException;
use MiniErp\Context\AuthenticatedPlatformAdmin;
use MiniErp\Contracts\PlatformAdminAuthorizerContract;
use MiniErp\Contracts\PlatformTenantRepositoryContract;
use MiniErp\Contracts\TenantDatabaseProvisionerContract;
use Throwable;

final class ProvisionPlatformTenantService
{
    public function __construct(
        private PlatformTenantRepositoryContract $repository,
        private TenantDatabaseProvisionerContract $provisioner,
        private PlatformAdminAuthorizerContract $authorizer,
        private TenantSchemaTemplate $schemaTemplate
    ) {
    }

    public function databaseNameFor(int $tenantId): string
    {
        return PlatformTenantDatabaseName::fromTenantId($tenantId);
    }

    /** @return array{tenant_id: int, db_name: string, status: string} */
    public function provision(AuthenticatedPlatformAdmin $actor, int $tenantId): array
    {
        if (!$this->authorizer->isAuthorized($actor)) {
            throw new DomainException('Provisionamento não autorizado.');
        }

        $tenant = $this->repository->findById($tenantId);
        if ($tenant === null) {
            throw new DomainException('Empresa não encontrada.');
        }
        if ((string) ($tenant['status'] ?? '') !== 'cadastrada') {
            throw new DomainException('O estado atual não permite provisionamento.');
        }
        if (!empty($tenant['blocked'])) {
            throw new DomainException('Empresa bloqueada não pode ser provisionada.');
        }
        if (trim((string) ($tenant['db_name'] ?? '')) !== '') {
            throw new DomainException('Empresa já possui ambiente dedicado.');
        }

        $databaseName = $this->databaseNameFor($tenantId);
        $schemaVersion = $this->schemaTemplate->currentVersion();
        $this->schemaTemplate->currentSchemaPath();
        if (!$this->repository->supportsSchemaVersion()) {
            throw new DomainException('Provisionamento indisponível até a migration de schema_version ser aplicada no MAIN.');
        }
        if ($this->provisioner->databaseExists($databaseName)) {
            throw new DomainException('Conflito: o banco dedicado derivado já existe. Nenhuma adoção automática foi feita.');
        }
        if (!$this->repository->beginProvisioning($tenantId)) {
            throw new DomainException('A empresa mudou de estado; o provisionamento foi cancelado com segurança.');
        }

        try {
            $this->provisioner->createDatabase($databaseName);
            $this->provisioner->installSchema($databaseName, $schemaVersion);
            if (!$this->provisioner->validateSchema($databaseName, $schemaVersion)) {
                throw new DomainException('A estrutura criada não passou na validação.');
            }
            if (!$this->repository->completeProvisioning($tenantId, $databaseName, $schemaVersion)) {
                throw new DomainException('O ambiente foi criado, mas o MAIN não pôde ser finalizado com segurança.');
            }
        } catch (Throwable $exception) {
            throw new DomainException(
                'Provisionamento incompleto. A empresa não foi ativada; verifique o banco derivado antes de nova tentativa.',
                0,
                $exception
            );
        }

        return ['tenant_id' => $tenantId, 'db_name' => $databaseName, 'status' => 'ativa', 'schema_version' => $schemaVersion];
    }
}
