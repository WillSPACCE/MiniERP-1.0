# PLATFORM-02A — auditoria histórica dos tenants

Auditoria executada em 2026-08-22. O baseline canônico `v1` possui 27 tabelas e deriva exclusivamente de `database/tenant-template/v1/schema.sql` mais migrations aprovadas. O template foi corrigido para incluir `fiscal_certificates`, `fiscal_certificate_audit` e `fiscal_series_audit`; não contém seeds.

| tenant | banco | criado | tabelas | classificação schema | causa/ação |
|---:|---|---|---:|---|---|
| 1 | mini_erp_tenant_1 | 14/08 | 12 | HISTORICAL_BASELINE + SHARED_DATABASE | fixture Default comprovada por script, mas banco compartilhado com tenant 2: preservar/revisar |
| 2 | mini_erp_tenant_1 | 14/08 | 12 | SHARED_DATABASE | UNKNOWN; preservar |
| 3 | mini_erp_tenant_2 | 14/08 | 12 | HISTORICAL_BASELINE | UNKNOWN; preservar, faltam 15 tabelas fiscais |
| 4 | mini_erp_tenant_3 | 14/08 | 12 | HISTORICAL_BASELINE | UNKNOWN; preservar, faltam 15 tabelas fiscais |
| 5 | mini_erp_tenant_5 | 14/08 | 12 | HISTORICAL_BASELINE | UNKNOWN; preservar, faltam 15 tabelas fiscais |
| 12 | mini_erp_tenant_12 | 14/08 | 12 | HISTORICAL_BASELINE | TEST_CONFIRMED por `scripts/create_sample_tenant.php`; backup e remoção concluídos |
| 14 | mini_erp_tenant_14 | 20/08 | 27 | ATUALIZADO | USER_PRESERVE; nenhuma migration faltante |

Não havia banco órfão. O tenant 4 aponta para `mini_erp_tenant_3`, enquanto `mini_erp_tenant_4` não existe; isso é db_name fora do padrão, preservado para revisão. Os bancos de 14/08 antecedem todas as migrations FISCAL de 21/08, portanto a ausência fiscal é historicamente explicável e não foi classificada como bug de provisionamento.

Classificação final: USER_PRESERVE: 14. TEST_CONFIRMED: 1 e 12; somente 12 era removível. UNKNOWN: 2, 3, 4 e 5. Nenhum UNKNOWN foi alterado. INFOCASE foi preservada por falta de evidência inequívoca.

O primeiro teste do fluxo real encontrou `tenants.schema_version` ausente no MAIN e bloqueio atual de provisionamento. A migration aprovada `20260820_add_tenant_schema_version.sql` foi aplicada após backup. Em seguida, uma empresa `PLATFORM-02A TEST_ONLY` foi criada/provisionada pelo serviço real: 27 tabelas, todas as estruturas fiscais e contagens operacionais zero. Fixture, banco e storage foram removidos. Conclusão: **o provisionamento atual está correto após a correção do template e do MAIN; divergências restantes são históricas ou exigem revisão manual**.
