# FISCAL-CONFIG-01 — Central Fiscal da Empresa

A Central Fiscal fica em Painel → Empresa → Central Fiscal e usa exclusivamente o banco do tenant selecionado. A migration `20260824_create_company_fiscal_settings.sql` é aditiva, cria estruturas vazias e não foi aplicada aos tenants 5 ou 14.

Grupos: Geral Fiscal; quatro contextos CFOP; CSC de homologação/produção; ICMS versionado por UF; PIS/COFINS; IPI; IBS/CBS; Imposto Seletivo; prontidão. Produção permanece bloqueada. CSC usa AES-256-GCM via `LocalEncryptedSecretStorage`; o banco contém apenas referência e sufixo. Auditoria guarda ator, data, grupo, antes/depois; CSC é `REDACTED`.

Nenhum default tributário foi criado. Ausência resulta em `FISCAL_PENDING`. Não há transmissão SEFAZ nem QR Code nesta entrega.

## Matriz de campos

| Grupo | UI | Tabela/colunas | Serviço/validação | TaxEngine/XML/snapshot | Status |
|---|---|---|---|---|---|
| Ambiente/modelo/gerais | Geral | `establishment_fiscal_settings` | Produção bloqueada; modelo 55/65; decimal canônico | Hint; snapshot futuro da resolução | Implementado |
| CFOP 1/2/5/6 | CFOP | `establishment_cfop_defaults` | CFOP ativo no CRUD e prefixo coerente | Hint; TaxEngine decide; snapshot congela | Implementado |
| CSC/idCSC | NFC-e/CSC | `establishment_csc_credentials` | tenant+estabelecimento+ambiente; segredo cifrado | Somente modelo 65 | Implementado |
| ICMS UF | ICMS | `establishment_icms_defaults` | UF oficial, DECIMAL, vigência | Default inferior à regra/produto | Implementado |
| PIS/COFINS/IPI | Tributos | `establishment_legacy_tax_defaults` | CST com zero à esquerda, DECIMAL, cEnq separado | Default/hint | Implementado |
| IBS/CBS/IS | RTC | `establishment_rtc_defaults` | escopo ALL/55/65, DECIMAL, classificação sem lista fictícia | Default/hint | Implementado |
| Auditoria | automática | `establishment_fiscal_settings_audit` | ator, grupo, before/after | Não fiscaliza XML | Implementado |

Risco operacional: a migration deve ser validada primeiro em banco artificial e receber backup antes de qualquer rollout real.
