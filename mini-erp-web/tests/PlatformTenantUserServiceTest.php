<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Context/AuthenticatedPlatformAdmin.php';
require_once __DIR__ . '/../src/Context/SelectedTenant.php';
require_once __DIR__ . '/../src/Context/AdministrativeContext.php';
require_once __DIR__ . '/../src/Contracts/PlatformAdminAuthorizerContract.php';
require_once __DIR__ . '/../src/Contracts/PlatformTenantUserRepositoryContract.php';
require_once __DIR__ . '/../src/Authorization/ConfiguredPlatformAdminAuthorizer.php';
require_once __DIR__ . '/../src/Services/PlatformTenantUserData.php';
require_once __DIR__ . '/../src/Services/PlatformTenantUserService.php';

use MiniErp\Authorization\ConfiguredPlatformAdminAuthorizer;
use MiniErp\Context\AuthenticatedPlatformAdmin;
use MiniErp\Contracts\PlatformTenantUserRepositoryContract;
use MiniErp\Services\PlatformTenantUserData;
use MiniErp\Services\PlatformTenantUserService;

function userAssert(bool $condition, string $message): void { if (!$condition) { fwrite(STDERR, "ASSERTION FAILED: {$message}\n"); exit(1); } }

final class TenantUserRepositoryFake implements PlatformTenantUserRepositoryContract
{
    public array $rows = []; public ?string $lastPlaintext = null; private int $nextId = 1;
    public function listForTenant(int $tenantId): array { return array_values(array_filter($this->rows, fn(array $r): bool => $r['tenant_id'] === $tenantId)); }
    public function findForTenant(int $tenantId, int $userId): ?array { $row=$this->rows[$userId]??null; return $row!==null&&$row['tenant_id']===$tenantId?$row:null; }
    public function emailExists(string $email, ?int $exceptUserId = null): bool { foreach($this->rows as $id=>$row)if(strtolower($row['email'])===strtolower($email)&&$id!==$exceptUserId)return true;return false; }
    public function createForTenant(int $tenantId, array $data): array { $id=$this->nextId++;$this->rows[$id]=array_merge($data,['id'=>$id,'tenant_id'=>$tenantId,'company_id'=>$tenantId]);return $this->rows[$id]; }
    public function updateForTenant(int $tenantId,int $userId,array $data):bool{if($this->findForTenant($tenantId,$userId)===null)return false;$this->rows[$userId]=array_merge($this->rows[$userId],$data);return true;}
    public function setStatusForTenant(int $tenantId,int $userId,string $status):bool{if($this->findForTenant($tenantId,$userId)===null)return false;$this->rows[$userId]['status']=$status;return true;}
    public function setPasswordForTenant(int $tenantId,int $userId,string $passwordHash):bool{if($this->findForTenant($tenantId,$userId)===null)return false;$this->rows[$userId]['senha']=$passwordHash;return true;}
}

$repository=new TenantUserRepositoryFake();$actor=new AuthenticatedPlatformAdmin(50,'platform@example.test','Admin');$service=new PlatformTenantUserService($repository,new ConfiguredPlatformAdminAuthorizer([50]));
$tenantA=['tenant_id'=>14,'status'=>'ativa','blocked'=>0,'db_name'=>'mini_erp_tenant_14'];$tenantB=['tenant_id'=>15,'status'=>'ativa','blocked'=>0,'db_name'=>'mini_erp_tenant_15'];$contextA=$service->context($actor,$tenantA);$contextB=$service->context($actor,$tenantB);
$_SESSION['tenant_id']=71;$_SESSION['current_company_id']=81;
$createdA=$service->create($contextA,new PlatformTenantUserData(' João ','JOAO@EXAMPLE.TEST','admin','ativo'),'Senha123','Senha123');
$createdB=$service->create($contextB,new PlatformTenantUserData('Maria','maria@example.test','user','ativo'),'Senha456','Senha456');
userAssert($createdA['tenant_id']===14&&$createdA['company_id']===14,'tenant is derived from administrative context and company_id only mirrors it');
userAssert($createdA['email']==='joao@example.test','email is normalized');
userAssert($createdA['senha']!=='Senha123'&&password_verify('Senha123',$createdA['senha']),'password is hashed');
userAssert(count($service->list($contextA))===1&&$service->list($contextA)[0]['id']===$createdA['id'],'tenant A list excludes tenant B');
try{$service->find($contextA,$createdB['id']);userAssert(false,'cross-tenant find rejected');}catch(DomainException){}
try{$service->update($contextA,$createdB['id'],new PlatformTenantUserData('X','x@example.test','user','ativo'));userAssert(false,'cross-tenant edit rejected');}catch(DomainException){}
try{$service->setStatus($contextA,$createdB['id'],'inativo');userAssert(false,'cross-tenant status rejected');}catch(DomainException){}
try{$service->resetPassword($contextA,$createdB['id'],'Nova1234','Nova1234');userAssert(false,'cross-tenant password reset rejected');}catch(DomainException){}
$service->update($contextA,$createdA['id'],new PlatformTenantUserData('João Atualizado','novo@example.test','user','inativo'));userAssert($repository->rows[$createdA['id']]['tenant_id']===14,'edit cannot move tenant');
$service->setStatus($contextA,$createdA['id'],'ativo');userAssert($repository->rows[$createdA['id']]['status']==='ativo','status activation works');
$service->resetPassword($contextA,$createdA['id'],'Nova1234','Nova1234');userAssert(password_verify('Nova1234',$repository->rows[$createdA['id']]['senha']),'password reset hashes value');
try{$service->create($contextA,new PlatformTenantUserData('Dup','maria@example.test','user','ativo'),'Senha123','Senha123');userAssert(false,'global duplicate email rejected');}catch(DomainException){}
try{new PlatformTenantUserData('Bad','not-email','user','ativo');userAssert(false,'invalid email rejected');}catch(InvalidArgumentException){}
try{$service->context($actor,['tenant_id'=>16,'status'=>'cadastrada','db_name'=>null]);userAssert(false,'unprovisioned tenant rejected');}catch(DomainException){}
$blocked=$service->context($actor,['tenant_id'=>17,'status'=>'bloqueada','blocked'=>1,'db_name'=>'mini_erp_tenant_17']);userAssert($blocked->getSelectedTenantId()===17,'blocked tenant remains administrable');
try{(new PlatformTenantUserService($repository,new ConfiguredPlatformAdminAuthorizer([])))->context($actor,$tenantA);userAssert(false,'unauthorized platform actor rejected');}catch(DomainException){}
userAssert($_SESSION['tenant_id']===71&&$_SESSION['current_company_id']===81,'ERP session remains unchanged');
echo "PlatformTenantUserService OK\n";
