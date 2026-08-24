# Deep diff do schema tenant

`TenantSchemaInspector` lê `information_schema` para tabelas, engine/collation, colunas, tipos/unsigned, nullable, default, extra, PK/índices e FKs. `TemplateSchemaInspector` extrai o baseline v1 sem usar tenant real como template. `TenantSchemaComparator` classifica tabelas/colunas/propriedades/índices/FKs ausentes ou extras.

## Normalização PLATFORM-02A2

O parser preserva precisão/escala de tipos como `DECIMAL(18,2)` e reconhece PK inline como não nula. O comparador normaliza display width de inteiros, `BOOLEAN`/`TINYINT`, `JSON` como alias MariaDB de `LONGTEXT`, defaults entre aspas, `NULL`, expressões parentizadas e `CURRENT_TIMESTAMP()`; índices implícitos que suportam FKs não são reportados como drift extra. Diferenças reais de tamanho, colunas e estruturas continuam visíveis.

Pós-reconciliação parcial: tenant 5 = `OUTDATED_OR_DRIFT` (34); tenant 14 = `OUTDATED_OR_DRIFT` (25). Os JSONs integrais estão junto ao manifesto do backup PLATFORM-02A2.

## Estado PLATFORM-02A3

O comparador bruto continua mostrando extras, enquanto `SchemaCompatibilityClassifier` separa compatibilidade funcional. Extras legados não conflitantes resultam em `CURRENT_WITH_LEGACY`; ausências e drifts funcionais continuam bloqueantes. Tenant 5 e 14 terminaram com 23 `LEGACY_EXTRA`, zero bloqueios e estado observado `CURRENT_WITH_LEGACY`.

Resultado histórico: o template oficial possui 24 tabelas data-plane. Os bancos 1, 2, 3 e 5 possuem 12 tabelas, das quais três são extras control-plane legadas; portanto faltam 15 tabelas oficiais. Banco 3 tem menos colunas comerciais que 1/2/5. Classificação: `HISTORICAL_BASELINE`, não bug atual. Tenant 14 possui as 24 oficiais mais três extras legadas (`tenants`, `usuarios`, `password_resets`), totalizando 27; extras devem ser preservadas.

Migrations faltantes para o tenant 5: establishments, extensões fiscais de pessoas/produtos, TaxEngine, operações/documentos, XML pipeline, certificados e auditorias, em ordem das migrations de 21/08. Não foram aplicadas nesta task.
