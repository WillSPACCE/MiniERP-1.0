# PLATFORM-02B — Testes manuais e validação

## Fluxo principal

1. Fazer login no Control-Plane com `platform_admin_users` usando um usuário `SUPER_ADMIN`.
2. Abrir `Operações Multi-tenant`.
3. Selecionar empresas de teste (`TEST_ONLY`) ou os tenants permitidos.
4. Selecionar a migration oficial do catálogo.
5. Clicar `SIMULAR`.
6. Validar status individual e `write_performed = false`.
7. Revisar checksum, risco, tabelas afetadas e backup obrigatório.
8. Confirmar a operação com a frase de confirmação exigida.
9. Clicar `EXECUTAR OPERAÇÃO`.
10. Verificar backup gerado em `backups/MULTITENANT/{operation_id}/`.
11. Conferir `sha256` do backup e do arquivo oficial.
12. Validar progresso por tenant.
13. Concluir com `SUCCESS` ou `PARTIAL`.
14. Conferir `tenant_schema_migrations` no banco do tenant.
15. Verificar `platform_admin_audit_log`.
16. Abrir histórico operacional.

## Casos de regressão obrigatórios

- `ERP user` bloqueado pela autenticação administrativa.
- `TARGET_MISMATCH` quando a migration é do tipo `MAIN`.
- `CHECKSUM_MISMATCH` quando o arquivo muda entre dry-run e execução.
- `DEPENDENCY_MISSING` quando a dependencia não foi aplicada.
- `BACKUP_FAILED` quando o backup falha.
- `DESTRUCTIVE` bloqueado.
- `STRUCTURAL` exige confirmação textual.
- `double submit` não executa a migração duas vezes.
- `lock concorrente` bloqueia execução simultânea no mesmo tenant.
- `PARTIAL` quando um tenant está `BLOCKED` e outro está pronto.
- `Database Manager` permanece `READ-ONLY`.
- `tenants 5` e `14` não recebem writes durante os testes da task.

## Cleanup

Após os testes em bancos `TEST_ONLY`, remover os bancos temporários e confirmar que não restaram writes em produção/proteção.

## Resultado esperado

- dry-run obrigatório;
- `plan_id` gerado e expirando em 600s;
- `operation_id` único;
- backup por tenant;
- lock e execução sequencial;
- validação pós-execução;
- auditoria concluída;
- histórico persistido.
