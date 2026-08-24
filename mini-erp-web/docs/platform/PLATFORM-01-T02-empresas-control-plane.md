# PLATFORM-01-T02 — Cadastro e edição de empresas no Control-Plane

## Schema real inspecionado em 2026-08-20

A inspeção somente leitura foi feita diretamente no MAIN com `SHOW CREATE TABLE`, `SHOW INDEX` e `INFORMATION_SCHEMA`.

`tenants` possui: `id` auto-increment, `uuid`, `nome_fantasia`, `razao_social`, `cnpj`, `slug`, `logo`, `status`, `data`, timestamps, `db_name` anulável, endereço, município, regime e `blocked`.

Constraints comprovadas:

- primary key em `id`;
- `uq_tenants_slug` único;
- `uq_tenants_uuid` único;
- `uq_tenants_cnpj` único;
- `uuid` e `slug` não nulos;
- `db_name` anulável;
- `blocked` com default `0`;
- `status` com default legado `ativo`.

Existe também uma tabela `companies`, classificada como legado. O fluxo novo usa somente `tenants`; `tenant_id` é o alias canônico de `tenants.id`. Nenhum `company_id` é aceito ou persistido pelo fluxo T02.

## Divergência do fluxo legado

O legado chama `Repository::saveCompany()`. Ao criar, ele pode provisionar banco, aplicar schema/seeds e criar administradores; ao editar, pode garantir administrador. Esses efeitos colaterais tornam o método inadequado para T02.

O novo `PlatformTenantRepository` acessa somente o MAIN e contém exclusivamente consultas à tabela `tenants`. Não usa `Database`, `TenantConnectionResolver` ou nome de banco vindo da UI.

## Regras T02

- Cadastro e edição aceitam somente razão social, nome fantasia, CNPJ e slug.
- `tenant_id` é gerado por auto-increment.
- CNPJ é reduzido a dígitos e deve possuir 14 dígitos; unicidade é validada antes da escrita e reforçada pelo índice real.
- Slug é transliterado, convertido para minúsculas, limitado a letras ASCII, números e hífen, e deve ser único.
- Cadastro gera UUID lógico aleatório; não reserva nem cria banco.
- Cadastro fixa `status = cadastrada`; `db_name` permanece `NULL` por omissão e `blocked` permanece no default `0`.
- Edição não altera ID, UUID, status, bloqueio, `db_name`, lifecycle ou credenciais.

## Decisão/schema necessário para T03

O schema não possui `company_status` e `provisioning_status` separados. `status` é um `varchar` legado sem constraint de domínio. T02 usa apenas o estado inicial fixo `cadastrada` para não representar a empresa como provisionada. T03 deverá formalizar o lifecycle e sua compatibilidade sem improvisar novas transições nesta task.
