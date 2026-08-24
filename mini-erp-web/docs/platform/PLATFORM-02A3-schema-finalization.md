# PLATFORM-02A3 — finalização confiável dos schemas

Executada em 2026-08-22 após backups de MAIN, tenant 5 e tenant 14 em `backups/PLATFORM-02A3-20260822-113718`.

## Classificação inicial

Tenant 5 tinha 34 diferenças: 9 funcionais (fechamento de certificados/auditoria e dois tipos), 24 extras legados e 1 índice obsoleto. Tenant 14 tinha 25 diferenças essencialmente legadas. Extras conhecidos (`tenants`, `usuarios`, `password_resets`, campos comerciais antigos) foram preservados.

Categorias usadas: `REQUIRED_MISSING`, `LEGACY_EXTRA`, `SAFE_EQUIVALENT`, `TYPE_DRIFT`, `INDEX_DRIFT`, `CONSTRAINT_DRIFT` e `MANUAL_REVIEW`. Estado funcional: `CURRENT`, `CURRENT_WITH_LEGACY`, `OUTDATED`, `DRIFTED` ou `BLOCKED`.

## Índice sensível

`uq_active_certificate(tenant_id, establishment_id, active)` impedia mais de um registro inativo e não representava corretamente lifecycle/histórico. Ele foi substituído por `ix_active_certificate`, não único. Não é FK e não participa do `FiscalNumberAllocator`, que mantém `FOR UPDATE` em `fiscal_series` e uniques próprios em séries/reservas. Clone `_999996` confirmou estrutura, múltiplos históricos e allocator; o teste concorrente confirmou 21 reservas sem duplicidade. Clone removido.

## Ajustes

- Tenant 5: migration oficial de fechamento aplicada; tipos `tipo_pessoa` e `codigo_ibge` reconciliados após precheck sem perda.
- Tenant 14: somente os dois tipos foram reconciliados; estruturas fiscais já estavam fechadas.
- Ambos: criada `tenant_schema_migrations`, com 10 migrations verificadas, SHA-256, status, timestamp e origem.
- Tenant 5 promovido para `schema_version=v1` somente após zero bloqueios funcionais.

Após incorporar `tenant_schema_migrations` ao template oficial, o resultado final dos dois tenants é `CURRENT_WITH_LEGACY`, 23 extras preservados, zero `REQUIRED_MISSING`, `TYPE_DRIFT`, `INDEX_DRIFT` ou `CONSTRAINT_DRIFT` funcional.

Hashes de 15 tabelas críticas por tenant permaneceram idênticos antes/depois. Nenhuma regra fiscal, certificado, série, pedido ou dado comercial foi criado.
