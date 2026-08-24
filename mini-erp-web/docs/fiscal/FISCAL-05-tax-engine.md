# FISCAL-05 — Motor tributário versionado

Fluxo: `FiscalTaxContext → TaxRuleRepositoryContract → TaxRuleResolver → FiscalTaxResolution → DecimalTaxCalculator`. Seleção e cálculo são separados; não há alíquotas, CST, CSOSN, CFOP ou cClassTrib hardcoded como legislação.

## Fontes oficiais verificadas em 21/08/2026

- Portal Nacional NF-e, Informe Técnico 2025.002 v1.60, publicado em 23/06/2026, atualizando tabelas de Crédito Presumido e cClassTrib: https://hom.nfe.fazenda.gov.br/portal/informe.aspx?Informe=FyRbETLvJgs%3D&ehCTG=false
- Portal Nacional NF-e, esquemas oficiais em uso, incluindo NT 2025.002 v1.40, NT 2026.002 e NT 2026.003: https://www.nfe.fazenda.gov.br/portal/listaConteudo.aspx?tipoConteudo=BMPFMBoln3w%3D
- MOC 7.0, Anexo I — Leiaute e Regras de Validação NF-e/NFC-e: https://www.nfe.fazenda.gov.br/portal/exibirArquivo.aspx?conteudo=J+I+v4eN00E%3D

As tabelas oficiais não são baixadas em runtime. `fiscal_reference_versions` registra documento, versão, publicação, vigência e SHA-256; `fiscal_classifications` guarda códigos por versão. Ambas nasceram vazias porque a task não autoriza inferir/importar conteúdo sem pipeline verificável.

## Decimal

BCMath opera strings decimais. A prévia TEST_ONLY multiplica quantidade × valor e demonstra half-up em duas casas. Isso não declara regra universal: escalas e arredondamentos específicos de cada tributo deverão vir de documentação oficial validada.

## Legado

`product_taxes` continua intacta e classificada como `LEGACY_TAX_DATA`: textos sem contexto, CST, base, vigência ou fonte não podem ser convertidos silenciosamente.
