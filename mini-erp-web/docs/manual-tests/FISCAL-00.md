# Validação manual — FISCAL-00

Esta validação é documental e somente leitura.

1. Abra `docs/fiscal/FISCAL-ARCHITECTURE.md`.
2. Confirme data, versões e URLs oficiais do MOC, schemas, NT 2025.002, NT 2026.004, tabelas RTC e CNPJ alfanumérico.
3. Abra `docs/fiscal/NFE-XML-DATA-MAP.md` e confirme os grupos `infNFe`, `ide`, `emit`, `enderEmit`, `dest`, `enderDest`, `det/prod`, impostos, total, transporte, cobrança, pagamento e informações adicionais.
4. Para uma amostra de cada grupo, confira origem ERP, tabela/coluna, obrigatoriedade, condição, natureza, fonte e status.
5. Confirme que campos condicionais usam `REQUIRED_WHEN`, não obrigatoriedade universal.
6. Confira no schema atual que não existem tabelas de estabelecimento fiscal, certificado, séries, documento fiscal ou snapshot.
7. Confira que `PlatformTenantData` ainda exige CNPJ numérico e que esse gap está registrado, sem alterar o runtime.
8. Abra `docs/fiscal/NFEPHP-INTEGRATION.md` e confira a decisão `ADOPT_WITH_CONDITIONS`, PHP 8.2.12 e Composer ausente.
9. Confira no roadmap FISCAL-00–16 e backlog ERP-CRUD reconciliado.
10. Execute os testes estáticos documentais aplicáveis.

Checklist:

- [ ] fontes oficiais e datas registradas
- [ ] modelos 55/65 e leiaute 4.00 cobertos
- [ ] RTC/IBS/CBS e CNPJ alfanumérico cobertos
- [ ] mapa XML identifica gaps reais
- [ ] Control-plane separado de Estabelecimento
- [ ] readiness separado do lifecycle
- [ ] certificado/CSC tratados como segredo
- [ ] produção não é default
- [ ] snapshot e storage fora de public especificados
- [ ] NFePHP não instalado
- [ ] nenhum schema/dado alterado
- [ ] nenhuma chamada SEFAZ realizada
