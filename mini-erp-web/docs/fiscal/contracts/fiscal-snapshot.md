# Contrato de Snapshot Fiscal

`fiscal_documents` e `fiscal_document_items` são versões internas imutáveis. Congelam emitente, destinatário, pagamento, transporte, totais, Produto, contexto, CFOP, ICMS, IPI, PIS, COFINS, IBS/CBS, IS, regra, versão, fonte e explicação. `idempotency_key` impede duplicação acidental. Estados atuais: `FISCAL_PENDING` e `FISCAL_READY`.
