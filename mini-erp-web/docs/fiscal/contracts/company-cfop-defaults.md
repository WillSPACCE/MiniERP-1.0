# Contrato — CFOPs padrão

Contextos canônicos: `ENTRY_INTERNAL` (1xxx), `ENTRY_INTERSTATE` (2xxx), `EXIT_INTERNAL` (5xxx), `EXIT_INTERSTATE` (6xxx). O select vem do CRUD `cfops`; backend confirma que o código está ativo e possui prefixo coerente.

CFOP da empresa é hint; `produto.cfop_padrao` é hint mais específico; TaxEngine é autoridade final; snapshot guarda o CFOP efetivo. Nenhuma alteração posterior retroage.
