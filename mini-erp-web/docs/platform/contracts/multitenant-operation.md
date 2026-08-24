# Contrato — Operação Multi-tenant

## Objetivo

Representar uma operação administrativa de migração em múltiplos tenants do Control-Plane.

## Tabela principal

`platform_database_operations`

Campos obrigatórios:

- `operation_id`: identificador da operação
- `plan_id`: plano de simulação associado
- `admin_id`: administrador do Control-Plane
- `migration_id`: migration oficial executada
- `checksum`: SHA-256 do arquivo oficial
- `risk`: risco classificado
- `reason`: motivo da execução
- `created_at`: criação
- `started_at`: início
- `finished_at`: conclusão
- `status`: estado geral final

## Tabela por tenant

`platform_database_operation_targets`

Campos obrigatórios:

- `operation_id`
- `tenant_id`
- `db_name`
- `status`
- `backup_path`
- `backup_size`
- `backup_sha256`
- `started_at`
- `finished_at`
- `duration_ms`
- `validation_json`
- `error_message`

## Estados

Geral:

- `PENDING`
- `BACKING_UP`
- `BACKUP_OK`
- `BACKUP_FAILED`
- `LOCKED`
- `RUNNING`
- `SUCCESS`
- `FAILED`
- `VALIDATION_FAILED`
- `ALREADY_APPLIED`
- `BASELINE_INCLUDED`
- `BLOCKED`

Resultado final da operação:

- `SUCCESS`
- `PARTIAL`
- `FAILED`
- `BLOCKED`

## Regras de contrato

- `plan_id` deve ser válido e não expirado;
- `admin_id` deve ser do Control-Plane e `role = SUPER_ADMIN`;
- `operation_id` é único;
- `checksum` deve corresponder ao arquivo oficial no catálogo;
- `db_name` deve ser resolvido exclusivamente pelo MAIN;
- `backup` é obrigatório antes de qualquer write em tenant ready;
- `validation_json` só é preenchido após validação estrutural;
- `error_message` nunca deve mascarar a falha real.

## Integridade operacional

- nenhum formulário GET executa write;
- nenhum double submit executa mais de uma vez a mesma operação;
- `plan_id` e `operation_id` são os pontos de idempotência;
- a ação apenas grava em tenant permitidos pelo plano;
- toda operação com sucesso parcial deve deixar registro explícito do tenant que falhou.
