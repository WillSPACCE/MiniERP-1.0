# PLATFORM-01-T10R — Integração da autenticação tenant ao ERP legado

## Correção aplicada

A T10A estabeleceu autenticação e isolamento corretos, mas também criou uma interface reduzida paralela em `/public/erp/`. A T10R remove essa duplicação visual: `/erp/` agora é somente a camada de login/redirecionamento, enquanto o usuário autenticado volta ao dashboard histórico em `/?page=dashboard`.

## Arquitetura final

O `ErpLegacyBootstrap` é a única ponte. Ele relê `erp_user_id` e `erp_tenant_id` no MAIN, revalida usuário, tenant, status, bloqueio e provisionamento, recompõe `TenantContext`, solicita o PDO ao `TenantConnectionResolver` e confirma com `SELECT DATABASE()` que o banco é exatamente `mini_erp_tenant_{tenant_id}`.

Somente depois disso a ponte deriva as chaves legadas `user_id` e `tenant_id`. `current_company_id` é removido e nunca é autoridade. O PDO já resolvido é instalado por `Database::useResolvedTenantConnection()` antes da construção do `Repository`. Nesse caminho, o construtor do repositório não executa os antigos ajustes automáticos de schema.

`db_name` não vem de GET, POST ou sessão e `Database::setTenantDbName()` não é usado pela ponte. O fluxo seguro também impede a resolução de slug da URL pelo legado, troca de tenant e fallback para tenant 1.

## Fluxo

Painel → `/erp/login.php?empresa={slug}` → autenticação MAIN → sessão ERP → `ErpLegacyBootstrap` → `TenantContext` → `TenantConnectionResolver` → PDO dedicado → `Database`/`Repository` legado → `/?page=dashboard`.

O logout remove a identidade ERP e as chaves de compatibilidade, mas preserva `platform_user_id`.

## Riscos e dívida técnica

- `public/index.php` continua monolítico e executa consultas de várias telas antecipadamente.
- `Repository` ainda depende de `tenant_id` da sessão em diversos métodos.
- Os CRUDs possuem compatibilidades de coluna e comportamentos legados que precisam de validação vertical.
- O login antigo `/login.php` continua preservado, mas não é o login tenant oficial.
- `Database::setTenantDbName()` permanece disponível para fluxos legados fora da ponte e deverá ser progressivamente eliminado.

Próximos incrementos: ERP-CRUD-01 a ERP-CRUD-07, tratados isoladamente.
