# PLATFORM-01-T05 — Template/schema versionado

## Fonte canônica e versão atual

A fonte canônica para novos tenants é `database/tenant-template/v1/schema.sql`. `TenantSchemaTemplate::CURRENT` define `v1` no backend e resolve somente versões reconhecidas. GET/POST não escolhem versão ou nome de banco.

O template v1 possui nove tabelas operacionais: `clientes`, `produtos`, `vendas`, `itens_venda`, `product_taxes`, `cfops`, `fornecedores`, `motoristas` e `transportadoras`. Contém apenas DDL; não contém seeds ou dados mínimos.

## Separação arquitetural

O antigo `database/schema.sql` mistura estruturas operacionais com `tenants`, `usuarios` e `password_resets`. O template v1 exclui essas três estruturas porque registro/lifecycle do tenant e autenticação canônica pertencem ao Control-Plane. A compatibilidade local de usuários será decidida em T06, sem antecipá-la no template.

`company_id` ainda aparece em `produtos` por compatibilidade do ERP atual. Sua remoção exige evolução consciente posterior; `tenant_id` continua sendo a identidade canônica no Control-Plane.

## Estado real e tenant 14

O tenant 14 foi criado pela fonte antiga e possui 12 tabelas vazias, incluindo `tenants`, `usuarios` e `password_resets`. Suas tabelas, colunas, índices e constraints correspondem ao `database/schema.sql` que T04 utilizava. Como o template oficial v1 contém apenas nove tabelas, não há equivalência estrutural exata: tenant 14 permanece com versão desconhecida e não foi alterado.

O tenant legado 12 possui as mesmas 12 tabelas-base, mas recebeu colunas oportunistas adicionais em `clientes`, `fornecedores`, `usuarios` e `tenants`, além de dados demonstrativos em clientes, produtos e vendas. Isso confirma que existência/nome do banco não prova uma versão histórica.

## Persistência da versão

Foi criada a migration opt-in `migrations/20260820_add_tenant_schema_version.sql`, que adiciona `tenants.schema_version VARCHAR(32) NULL`. `NULL` significa “não identificada”. A migration não atualiza registros existentes e não foi executada.

Enquanto a coluna não existir, o painel continua lendo tenants com `schema_version = NULL`, mas novos provisionamentos falham antes de qualquer acesso físico. Após aplicação consciente da migration, um novo provisionamento grava `v1` somente junto da conclusão que define `db_name` e `ativa`.

## Provisionamento e validação

O provisionador recebe `TenantSchemaTemplate`, aplica a versão atual conhecida e valida a lista exata de tabelas daquela versão. Template inexistente, ilegível, vazio, versão desconhecida, schema divergente ou indisponibilidade de persistência da versão impedem ativação. Permanecem as garantias T04: naming por `tenant_id`, sem seeds, sem `IF NOT EXISTS`, sem DROP, sem adoção, sem sessão ERP e sem dados externos.

## Evolução futura

Uma futura versão deve entrar em novo diretório (`v2/schema.sql`) e só se tornar `CURRENT` após definição do caminho de upgrade. T05 não implementa migration runner entre versões nem classifica tenants antigos. Reconciliação histórica deve comparar estrutura e registrar evidência explicitamente.

## Riscos e pendências

- o template v1 ainda reflete o schema operacional mínimo legado, incluindo `company_id` em produtos;
- o ERP atual possui alterações oportunistas não consolidadas no template;
- é necessário backup e confirmação antes da migration do MAIN;
- T06 definirá usuários; T10 validará se o schema operacional mínimo atende integralmente ao acesso ERP;
- `provisioning_status`, auditoria de etapas e migrations tenant entre versões permanecem futuros.
