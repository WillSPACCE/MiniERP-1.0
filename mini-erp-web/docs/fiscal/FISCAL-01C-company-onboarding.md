# FISCAL-01C — Onboarding empresarial e fiscal

## Fluxo funcional

1. **Identificação:** razão social, nome fantasia, CNPJ e slug são persistidos no MAIN.
2. **Provisionamento:** o usuário é levado ao detalhe e cria conscientemente o banco dedicado pelo lifecycle existente.
3. **Fiscal:** depois do banco existir, o Painel libera o formulário completo, persistido exclusivamente em `establishments`.
4. **Confirmação:** o detalhe calcula completude de cadastro, endereço e identidade tributária, sem promover readiness fiscal.

Não existe armazenamento temporário de IE, CRT ou endereço fiscal no MAIN. Uma empresa não provisionada mostra a pendência e a próxima ação; depois do provisionamento, o retorno aponta diretamente para completar Fiscal.

## Separação de estados

`EstablishmentFiscalCompleteness` responde se existem origens cadastrais suficientes para os grupos futuros `emit` e `enderEmit`: `registration_complete`, `address_complete`, `tax_identity_complete` e `emit_ready`. Certificado, NF-e, NFC-e, homologação e produção possuem flags separadas e falsas. `FiscalReadiness` continua `INCOMPLETE`.

## Fonte canônica

Painel e ERP reutilizam `TenantEstablishmentRepository`. O Painel resolve o banco pelo `AdministrativeContext`; o ERP usa o `TenantContext` autenticado. `db_name` não vem da UI e não existe mutação de sessão para selecionar a empresa administrada.

## XML

O formulário exibe a rastreabilidade de CNPJ, nomes, inscrições, CRT e endereço para `emit`/`enderEmit`. Isso representa origem de dados, não geração de XML. Nenhum XML de referência estava anexado a esta execução, portanto nenhum dado real foi copiado e nenhuma comparação específica foi inventada.
