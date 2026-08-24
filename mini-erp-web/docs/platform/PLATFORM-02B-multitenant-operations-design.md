# Design — Operações Multi-tenant

Fluxo futuro: selecionar tenants → escolher migration oficial → dry-run → backups → confirmação reforçada → execução individual → deep diff → auditoria.

A versão PLATFORM-02A3 é estritamente read-only. Descobre somente `.sql` versionados na pasta oficial, calcula SHA-256, classifica risco e compara com `tenant_schema_migrations`. Não aceita SQL livre e o botão EXECUTAR permanece desabilitado.

Prechecks: tenant ativo, `db_name` canônico vindo do MAIN, banco existente, banco não compartilhado, checksum, histórico e risco destrutivo. Resultados são individuais; uma falha futura não pode esconder o resultado dos outros tenants.

Estados do dry-run: `ALREADY_APPLIED`, `PENDING`, `BLOCKED` e `CHECKSUM_MISMATCH`. A execução real deverá acrescentar dependências explícitas, backup verificável por tenant, confirmação administrativa, locks e auditoria durável antes de ser liberada.
