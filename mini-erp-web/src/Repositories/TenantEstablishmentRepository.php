<?php

declare(strict_types=1);

namespace MiniErp\Repositories;

use MiniErp\Contracts\EstablishmentRepositoryContract;
use PDO;

final class TenantEstablishmentRepository implements EstablishmentRepositoryContract
{
    public function __construct(private PDO $connection) {}
    public function schemaAvailable(): bool
    {
        try { $this->connection->query('SELECT 1 FROM establishments WHERE 1 = 0'); return true; } catch (\Throwable) { return false; }
    }
    public function findPrimaryForTenant(int $tenantId): ?array
    {
        if (!$this->schemaAvailable()) return null;

        $stmt = $this->connection->prepare('SELECT * FROM establishments WHERE tenant_id = :tenant_id AND is_primary = 1 ORDER BY id ASC LIMIT 1');
        $stmt->execute(['tenant_id' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            return $row;
        }

        $fallback = $this->connection->prepare('SELECT * FROM establishments WHERE tenant_id = :tenant_id ORDER BY is_primary DESC, id ASC LIMIT 1');
        $fallback->execute(['tenant_id' => $tenantId]);
        return $fallback->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public function savePrimaryForTenant(int $tenantId, array $data): array
    {
        $existing = $this->findPrimaryForTenant($tenantId);
        $data['tenant_id'] = $tenantId;
        if ($existing === null) {
            $columns = array_keys($data); $params = array_map(static fn ($c) => ':' . $c, $columns);
            $stmt = $this->connection->prepare('INSERT INTO establishments (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', $params) . ')');
        } else {
            $sets = array_map(static fn ($c) => "`{$c}` = :{$c}", array_keys($data));
            $stmt = $this->connection->prepare('UPDATE establishments SET ' . implode(',', $sets) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :row_id AND tenant_id = :scope_tenant_id');
            $data['row_id'] = (int) $existing['id']; $data['scope_tenant_id'] = $tenantId;
        }
        $stmt->execute($data);
        $saved=$this->findPrimaryForTenant($tenantId)??[];if($saved)$this->inheritGlobalCfops($tenantId,(int)$saved['id']);return$saved;
    }
    private function inheritGlobalCfops(int$tenantId,int$establishmentId):void
    {
        try{$defaults=$this->connection->query('SELECT entry_internal_cfop,entry_interstate_cfop,exit_internal_cfop,exit_interstate_cfop FROM mini_erp.platform_fiscal_defaults WHERE id=1')->fetch(PDO::FETCH_ASSOC);if(!$defaults)return;$map=['ENTRY_INTERNAL'=>$defaults['entry_internal_cfop'],'ENTRY_INTERSTATE'=>$defaults['entry_interstate_cfop'],'EXIT_INTERNAL'=>$defaults['exit_internal_cfop'],'EXIT_INTERSTATE'=>$defaults['exit_interstate_cfop']];$descriptions=['1102'=>['Compra para comercialização','Entrada','Dentro do Estado'],'2102'=>['Compra para comercialização','Entrada','Interestadual'],'5102'=>['Venda de mercadoria adquirida ou recebida de terceiros','Saída','Dentro do Estado'],'6102'=>['Venda de mercadoria adquirida ou recebida de terceiros','Saída','Interestadual']];foreach(array_unique($map)as$code){if(!isset($descriptions[$code]))continue;[$description,$nature,$application]=$descriptions[$code];$this->connection->prepare("INSERT INTO cfops(codigo,descricao,natureza,aplicacao,status) VALUES(?,?,?,?,'ativo') ON DUPLICATE KEY UPDATE codigo=VALUES(codigo)")->execute([$code,$description,$nature,$application]);}$insert=$this->connection->prepare('INSERT IGNORE INTO establishment_cfop_defaults(tenant_id,establishment_id,operation_context,cfop) VALUES(?,?,?,?)');foreach($map as$context=>$cfop)$insert->execute([$tenantId,$establishmentId,$context,$cfop]);}catch(\Throwable){/* Tenant antigo sem migration: cadastro do estabelecimento continua disponível. */}
    }
}
