<?php
declare(strict_types=1);
namespace MiniErp\Services;

use DomainException;
use InvalidArgumentException;
use PDO;

final class EmployeeAccessService
{
    public function __construct(private PDO $pdo, private int $tenantId, private int $actorId) {}

    public function assertActorCanManage(): void
    {
        $stmt=$this->pdo->prepare('SELECT role,tenant_id,status FROM usuarios WHERE id=? LIMIT 1');
        $stmt->execute([$this->actorId]);$actor=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!$actor||(int)$actor['tenant_id']!==$this->tenantId||$actor['status']!=='ativo'||!in_array(strtolower((string)$actor['role']),['admin','administrador'],true))throw new DomainException('Somente um administrador da empresa pode gerenciar acessos.');
    }

    public function findByEmail(string $email): ?array
    {
        if(trim($email)==='')return null;$stmt=$this->pdo->prepare("SELECT id,nome,email,role,status,cargo FROM usuarios WHERE tenant_id=? AND LOWER(email)=LOWER(?) AND role='user' AND cargo='funcionario' LIMIT 1");$stmt->execute([$this->tenantId,trim($email)]);return$stmt->fetch(PDO::FETCH_ASSOC)?:null;
    }

    public function create(string $name,string $email,string $password,string $confirmation): array
    {
        $this->assertActorCanManage();$name=trim($name);$email=strtolower(trim($email));
        if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Nome e e-mail válido são obrigatórios para criar o login.');
        $this->validatePassword($password,$confirmation);
        $check=$this->pdo->prepare('SELECT id FROM usuarios WHERE LOWER(email)=LOWER(?) LIMIT 1');$check->execute([$email]);if($check->fetch())throw new DomainException('Este e-mail já possui um login no ERP.');
        $stmt=$this->pdo->prepare("INSERT INTO usuarios(nome,email,senha,role,status,email_verified,cargo,company_id,tenant_id) VALUES(?,?,?,'user','ativo',1,'funcionario',?,?)");$stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$this->tenantId,$this->tenantId]);
        return$this->findByEmail($email)??throw new DomainException('Login criado, mas não pôde ser relido.');
    }

    public function resetPassword(int $accessId,string $password,string $confirmation): void
    {
        $this->assertActorCanManage();$this->validatePassword($password,$confirmation);$this->owned($accessId);$stmt=$this->pdo->prepare('UPDATE usuarios SET senha=? WHERE id=? AND tenant_id=?');$stmt->execute([password_hash($password,PASSWORD_DEFAULT),$accessId,$this->tenantId]);
    }

    public function setStatus(int $accessId,string $status): void
    {
        $this->assertActorCanManage();if(!in_array($status,['ativo','inativo'],true))throw new InvalidArgumentException('Status de acesso inválido.');if($accessId===$this->actorId&&$status==='inativo')throw new DomainException('Você não pode desativar o próprio acesso.');$this->owned($accessId);$stmt=$this->pdo->prepare('UPDATE usuarios SET status=? WHERE id=? AND tenant_id=?');$stmt->execute([$status,$accessId,$this->tenantId]);
    }

    private function owned(int $id): array
    {
        $stmt=$this->pdo->prepare("SELECT id,nome,email,role,status,cargo FROM usuarios WHERE id=? AND tenant_id=? AND role='user' AND cargo='funcionario' LIMIT 1");$stmt->execute([$id,$this->tenantId]);return$stmt->fetch(PDO::FETCH_ASSOC)?:throw new DomainException('Login de funcionário não encontrado nesta empresa.');
    }
    private function validatePassword(string $password,string $confirmation): void
    {
        if($password!==$confirmation)throw new InvalidArgumentException('A confirmação da senha não confere.');if(strlen($password)<8||!preg_match('/[A-Za-z]/',$password)||!preg_match('/\d/',$password))throw new InvalidArgumentException('A senha deve ter pelo menos 8 caracteres, incluindo letra e número.');
    }
}
