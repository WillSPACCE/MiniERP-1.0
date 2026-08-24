# Teste manual — PLATFORM-02A3

## Schema

1. Abra INFOCASE e seu Database Manager; confira versão declarada v1 e estado observado `CURRENT_WITH_LEGACY`.
2. Repita para Willyan Info.
3. Confirme que tabelas e campos legados continuam presentes.

## Operações Multi-tenant

1. Abra `/plataforma/operacoes-multitenant.php`.
2. Pesquise INFOCASE e Willyan Info por nome, slug, ID e banco.
3. Teste filtros de ativos, atuais, pendentes e drift.
4. Use selecionar visíveis/atualizados/pendentes e limpar seleção.
5. Escolha uma migration, abra detalhes e confira SQL somente leitura, checksum, tabelas e risco.
6. Selecione os dois tenants e clique SIMULAR OPERAÇÃO.
7. Confira resultado individual `ALREADY_APPLIED` e `write=false`.
8. Confirme que EXECUTAR OPERAÇÃO está desabilitado e que não existe textarea SQL.
9. Confira o histórico por tenant.
10. Compare contagens antes/depois; nenhuma alteração deve ocorrer no dry-run.
