# PLATFORM-02A2 — limpeza e reconciliação controlada

Executada em 2026-08-22. O precheck confirmou o mapeamento autorizado: tenants 1/2 → `mini_erp_tenant_1`, 3 → `_2`, 4 → `_3`, 5 → `_5` e 14 → `_14`.

## Backups e auditoria

Diretório: `C:\xampp\htdocs\MiniRP\backups\PLATFORM-02A2-20260822-112712`.

| Banco | Bytes | SHA-256 |
|---|---:|---|
| mini_erp | 30094 | 0086FF484F88F4D4C9B1C50990F1F28C7BA19A751765AD95D1D24E90D356EEAF |
| mini_erp_tenant_1 | 16941 | 116F81B5CBE0496AD0E26D567CE3E8D5D9F58412EF498AE79B7A26DD302F3356 |
| mini_erp_tenant_2 | 16959 | C7F3AE4E8C20261CEFC7721CFBC25706DB64025BEEF644A5B2811E007AB087B5 |
| mini_erp_tenant_3 | 14299 | 896B8258F6773E62AD43BF559458DA5BD6BFCCA0D303C5EB48560C12F1A05EB9 |
| mini_erp_tenant_5 | 16799 | 36D9382222FD93F8C1658CA007FBE24E60E16DF22B27EE67E1AA15912A4899B3 |

Eventos registrados pelo operador local `PLATFORM-02A2`: `TEST_DATABASE_BACKED_UP` para `_1`, `_2` e `_3`; `TENANT_TEST_REMOVED` para 1–4; `TEST_DATABASE_DROPPED` para `_1`, `_2` e `_3`. Os dumps e o `manifest.json` são a trilha recuperável.

## Limpeza MAIN

Uma transação removeu, com predicados restritos a 1–4: 3 password resets, 2 product taxes, 4 itens, 4 vendas, 10 clientes, 1 fornecedor, 1 motorista, 1 transportadora, 5 produtos, 6 usuários e 4 tenants. `companies` estava vazia. Após o commit não havia referências a 1–4 nem tenant preservado apontando para `_1`, `_2` ou `_3`; só então esses três bancos foram removidos.

Estado final do MAIN: tenant 5/INFOCASE e tenant 14/willyan info. Bancos finais: `_5` e `_14`.

## Reconciliação do tenant 5

Contagens preservadas antes/depois: clientes 3, produtos 4, vendas 2, itens 2 e usuários locais 1. Nenhuma venda foi convertida.

| Migration | Decisão | Resultado |
|---|---|---|
| create_tenant_establishments | aditiva, sem seed | aplicada |
| extend_clientes_as_fiscal_people | aditiva | aplicada |
| extend_produtos_as_fiscal_products | aditiva | aplicada |
| create_versioned_tax_engine | aditiva, zero regras | aplicada |
| create_fiscal_operations | aditiva | aplicada |
| create_fiscal_xml_pipeline | aditiva, zero séries/artifacts | aplicada |
| create_fiscal_credentials_and_series_audit | aditiva, zero certificados | aplicada |
| close_fiscal_certificate_series_runtime | contém `DROP INDEX` | bloqueada e não aplicada |

O tenant 5 terminou `OUTDATED_OR_DRIFT` com 34 diferenças: 1 tabela, 6 colunas e 1 índice oficiais ausentes, 1 índice extra, além de 3 tabelas, 20 colunas e 2 diferenças reais de tipo legadas. `schema_version` permaneceu `NULL`.

O tenant 14 foi somente lido e permaneceu `OUTDATED_OR_DRIFT`, com 25 diferenças legadas: 3 tabelas, 20 colunas extras e 2 tipos divergentes. Seu `schema_version=v1` preexistente não foi alterado.

## Provisionamento e decisão

O fluxo real criou tenant TEST_ONLY com template v1, confirmou tabelas completas e zero dados operacionais, e removeu tenant e banco artificiais no `finally`.

Resultado: **BLOCKED** para fechamento total da PLATFORM-02A2, pois a migration necessária contém operação proibida e os tenants 5 e 14 não atingiram diff funcional zero. `PLATFORM-02B WRITE = NÃO`.
