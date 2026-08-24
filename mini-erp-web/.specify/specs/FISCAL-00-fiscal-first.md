# FISCAL-00 — Baseline Fiscal-First

Status: especificado; implementação fiscal não iniciada.

## Decisões aprovadas

- NF-e modelo 55 e NFC-e modelo 65 usam leiaute 4.00, MOC 7.0 e NTs/schemas oficiais vigentes como fonte normativa.
- Toda mudança em cadastro/operação fiscalmente relevante exige rastreabilidade XML ↔ ERP e classificação `REQUIRED_ALWAYS`, `REQUIRED_WHEN`, `OPTIONAL`, `DERIVED` ou `NOT_APPLICABLE`.
- Tenant/control-plane não é estabelecimento fiscal. Dados fiscais canônicos pertencem ao data-plane e suportarão múltiplos estabelecimentos no futuro sem assumir banco por filial.
- Lifecycle operacional e Fiscal Readiness são estados independentes.
- Documento autorizado é histórico imutável baseado em snapshot, nunca reconstruído dos cadastros atuais.
- Builder recebe DTO validado e não consulta banco nem decide tributação.
- Certificado A1 e CSC são segredos; arquivo/senha/valor não ficam em public, Git, logs, sessão ou plaintext.
- Homologação é o primeiro ambiente. Produção exige liberação formal e nunca é default.
- XSD oficial é versionado, verificado e distribuído com a release fiscal; não é baixado a cada emissão.
- NFePHP está `ADOPT_WITH_CONDITIONS` e permanece não instalado em FISCAL-00.

## Fora do escopo

Código fiscal, migration, emissão, XML executável, assinatura, certificado/CSC real, consulta/transmissão SEFAZ, DANFE e alteração de dados reais.

## Contratos planejados

`FiscalDocumentDTO`, `FiscalItemDTO`, `FiscalPartyDTO`, `FiscalTaxDTO`, `FiscalPaymentDTO`, `FiscalTransportDTO`, `FiscalDocumentValidator`, `TaxRuleResolver`, `FiscalTaxEngine`, `NfeXmlBuilderContract`, `FiscalXsdValidator`, `FiscalSigner`, `SefazGateway` e `FiscalDocumentStorage`.

Detalhamento normativo e mapa de dados: `docs/fiscal/`.
