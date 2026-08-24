<?php

declare(strict_types=1);

namespace MiniErp\Services;

use InvalidArgumentException;

final readonly class PlatformTenantUserData
{
    public const ROLES = ['admin', 'user'];
    public const STATUSES = ['ativo', 'inativo'];

    private string $name;
    private string $email;
    private string $role;
    private string $status;

    public function __construct(string $name, string $email, string $role, string $status)
    {
        $this->name = trim($name);
        $this->email = strtolower(trim($email));
        $this->role = trim($role);
        $this->status = trim($status);
        if ($this->name === '') throw new InvalidArgumentException('Nome é obrigatório.');
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('E-mail inválido.');
        if (!in_array($this->role, self::ROLES, true)) throw new InvalidArgumentException('Perfil não permitido.');
        if (!in_array($this->status, self::STATUSES, true)) throw new InvalidArgumentException('Status não permitido.');
    }

    public static function fromArray(array $data): self
    {
        return new self(self::string($data, 'nome'), self::string($data, 'email'), self::string($data, 'role'), self::string($data, 'status'));
    }

    public function toArray(): array { return ['nome' => $this->name, 'email' => $this->email, 'role' => $this->role, 'status' => $this->status]; }
    public function email(): string { return $this->email; }
    private static function string(array $data, string $key): string { $value = $data[$key] ?? ''; if (!is_scalar($value)) throw new InvalidArgumentException('Campo inválido.'); return (string) $value; }
}
