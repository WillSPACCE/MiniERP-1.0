<?php
declare(strict_types=1);

namespace MiniErp\Repositories;

use PDO;
use RuntimeException;

final class TenantAccessPolicyRepository
{
    public function __construct(private PDO $pdo) { $this->ensureSchema(); }

    private function ensureSchema(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS tenant_access_policies (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            access_mode VARCHAR(20) NOT NULL DEFAULT 'FULL',
            can_issue_fiscal TINYINT(1) NOT NULL DEFAULT 1,
            can_manage_users TINYINT(1) NOT NULL DEFAULT 1,
            can_use_financial TINYINT(1) NOT NULL DEFAULT 1,
            reason VARCHAR(500) NOT NULL,
            starts_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NULL,
            created_by BIGINT UNSIGNED NOT NULL,
            revoked_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY ix_tenant_policy_active (tenant_id, revoked_at, expires_at),
            KEY ix_tenant_policy_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS tenant_access_rules (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            rule_key VARCHAR(32) NOT NULL,
            rule_value VARCHAR(32) NOT NULL,
            reason VARCHAR(500) NOT NULL,
            expires_at DATETIME NULL,
            created_by BIGINT UNSIGNED NOT NULL,
            revoked_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY ix_tenant_rule_active (tenant_id, rule_key, revoked_at, expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function activeForTenant(int $tenantId): array
    {
        $s=$this->pdo->prepare("SELECT * FROM tenant_access_policies WHERE tenant_id=? AND revoked_at IS NULL AND starts_at<=NOW() AND (expires_at IS NULL OR expires_at>NOW()) ORDER BY id DESC LIMIT 1");
        $s->execute([$tenantId]);
        return $s->fetch(PDO::FETCH_ASSOC) ?: ['tenant_id'=>$tenantId,'access_mode'=>'FULL','can_issue_fiscal'=>1,'can_manage_users'=>1,'can_use_financial'=>1,'expires_at'=>null,'reason'=>''];
    }

    public function listTenantsWithPolicies(): array
    {
        return $this->pdo->query("SELECT t.id AS tenant_id,t.nome_fantasia,t.razao_social,t.cnpj,t.status,t.blocked,p.id AS policy_id,p.access_mode,p.can_issue_fiscal,p.can_manage_users,p.can_use_financial,p.reason,p.expires_at,p.created_at,a.name AS actor_name FROM tenants t LEFT JOIN tenant_access_policies p ON p.id=(SELECT p2.id FROM tenant_access_policies p2 WHERE p2.tenant_id=t.id AND p2.revoked_at IS NULL AND p2.starts_at<=NOW() AND (p2.expires_at IS NULL OR p2.expires_at>NOW()) ORDER BY p2.id DESC LIMIT 1) LEFT JOIN platform_admin_users a ON a.id=p.created_by ORDER BY t.nome_fantasia,t.id")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function history(int $limit=100): array
    {
        $s=$this->pdo->prepare("SELECT p.*,t.nome_fantasia,a.name AS actor_name FROM tenant_access_policies p JOIN tenants t ON t.id=p.tenant_id LEFT JOIN platform_admin_users a ON a.id=p.created_by ORDER BY p.id DESC LIMIT :lim");$s->bindValue(':lim',max(1,min(500,$limit)),PDO::PARAM_INT);$s->execute();return$s->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activeRules(int $tenantId): array
    {
        $s=$this->pdo->prepare("SELECT r.* FROM tenant_access_rules r WHERE r.tenant_id=? AND r.revoked_at IS NULL AND (r.expires_at IS NULL OR r.expires_at>NOW()) AND r.id=(SELECT r2.id FROM tenant_access_rules r2 WHERE r2.tenant_id=r.tenant_id AND r2.rule_key=r.rule_key AND r2.revoked_at IS NULL AND (r2.expires_at IS NULL OR r2.expires_at>NOW()) ORDER BY r2.id DESC LIMIT 1)");$s->execute([$tenantId]);$result=[];foreach($s->fetchAll(PDO::FETCH_ASSOC)as$row)$result[$row['rule_key']]=$row;return$result;
    }

    public function setRule(int $tenantId,string $key,string $value,?int $days,string $reason,int $actorId): int
    {
        $key=strtoupper(trim($key));if(!in_array($key,['ACCESS','FISCAL','USERS','FINANCIAL'],true))throw new RuntimeException('Regra inválida.');
        if($key==='ACCESS'){if(!in_array($value,['FULL','READ_ONLY','BLOCKED'],true))throw new RuntimeException('Valor de acesso inválido.');}elseif(!in_array($value,['0','1'],true))throw new RuntimeException('Valor da permissão inválido.');
        if(!in_array($days,[null,5,10,15,30],true))throw new RuntimeException('Prazo inválido.');if(trim($reason)==='')throw new RuntimeException('Informe o motivo da regra.');
        $expires=$days===null?null:date('Y-m-d H:i:s',time()+$days*86400);$this->pdo->beginTransaction();try{$this->pdo->prepare('UPDATE tenant_access_rules SET revoked_at=NOW() WHERE tenant_id=? AND rule_key=? AND revoked_at IS NULL')->execute([$tenantId,$key]);$s=$this->pdo->prepare('INSERT INTO tenant_access_rules(tenant_id,rule_key,rule_value,reason,expires_at,created_by) VALUES(?,?,?,?,?,?)');$s->execute([$tenantId,$key,$value,trim($reason),$expires,$actorId]);$id=(int)$this->pdo->lastInsertId();$this->pdo->commit();return$id;}catch(\Throwable$e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw$e;}
    }

    public function effectiveForTenant(int $tenantId): array
    {
        $policy=$this->activeForTenant($tenantId);$rules=$this->activeRules($tenantId);$access=$rules['ACCESS']??null;return ['tenant_id'=>$tenantId,'access_mode'=>$access['rule_value']??$policy['access_mode'],'can_issue_fiscal'=>(int)($rules['FISCAL']['rule_value']??$policy['can_issue_fiscal']),'can_manage_users'=>(int)($rules['USERS']['rule_value']??$policy['can_manage_users']),'can_use_financial'=>(int)($rules['FINANCIAL']['rule_value']??$policy['can_use_financial']),'rules'=>$rules,'reason'=>$access['reason']??($policy['reason']??''),'expires_at'=>$access['expires_at']??($policy['expires_at']??null)];
    }

    public function apply(int $tenantId,string $mode,bool $fiscal,bool $users,bool $financial,?int $days,string $reason,int $actorId): int
    {
        $mode=strtoupper(trim($mode));if(!in_array($mode,['FULL','READ_ONLY','BLOCKED'],true))throw new RuntimeException('Modo de acesso inválido.');
        if(!in_array($days,[null,5,10,15,30],true))throw new RuntimeException('Prazo inválido.');
        if(trim($reason)==='')throw new RuntimeException('Informe o motivo da alteração.');
        $this->pdo->beginTransaction();try{$this->pdo->prepare('UPDATE tenant_access_policies SET revoked_at=NOW() WHERE tenant_id=? AND revoked_at IS NULL')->execute([$tenantId]);$expires=$days===null?null:date('Y-m-d H:i:s',time()+$days*86400);$s=$this->pdo->prepare('INSERT INTO tenant_access_policies(tenant_id,access_mode,can_issue_fiscal,can_manage_users,can_use_financial,reason,expires_at,created_by) VALUES(?,?,?,?,?,?,?,?)');$s->execute([$tenantId,$mode,$fiscal?1:0,$users?1:0,$financial?1:0,trim($reason),$expires,$actorId]);$id=(int)$this->pdo->lastInsertId();$this->pdo->commit();return$id;}catch(\Throwable$e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw$e;}
    }
}
