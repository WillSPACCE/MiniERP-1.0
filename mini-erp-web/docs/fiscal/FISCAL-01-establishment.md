# FISCAL-01 — Cadastro fiscal do estabelecimento

Status: implementado; migration aplicada e validada no tenant local 14 pela FISCAL-01A. Demais tenants existentes permanecem pendentes de rollout controlado.

`mini_erp.tenants` permanece a identidade/lifecycle do Control-Plane. `establishments`, no banco dedicado, é a fonte canônica fiscal. `tenant_id` é sempre o escopo; `company_id` não participa deste modelo e `db_name` é resolvido no backend.

## Bootstrap e sincronização

Na ausência de registro, o formulário do Painel é preenchido uma única vez com CNPJ, razão social, nome fantasia e endereço disponíveis no MAIN. O primeiro salvamento cria o estabelecimento no tenant. Depois disso não existe sincronização automática: alterações administrativas não sobrescrevem dados fiscais e alterações fiscais não são duplicadas no MAIN.

O tipo desta entrega é `MATRIZ`, `is_primary=1`; a chave de tenant e o índice de principal preparam filiais futuras sem implementá-las.

## Readiness

O checklist verifica identificação, IE, CRT, endereço e IBGE. Certificado A1, ambiente fiscal e série permanecem pendentes. Por isso o estado desta task é sempre `INCOMPLETE`, independente de `tenants.status`.

## Aplicação

Novos tenants recebem a tabela pelo template `v1`. Tenants existentes exigem backup e aplicação manual de `migrations/20260821_create_tenant_establishments.sql`. O runtime nunca executa DDL.
