<?php

declare(strict_types=1);

namespace MiniErp\Services;

use InvalidArgumentException;

final class CreateUserForTenantRequest
{
    private string $nome;
    private string $email;
    private string $password;
    private ?int $companyId;
    private string $role;
    private string $status;
    private string $avatar;
    private string $permissions;
    private string $cargo;

    public function __construct(
        string $nome,
        string $email,
        string $password,
        ?int $companyId = null,
        string $role = 'user',
        string $status = 'ativo',
        string $avatar = '',
        string $permissions = '',
        string $cargo = 'funcionario'
    ) {
        $this->nome = trim($nome);
        $this->email = trim($email);
        $this->password = $password;
        $this->companyId = $companyId;
        $this->role = trim($role);
        $this->status = trim($status);
        $this->avatar = trim($avatar);
        $this->permissions = trim($permissions);
        $this->cargo = trim($cargo);

        if ($this->nome === '') {
            throw new InvalidArgumentException('User name is required.');
        }

        if ($this->email === '' || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid user email is required.');
        }

        if ($this->password === '') {
            throw new InvalidArgumentException('Password is required.');
        }

        if ($this->companyId !== null && $this->companyId < 1) {
            throw new InvalidArgumentException('companyId must be a positive integer when provided.');
        }
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getCompanyId(): ?int
    {
        return $this->companyId;
    }

    public function getRole(): string
    {
        return $this->role === '' ? 'user' : $this->role;
    }

    public function getStatus(): string
    {
        return $this->status === '' ? 'ativo' : $this->status;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }

    public function getPermissions(): string
    {
        return $this->permissions;
    }

    public function getCargo(): string
    {
        return $this->cargo === '' ? 'funcionario' : $this->cargo;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPersistencePayload(int $targetTenantId, string $passwordHash): array
    {
        $payload = [
            'nome' => $this->nome,
            'email' => $this->email,
            'senha' => $passwordHash,
            'role' => $this->getRole(),
            'status' => $this->getStatus(),
            'avatar' => $this->avatar,
            'permissions' => $this->permissions,
            'cargo' => $this->getCargo(),
            'tenant_id' => $targetTenantId,
        ];

        if ($this->companyId !== null) {
            $payload['company_id'] = $this->companyId;
        }

        return $payload;
    }
}
