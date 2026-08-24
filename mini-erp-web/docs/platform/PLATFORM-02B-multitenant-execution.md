# PLATFORM-02B — Execução segura de migrations Multi-tenant

## Escopo

A operação multi-tenant foi convertida em um fluxo administrativo controlado do Control-Plane, sem SQL livre e sem Writing Mode no Database Manager.

Regras de execução:

- identidade administrativa proveniente de `platform_admin_users.id`;
- somente `SUPER_ADMIN` pode executar migrations;
- somente arquivos oficiais do diretório `migrations/` entram no catálogo;
- dry-run obrigatório antes de qualquer write;
- `plan_id` expira em 600 segundos;
- backup individual por tenant antes de write;
- lock por tenant para impedir concorrência;
- audit em `platform_admin_audit_log` com ações do fluxo;
- validação pós-execução antes de registrar sucesso;
- histórico e detalhamento persistidos em tabelas do MAIN.

## Autorização

O runtime do painel usa a sessão administrativa do Control-Plane e nunca `usuarios.id` ou contexto ERP. O fluxo exige identidade ativa e `role = SUPER_ADMIN`.

O método de autenticação foi mantido estritamente no MAIN, consultando `platform_admin_users` e não qualquer tabela de ERP.

## Catalogação

O catálogo oficial é construído a partir dos arquivos `.sql` em `migrations/` e inclui:

- `migration_id`
- `filename`
- `checksum`
- `target` (`MAIN` ou `TENANT`)
- `risk` (`SAFE_ADDITIVE`, `STRUCTURAL`, `DESTRUCTIVE`, `MANUAL_REVIEW`)
- `dependencies`
- `tables_affected`
- `requires_backup`
- `transaction_mode`
- `schema_version_from` / `schema_version_to`

A interface só expõe migrações com `target = TENANT`.

## Pré-validação

Antes do dry-run, o precheck valida:

- `migration.target === TENANT`;
- `risk` permitido;
- `db_name` canônico do tenant e não arbitrário;
- banco físico existente;
- banco pertence ao tenant resolvido no MAIN;
- tenant ativo e sem bloqueio;
- checksum do arquivo e do catalog;
- dependências históricas;
- estado de baseline e `BASELINE_INCLUDED` quando aplicável;
- incompatibilidade de schema e `SCHEMA_INCOMPATIBLE`.

## Planner e plan_id

O plano é criado por `MultiTenantMigrationService::simulate()` e persiste em `platform_migration_plans`.

A estrutura inclui:

- `plan_id`
- `admin_id`
- `migration_id`
- `checksum`
- `tenant_ids_json`
- `simulation_json`
- `created_at`
- `expires_at`

A execução real só é permitida com `plan_id` válido e dentro do TTL.

## Execução real

A operação real usa `MultiTenantMigrationExecutor` em fluxo sequencial, tenant por tenant.

O executor:

1. revalida o tenant e a migration no backend;
2. cria backup individual em `backups/MULTITENANT/{operation_id}/`;
3. grava status `BACKING_UP` até backup concluído;
4. cria LOCK por tenant/operação;
5. executa a migration do arquivo oficial;
6. valida estrutura pós-execução;
7. registra `tenant_schema_migrations` com `source = PLATFORM_MULTI_TENANT`;
8. encerra com status `SUCCESS`, `VALIDATION_FAILED`, `FAILED`, `BACKUP_FAILED` ou `BLOCKED`.

## Segurança e política de risco

- `SAFE_ADDITIVE`: permitido após dry-run e backup;
- `STRUCTURAL`: apenas quando explicitamente aprovado no catálogo;
- `DESTRUCTIVE`: bloqueado;
- `MANUAL_REVIEW`: bloqueado.

A aplicação não aceita SQL livre, upload de `.sql`, path arbitrário, edição pelo navegador e nem `db_name` vindo do POST/GET.

## Auditoria

Os eventos do fluxo são persistidos em `platform_admin_audit_log` com ações como:

- `MULTITENANT_DRY_RUN`
- `MULTITENANT_EXECUTION_REQUESTED`
- `MULTITENANT_BACKUP_CREATED`
- `MULTITENANT_MIGRATION_STARTED`
- `MULTITENANT_MIGRATION_SUCCESS`
- `MULTITENANT_MIGRATION_FAILED`
- `MULTITENANT_OPERATION_COMPLETED`
- `MULTITENANT_OPERATION_PARTIAL`

## Estados de operação e tenant

Operação:

- `PENDING`, `BACKING_UP`, `BACKUP_OK`, `BACKUP_FAILED`, `LOCKED`, `RUNNING`, `SUCCESS`, `FAILED`, `VALIDATION_FAILED`, `ALREADY_APPLIED`, `BASELINE_INCLUDED`, `BLOCKED`

Tenants:

- `READY`, `ALREADY_APPLIED`, `BASELINE_INCLUDED`, `DEPENDENCY_MISSING`, `CHECKSUM_MISMATCH`, `SCHEMA_INCOMPATIBLE`, `TARGET_MISMATCH`, `BLOCKED`

O resultado geral pode ser `SUCCESS`, `PARTIAL`, `FAILED` ou `BLOCKED`.

## Histórico e relatório

A UI preserva a tela de Operações Multi-tenant com:

- lista de empresas;
- seleção de migração; 
- botão `SIMULAR`;
- resultado por tenant;
- confirmação textual para `STRUCTURAL`;
- botão `EXECUTAR OPERAÇÃO`;
- histórico de operações;
- visualização de detalhe;
- relatório em JSON/TXT sem segredos.

## Validação executada

A validação funcional foi executada com o teste MariaDB focado:

- operação de dry-run em dois tenants temporários;
- backup individual;
- lock por tenant;
- execução sequencial;
- registro em `tenant_schema_migrations`;
- auditoria administrativa;
- cenário `PARTIAL` com um tenant bloqueado;
- simulação de `CHECKSUM_MISMATCH` e `DEPENDENCY_MISSING`;
- bloqueio de `DESTRUCTIVE`;
- rejeição de `MAIN` no executor multi-tenant;
- operação de double submit e lock concorrente.

## Observações finais

- `Database Manager` continua `READ-ONLY`.
- Tenants `5` e `14` não receberam writes de validação para esta task e ficam protegidos.
- `mini_erp`, `mysql`, `information_schema`, `performance_schema` e `sys` são bloqueados explicitamente pelo executor.
- Testes de escrita para garantia do fluxo usam bancos `TEST_ONLY` temporários e são removidos após o cleanup.
