# FISCAL-01A — Aceitação física do cadastro fiscal

Status: concluída para o tenant local 14 em 21/08/2026.

- Migration auditada e aplicada exclusivamente em `mini_erp_tenant_14` após backup.
- Schema, colunas e índices conferidos no MariaDB 10.4.32.
- Round-trip de todos os campos executado com fixture transacional e rollback.
- Painel autenticado validado por HTTP; ERP e Painel usam o mesmo repositório do banco dedicado.
- Readiness permaneceu `INCOMPLETE`; nenhum dado fiscal real foi inventado.
- MAIN não recebeu `establishments` e nenhum outro tenant recebeu a migration.
- Template oficial já contém a estrutura para novos tenants.
- Login visual do ERP ficou pendente de credencial do usuário do tenant 14; autenticação não foi alterada.
