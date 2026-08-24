# FISCAL-01A — Rollout físico de establishments

Inventário somente leitura de 21/08/2026:

| Tenant | Banco registrado | Establishment existe? | Migration necessária | Status |
|---:|---|---|---|---|
| 1 | `mini_erp_tenant_1` | Não | Sim | Pendente; não aplicar em massa |
| 2 | `mini_erp_tenant_1` | Não | Sim | Conflito: dois tenants apontam ao mesmo banco; reconciliar antes |
| 3 | `mini_erp_tenant_2` | Não | Sim | Pendente; não aplicar em massa |
| 4 | `mini_erp_tenant_3` | Não | Sim | Pendente; não aplicar em massa |
| 5 | `mini_erp_tenant_5` | Não | Sim | Pendente; não aplicar em massa |
| 12 | `mini_erp_tenant_12` | Não | Sim | Pendente; não aplicar em massa |
| 14 | `mini_erp_tenant_14` | Sim | Não | Migration aplicada e schema validado |

Cada rollout futuro exige confirmação do vínculo tenant/banco, backup, precheck, aplicação isolada da migration, comparação do schema e teste transacional com rollback. O conflito dos tenants 1 e 2 impede automação ingênua por linha do MAIN.

Novos tenants recebem `establishments` pelo template oficial `database/tenant-template/v1/schema.sql`, usado por `TenantSchemaTemplate::CURRENT` e validado pelo provisionador.

O template foi aplicado em `mini_erp_tenant_990001`, banco artificial exclusivo do teste: foram encontradas 10 tabelas e exatamente uma `establishments`. O banco artificial foi removido e sua inexistência foi confirmada após o teste.

## XML de referência

Nenhum arquivo XML de referência estava disponível no workspace ou nos anexos desta execução. O contrato e o mapa confirmam origem real para `emit/CNPJ`, `xNome`, `xFant`, `IE`, `CRT` e todos os campos solicitados de `enderEmit`; a comparação estrutural com o XML específico permanece pendente até o arquivo ser disponibilizado. Isso não foi mascarado como validação concluída e nenhum dado de cliente foi copiado.
