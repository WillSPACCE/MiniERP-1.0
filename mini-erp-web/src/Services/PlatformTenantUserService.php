<?php

declare(strict_types=1);

namespace MiniErp\Services;

use DomainException;
use InvalidArgumentException;
use MiniErp\Context\AdministrativeContext;
use MiniErp\Context\AuthenticatedPlatformAdmin;
use MiniErp\Context\SelectedTenant;
use MiniErp\Contracts\PlatformAdminAuthorizerContract;
use MiniErp\Contracts\PlatformTenantUserRepositoryContract;

final class PlatformTenantUserService
{
    public function __construct(private PlatformTenantUserRepositoryContract $users, private PlatformAdminAuthorizerContract $authorizer)
    {
    }

    public function context(AuthenticatedPlatformAdmin $actor, array $tenant): AdministrativeContext
    {
        if (!$this->authorizer->isAuthorized($actor)) throw new DomainException('Operação não autorizada.');
        $tenantId = (int) ($tenant['tenant_id'] ?? 0);
        if ($tenantId < 1) throw new DomainException('Empresa não encontrada.');
        $status = strtolower(trim((string) ($tenant['status'] ?? '')));
        if (!in_array($status, ['ativo', 'ativa', 'parcialmente_bloqueada', 'bloqueada'], true) || trim((string) ($tenant['db_name'] ?? '')) === '') {
            throw new DomainException('Empresa ainda não está pronta para usuários.');
        }
        return new AdministrativeContext($actor->getUserId(), new SelectedTenant($actor->getUserId(), $tenantId, true));
    }

    public function list(AdministrativeContext $context): array { return $this->users->listForTenant($context->getSelectedTenantId()); }

    public function find(AdministrativeContext $context, int $userId): array
    {
        $user = $this->users->findForTenant($context->getSelectedTenantId(), $userId);
        if ($user === null) throw new DomainException('Usuário não encontrado ou não pertence a esta empresa.');
        return $user;
    }

    public function create(AdministrativeContext $context, PlatformTenantUserData $data, string $password, string $confirmation): array
    {
        $this->validatePassword($password, $confirmation);
        if ($this->users->emailExists($data->email())) throw new DomainException('Este e-mail já está cadastrado.');
        return $this->users->createForTenant($context->getSelectedTenantId(), array_merge($data->toArray(), ['senha' => password_hash($password, PASSWORD_DEFAULT)]));
    }

    public function update(AdministrativeContext $context, int $userId, PlatformTenantUserData $data): void
    {
        $this->find($context, $userId);
        if ($this->users->emailExists($data->email(), $userId)) throw new DomainException('Este e-mail já está cadastrado.');
        if (!$this->users->updateForTenant($context->getSelectedTenantId(), $userId, $data->toArray())) throw new DomainException('Usuário não encontrado ou não pertence a esta empresa.');
    }

    public function setStatus(AdministrativeContext $context, int $userId, string $status): void
    {
        if (!in_array($status, PlatformTenantUserData::STATUSES, true)) throw new InvalidArgumentException('Status não permitido.');
        $this->find($context, $userId);
        if (!$this->users->setStatusForTenant($context->getSelectedTenantId(), $userId, $status)) throw new DomainException('Usuário não encontrado ou não pertence a esta empresa.');
    }

    public function resetPassword(AdministrativeContext $context, int $userId, string $password, string $confirmation): void
    {
        $this->find($context, $userId);
        $this->validatePassword($password, $confirmation);
        if (!$this->users->setPasswordForTenant($context->getSelectedTenantId(), $userId, password_hash($password, PASSWORD_DEFAULT))) throw new DomainException('Usuário não encontrado ou não pertence a esta empresa.');
    }

    private function validatePassword(string $password, string $confirmation): void
    {
        if ($password !== $confirmation) throw new InvalidArgumentException('A confirmação da senha não confere.');
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException('A senha deve ter pelo menos 8 caracteres, incluindo letra e número.');
        }
    }
}
