# FiscalDocumentDTO

DTO imutável criado exclusivamente de `issuer_snapshot`, `recipient_snapshot`, snapshots de produto e `FiscalTaxResolution`, totais, pagamento e transporte do Documento Fiscal Interno. O construtor fail-closed exige `FISCAL_READY`, ao menos um item e resolução tributária congelada para cada item.

O builder futuro não poderá consultar cadastros atuais de Empresa, Pessoa ou Produto. Modelo, natureza, data e demais campos de `ide` precisam estar congelados no documento antes da geração.
