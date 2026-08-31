<?php
declare(strict_types=1);

namespace MiniErp\Services;

use DomainException;
use PDO;

final class TenantAccessRegistrationService
{
    public function __construct(private PDO $main, private string $configPath) {}

    public function register(string $slug, array $data): array
    {
        $slug = strtolower(trim($slug));
        $name = trim((string)($data['name'] ?? ''));
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $phone = preg_replace('/[^0-9+]/', '', (string)($data['phone'] ?? ''));
        $provider = strtolower(trim((string)($data['provider'] ?? 'password')));
        $subject = trim((string)($data['provider_subject'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($slug === '' || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Informe nome, e-mail válido e a empresa correta.');
        }
        if ($provider === 'password' && strlen($password) < 8) {
            throw new DomainException('A senha precisa ter pelo menos 8 caracteres.');
        }
        if (!in_array($provider, ['password', 'google', 'facebook', 'linkedin'], true)) {
            throw new DomainException('Forma de cadastro inválida.');
        }

        $tenantStmt = $this->main->prepare('SELECT id, db_name, status, blocked FROM tenants WHERE slug=:slug LIMIT 1');
        $tenantStmt->execute(['slug' => $slug]);
        $tenant = $tenantStmt->fetch(PDO::FETCH_ASSOC);
        $tenantId = (int)($tenant['id'] ?? 0);
        $expectedDb = 'mini_erp_tenant_' . $tenantId;
        if (!$tenant || $tenantId < 1 || !empty($tenant['blocked']) || !in_array(strtolower((string)$tenant['status']), ['ativo','ativa','active'], true) || !hash_equals($expectedDb, (string)$tenant['db_name'])) {
            throw new DomainException('Empresa indisponível para novos cadastros.');
        }

        $tenantPdo = $this->tenantConnection($expectedDb);
        $person = $tenantPdo->prepare('SELECT id FROM clientes WHERE LOWER(email)=LOWER(:email) ORDER BY id LIMIT 1');
        $person->execute(['email' => $email]);
        $personId = (int)($person->fetchColumn() ?: 0);
        if ($personId < 1) {
            $insertPerson = $tenantPdo->prepare("INSERT INTO clientes(nome,email,telefone,fone_principal,status,tipo_pessoa,pessoa_fisica,data_cadastro,person_type,role_customer,data) VALUES(:nome,:email,:telefone,:fone,'ativo','cliente','sim',CURRENT_DATE,'PF',1,:data)");
            $insertPerson->execute(['nome'=>$name,'email'=>$email,'telefone'=>$phone,'fone'=>$phone,'data'=>json_encode(['registration_source'=>$provider], JSON_UNESCAPED_UNICODE)]);
            $personId = (int)$tenantPdo->lastInsertId();
        }

        $hash = $provider === 'password' ? password_hash($password, PASSWORD_DEFAULT) : password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $this->main->beginTransaction();
        try {
            $find = $this->main->prepare('SELECT id,status FROM usuarios WHERE tenant_id=:tenant AND LOWER(email)=LOWER(:email) LIMIT 1 FOR UPDATE');
            $find->execute(['tenant'=>$tenantId,'email'=>$email]);
            $existing = $find->fetch(PDO::FETCH_ASSOC);
            if ($existing && strtolower((string)$existing['status']) === 'ativo') {
                $userId=(int)$existing['id'];
                if ($provider === 'password') throw new DomainException('Este e-mail já possui acesso ativo nesta empresa. Use a tela Entrar.');
                $identity=$this->main->prepare('INSERT INTO user_oauth_identities(user_id,tenant_id,provider,provider_subject) VALUES(:user,:tenant,:provider,:subject) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id),tenant_id=VALUES(tenant_id),updated_at=CURRENT_TIMESTAMP');
                $identity->execute(['user'=>$userId,'tenant'=>$tenantId,'provider'=>$provider,'subject'=>$subject]);
                $this->main->commit();
                return ['tenant_id'=>$tenantId,'person_id'=>$personId,'user_id'=>$userId,'active'=>true];
            }
            if ($existing) {
                $userId=(int)$existing['id'];
                $sql='UPDATE usuarios SET nome=:nome,pessoa_id=:pessoa,status="pendente",email_verified=:verified';
                $params=['nome'=>$name,'pessoa'=>$personId,'verified'=>$provider==='password'?0:1,'id'=>$userId];
                if ($provider === 'password') {$sql.=',senha=:senha';$params['senha']=$hash;}
                $this->main->prepare($sql.' WHERE id=:id')->execute($params);
            } else {
                $insert=$this->main->prepare("INSERT INTO usuarios(nome,email,senha,role,status,email_verified,cargo,tenant_id,company_id,pessoa_id) VALUES(:nome,:email,:senha,'user','pendente',:verified,'funcionario',:tenant,:tenant,:pessoa)");
                $insert->execute(['nome'=>$name,'email'=>$email,'senha'=>$hash,'verified'=>$provider==='password'?0:1,'tenant'=>$tenantId,'pessoa'=>$personId]);
                $userId=(int)$this->main->lastInsertId();
            }
            if ($provider !== 'password') {
                $identity=$this->main->prepare('INSERT INTO user_oauth_identities(user_id,tenant_id,provider,provider_subject) VALUES(:user,:tenant,:provider,:subject) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id),tenant_id=VALUES(tenant_id),updated_at=CURRENT_TIMESTAMP');
                $identity->execute(['user'=>$userId,'tenant'=>$tenantId,'provider'=>$provider,'subject'=>$subject]);
            }
            $this->upsertTenantUser($tenantPdo, $name, $email, $hash, $personId, $provider !== 'password');
            $this->main->commit();
        } catch (\Throwable $error) {
            if ($this->main->inTransaction()) $this->main->rollBack();
            throw $error;
        }
        return ['tenant_id'=>$tenantId,'person_id'=>$personId,'user_id'=>$userId,'active'=>false];
    }

    private function tenantConnection(string $database): PDO
    {
        $config=require $this->configPath;$db=$config['db'];
        return new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',$db['host'],$db['port'],$database),$db['username'],$db['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    }

    private function upsertTenantUser(PDO $pdo,string $name,string $email,string $hash,int $personId,bool $verified): void
    {
        $find=$pdo->prepare('SELECT id FROM usuarios WHERE LOWER(email)=LOWER(:email) LIMIT 1');$find->execute(['email'=>$email]);$id=(int)($find->fetchColumn()?:0);
        if($id>0){$pdo->prepare("UPDATE usuarios SET nome=:nome,pessoa_id=:pessoa,status='pendente',email_verified=:verified WHERE id=:id")->execute(['nome'=>$name,'pessoa'=>$personId,'verified'=>$verified?1:0,'id'=>$id]);return;}
        $pdo->prepare("INSERT INTO usuarios(nome,email,senha,role,status,email_verified,cargo,pessoa_id) VALUES(:nome,:email,:senha,'user','pendente',:verified,'funcionario',:pessoa)")->execute(['nome'=>$name,'email'=>$email,'senha'=>$hash,'verified'=>$verified?1:0,'pessoa'=>$personId]);
    }
}
