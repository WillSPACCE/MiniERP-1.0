# FISCAL-00 — Arquitetura Fiscal-First

Baseline pesquisado em 21/08/2026. Este documento não autoriza emissão, transmissão ou uso de credenciais reais.

## Fontes oficiais e vigência

| Ref. | Documento | Versão/data observada | URL | Impacto |
|---|---|---|---|---|
| S1 | MOC NF-e/NFC-e e Anexo I | MOC 7.0; modelos 55/65, leiaute 4.00 | https://www.nfe.fazenda.gov.br/portal/listaConteudo.aspx?tipoConteudo=ndIjl+iEFdE= | Base de grupos, tags e validações; deve ser combinada com NTs posteriores. |
| S2 | Schemas XML oficiais | pacote 010e v1.01, publicado 26/06/2026 | https://hom.nfe.fazenda.gov.br/portal/listaConteudo.aspx?tipoConteudo=BMPFMBoln3w= | XSD deve ser versionado e fixado por release, não baixado em emissão. |
| S3 | NT 2025.002 RTC | v1.50, publicada 03/06/2026 | https://www.nfe.fazenda.gov.br/portal/listaConteudo.aspx?tipoConteudo=04BIflQt1aY= | Introduz/atualiza IBS, CBS, IS, classificações e validações RTC. |
| S4 | NT 2026.004 | v1.01, publicada 08/06/2026 | https://www.nfe.fazenda.gov.br/portal/listaConteudo.aspx?tipoConteudo=04BIflQt1aY= | Schema NF-e/NFC-e compatível com CNPJ alfanumérico. |
| S5 | Informe Técnico 2025.002 | v1.60, publicado 23/06/2026 | https://www.nfe.fazenda.gov.br/portal/listaConteudo.aspx?tipoConteudo=hXzemuyNHW4= | Tabelas de classificação tributária, CST e crédito presumido IBS/CBS devem ser dados versionados. |
| S6 | DANFE NFC-e/QR Code | versão 6.0, março/2025 | https://www.nfe.fazenda.gov.br/portal/listaConteudo.aspx?tipoConteudo=ndIjl+iEFdE= | CSC/ID CSC são condicionais ao modelo 65, UF e ambiente. |
| S7 | CNPJ alfanumérico — RFB | produção em julho/2026; primeiro emitido em 31/07/2026 | https://www.gov.br/receitafederal/pt-br/acesso-a-informacao/acoes-e-programas/programas-e-atividades/cnpj-alfanumerico | CNPJ não pode ser normalizado como somente dígitos; formatos antigo e novo coexistem. |
| S8 | NTs 2026.002/2026.003 | v1.00, publicadas 25/05/2026 | https://www.nfe.fazenda.gov.br/portal/listaConteudo.aspx?tipoConteudo=04BIflQt1aY= | DANFE Simplificado Tipo 2 e operações presenciais/não presenciais entram no versionamento fiscal. |

Antes de implementar cada etapa, conferir novamente o Portal Nacional: NT e pacote de schemas são artefatos mutáveis.

## Regra obrigatória Fiscal-First

Qualquer mudança em Empresa, Estabelecimento, Pessoa/Cliente, Fornecedor, Produto, CFOP, Tributação, Entrada, Saída, Pedido, Transporte, Pagamento ou Fiscal deve responder e registrar:

1. grupo/tag XML consumidor;
2. entidade ERP de origem;
3. persistência canônica;
4. classificação de obrigatoriedade;
5. condição de uso;
6. derivação, quando houver;
7. necessidade de snapshot imutável;
8. schema, MOC, NT ou tabela oficial que fundamenta a decisão.

Categorias: `REQUIRED_ALWAYS`, `REQUIRED_WHEN`, `OPTIONAL`, `DERIVED`, `NOT_APPLICABLE`. Obrigatoriedade sempre depende da versão do schema, modelo 55/65, UF, ambiente, CRT, operação, produto e destinatário.

## Limites de domínio e fontes canônicas

### Control-plane (`mini_erp`)

`tenant_id`, UUID, slug, lifecycle, bloqueio, `db_name`, `schema_version` e metadados de provisionamento. CNPJ/nome hoje presentes em `tenants` são identidade administrativa e índice de descoberta; não devem ser usados diretamente para montar XML.

### Data-plane (`mini_erp_tenant_*`)

Criar futuramente `establishments` como fonte canônica fiscal por estabelecimento: identidade legal, IE/IEST/IM, CNAE, CRT, endereço, habilitação 55/65, ambiente, séries e numeração. Um tenant poderá ter matriz e filiais sem pressupor banco por filial.

Sincronização proposta: criação do tenant inicia um estabelecimento fiscal incompleto no banco dedicado somente após provisionamento. Mudanças fiscais acontecem no data-plane; MAIN mantém apenas um resumo de readiness sem duplicar valores fiscais autoritativos.

## Estado atual confirmado

- Painel cria/edita somente razão social, nome fantasia, CNPJ e slug.
- `PlatformTenantData` remove não dígitos e exige 14 dígitos: incompatível com S4/S7.
- MAIN possui campos legados de endereço/regime não usados pelos serviços T02.
- Tenant 14 possui clientes, produtos, CFOPs, fornecedores, vendas e itens, mas nenhuma tabela de estabelecimento fiscal/documento/snapshot.
- Produto tenant 14: código, NCM, CEST, unidade, GTIN, CFOP default, categoria, preço e estoque; faltam unidade tributável/conversão, origem, EX TIPI, benefício e regras fiscais.
- `product_taxes` guarda ICMS/IPI/PIS/COFINS como texto, sem CST/CSOSN, bases, alíquotas estruturadas, contexto, vigência ou IBS/CBS.
- Venda atual não guarda modelo, série, número, natureza, finalidade, presença, pagamentos estruturados, transporte, impostos, totais fiscais ou snapshots.

## Fiscal Readiness separado do lifecycle

Estados conceituais, a persistir no data-plane:

- `INCOMPLETE`: ERP operacional, emissão bloqueada.
- `READY_FOR_HOMOLOGATION`: cadastro, regras, séries e credenciais de homologação completos.
- `HOMOLOGATION_VALIDATED`: pelo menos um cenário formal homologado e evidências armazenadas.
- `READY_FOR_PRODUCTION`: revisão administrativa e todas as travas de produção satisfeitas.

Produção nunca é default. Critérios mínimos: estabelecimento completo, certificado válido, pacote XSD fixado, DTO validado, XML/XSD/assinatura testados, armazenamento seguro, séries/numeração configuradas, homologação autorizada e aprovação administrativa explícita.

Checklist futuro do Painel: dados cadastrais; endereço; CRT/IE/IBGE; NF-e; NFC-e; certificado; CSC; schemas; séries; homologação; storage; produção.

## Certificado e segredos

Prioridade: certificado A1 PFX/P12, sujeito à validação da biblioteca. Arquivo fora de `public/`, Git e banco em claro. Persistir apenas referência opaca e metadados: tipo, titular, CNPJ, validade, fingerprint, status e datas. Conteúdo e senha em secret storage do ambiente/serviço de chaves, cifrados em repouso, com chave fora do banco, controle de acesso, rotação e auditoria. Senha nunca retorna à UI, logs, sessão ou hidden input.

CSC/ID CSC são segredos separados por estabelecimento, UF e ambiente. Armazenar cifrado e exibir apenas máscara/metadados. Exigir somente para NFC-e modelo 65 quando aplicável; NF-e 55 isolada não depende de CSC.

## Pipeline fiscal futuro

`Operação persistida → agregação de cadastros/regras → TaxRuleResolver/FiscalTaxEngine → FiscalDocumentDTO → FiscalDocumentValidator → snapshot transacional → NfeXmlBuilderContract → XSD local versionado → assinatura → SEFAZ → protocolo/eventos → storage imutável`.

O builder não acessa banco nem calcula tributo. DTOs propostos: `FiscalDocumentDTO`, `FiscalItemDTO`, `FiscalPartyDTO`, `FiscalTaxDTO`, `FiscalPaymentDTO`, `FiscalTransportDTO`.

## Snapshot e storage

No momento fiscal, copiar para tabelas próprias todos os valores usados: emitente, destinatário, endereços, itens, códigos, unidades, quantidades, preços, descontos, CFOP, impostos, transporte, pagamentos e totais. Documento emitido nunca é reconstruído de cadastros mutáveis.

Storage fora de `public/`, por tenant/estabelecimento/documento, com referência no banco, hash, tamanho, tipo (`draft`, `validated`, `signed`, `authorized`, `event`), chave, protocolo, status e timestamps. Criptografia, retenção, backup, controle de acesso e trilha de auditoria são requisitos.

## Componentes futuros

- `TaxRuleResolver`/`FiscalTaxEngine`: entradas de estabelecimento, CRT, produto, NCM/CEST/origem, CFOP, UF, destinatário, operação e modelo; saídas CST/CSOSN, ICMS/ST/FCP, IPI, PIS, COFINS, IBS/CBS/IS e classificações.
- `FiscalDocumentValidator`: falha com mensagens acionáveis e regras condicionais versionadas.
- `NfeXmlBuilderContract`: recebe DTO validado e produz XML para uma versão explícita.
- `FiscalXsdValidator`, `FiscalSigner`, `SefazGateway`, `FiscalDocumentStorage`: fronteiras distintas e testáveis.

## Migrations futuras propostas (não criadas)

1. `establishments` e `establishment_addresses`.
2. `fiscal_profiles`/readiness e habilitações 55/65.
3. `fiscal_series` com alocação transacional de numeração.
4. `fiscal_secret_references` e `certificate_metadata` sem segredo em claro.
5. expansão fiscal de `parties` ou migração unificada cliente/fornecedor.
6. expansão fiscal de produtos e tabelas versionadas de classificação.
7. `tax_rules`/`tax_rule_versions` e resultados calculados.
8. `fiscal_documents`, `fiscal_document_items`, snapshots, impostos, pagamentos, transporte e totais.
9. `fiscal_artifacts`, protocolos, respostas e eventos.
10. tabelas de pacote XSD/NT e compatibilidade de schema.

Todas exigem design, migration reversível, compatibilidade tenant e plano de backfill próprios; nenhuma pertence à FISCAL-00.
