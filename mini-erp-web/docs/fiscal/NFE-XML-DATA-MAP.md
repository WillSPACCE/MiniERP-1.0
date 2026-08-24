# NF-e/NFC-e 4.00 — Mapa XML ↔ ERP

Baseline de 21/08/2026. Fontes S1–S8 estão definidas em `FISCAL-ARCHITECTURE.md`. Este mapa é arquitetural: obrigatoriedade final deve ser validada contra XSD e regras vigentes da operação.

FISCAL-06B preparou schema aditivo para série, número, ambiente, cNF, chave e artefato, mas ele não foi aplicado e não transforma os `GAP` de XML em implementação. O builder permanece bloqueado até instalação oficial e validação do NFePHP/schemas 2026, inclusive CNPJ alfanumérico e RTC.

FISCAL-06B-B reconciliou os pacotes oficiais `010e v1.02` e `010d v1.03`. A construção estrutural unsigned 55/65 foi comprovada, mas não altera os `GAP`: o XSD completo exige `ds:Signature`, proibida nesta etapa, e por isso nenhum XML recebeu estado `XSD_VALID`.

`IMPLEMENTED_DATA` significa origem concreta com UI/configuração, backend, coluna e round-trip. Não significa que XML, assinatura ou transmissão estejam implementados. Regras obrigatórias para novas tags estão em `FISCAL-FIELD-IMPLEMENTATION-RULES.md`.

| Grupo | Tag | Descrição | Origem ERP | Tabela | Coluna | Obrigatoriedade | Condição | Natureza | Fonte | Status |
|---|---|---|---|---|---|---|---|---|---|---|
| infNFe | `@Id` | ID/chave do documento | Documento | futura `fiscal_documents` | `access_key` | DERIVED | após identificação completa | Snapshot | S1/S2 | GAP |
| infNFe | `@versao` | versão leiaute | Pacote fiscal | futura `fiscal_schema_packages` | `layout_version` | REQUIRED_ALWAYS | todo documento | Snapshot | S1/S2 | GAP |
| ide | `cUF` | código UF emitente | Estabelecimento | futura `establishments` | `state_code` | DERIVED | todo documento | Snapshot | S1 | GAP |
| ide | `cNF` | código numérico | Documento | futura `fiscal_documents` | `numeric_code` | DERIVED | todo documento | Snapshot | S1 | GAP |
| ide | `natOp` | natureza operação | Operação | `cfops`/futura operação | `natureza` | REQUIRED_ALWAYS | todo documento | Snapshot | S1 | PARTIAL |
| ide | `mod` | modelo 55/65 | Configuração/operação | futura `fiscal_documents` | `model` | REQUIRED_ALWAYS | todo documento | Snapshot | S1 | GAP |
| ide | `serie` | série | Estabelecimento | futura `fiscal_series` | `series` | REQUIRED_ALWAYS | todo documento | Snapshot | S1 | GAP |
| ide | `nNF` | número fiscal | Série | futura `fiscal_series` | `next_number` | REQUIRED_ALWAYS | todo documento | Snapshot | S1 | GAP |
| ide | `dhEmi` | data/hora emissão | Documento | futura `fiscal_documents` | `issued_at` | REQUIRED_ALWAYS | todo documento | Snapshot | S1 | GAP |
| ide | `dhSaiEnt` | saída/entrada | Operação | `vendas`/futura entrada | `operation_at` | REQUIRED_WHEN | conforme modelo/operação | Snapshot | S1 | GAP |
| ide | `tpNF` | entrada/saída | Operação | futura operação | `direction` | REQUIRED_ALWAYS | todo documento | Snapshot | S1 | GAP |
| ide | `idDest` | destino operação | Endereços | futura operação | `destination_scope` | DERIVED | todo documento | Snapshot | S1 | GAP |
| ide | `cMunFG` | município fato gerador | Estabelecimento | futura `establishments` | `city_ibge_code` | REQUIRED_ALWAYS | todo documento | Snapshot | S1 | GAP |
| ide | `tpImp` | formato DANFE | Configuração | futura `fiscal_profiles` | `print_type` | REQUIRED_ALWAYS | todo documento | Snapshot | S1/S8 | GAP |
| ide | `tpEmis` | forma emissão | Contingência | futura `fiscal_documents` | `emission_type` | REQUIRED_ALWAYS | todo documento | Snapshot | S1 | GAP |
| ide | `tpAmb` | ambiente | Estabelecimento | futura `fiscal_profiles` | `environment` | REQUIRED_ALWAYS | todo documento | Snapshot | S1 | GAP |
| ide | `finNFe` | finalidade | Operação | futura operação | `purpose` | REQUIRED_ALWAYS | todo documento | Snapshot | S1 | GAP |
| ide | `indFinal` | consumidor final | Operação/destinatário | futura operação | `final_consumer` | REQUIRED_ALWAYS | todo documento | Snapshot | S1 | GAP |
| ide | `indPres` | indicador presença | Operação | futura operação | `presence_indicator` | REQUIRED_WHEN | regras do modelo/operação | Snapshot | S1/S8 | GAP |
| ide | `procEmi` | processo emissão | Sistema | futura `fiscal_documents` | `emission_process` | REQUIRED_ALWAYS | todo documento | Snapshot | S1 | GAP |
| ide | `verProc` | versão processo | Release | futura `fiscal_documents` | `process_version` | REQUIRED_ALWAYS | todo documento | Snapshot | S1 | GAP |
| emit | `CNPJ` | CNPJ emitente | Estabelecimento | `establishments` | `tax_id` | REQUIRED_ALWAYS | emitente PJ | Snapshot | S1/S4/S7 | IMPLEMENTED_DATA |
| emit | `xNome` | razão social | Estabelecimento | `establishments` | `legal_name` | REQUIRED_ALWAYS | todo emitente | Snapshot | S1 | IMPLEMENTED_DATA |
| emit | `xFant` | nome fantasia | Estabelecimento | `establishments` | `trade_name` | OPTIONAL | quando informado | Snapshot | S1 | IMPLEMENTED_DATA |
| emit | `IE` | inscrição estadual | Estabelecimento | `establishments` | `state_registration` | REQUIRED_ALWAYS | emitente NF-e/NFC-e | Snapshot | S1 | IMPLEMENTED_DATA |
| emit | `IEST` | IE substituto | Estabelecimento | `establishments` | `st_registration` | REQUIRED_WHEN | substituto tributário | Snapshot | S1 | IMPLEMENTED_DATA |
| emit | `IM` | inscrição municipal | Estabelecimento | `establishments` | `municipal_registration` | REQUIRED_WHEN | serviço sujeito/condição oficial | Snapshot | S1 | IMPLEMENTED_DATA |
| emit | `CNAE` | CNAE fiscal | Estabelecimento | `establishments` | `cnae` | REQUIRED_WHEN | condições do leiaute | Snapshot | S1 | IMPLEMENTED_DATA |
| emit | `CRT` | regime tributário | Estabelecimento | `establishments` | `tax_regime_code` | REQUIRED_ALWAYS | todo emitente | Snapshot | S1 | IMPLEMENTED_DATA |
| enderEmit | `xLgr` | logradouro | Estabelecimento | `establishments` | `street` | REQUIRED_ALWAYS | todo emitente | Snapshot | S1 | IMPLEMENTED_DATA |
| enderEmit | `nro` | número | Estabelecimento | `establishments` | `number` | REQUIRED_ALWAYS | todo emitente | Snapshot | S1 | IMPLEMENTED_DATA |
| enderEmit | `xCpl` | complemento | Estabelecimento | `establishments` | `complement` | OPTIONAL | quando houver | Snapshot | S1 | IMPLEMENTED_DATA |
| enderEmit | `xBairro` | bairro | Estabelecimento | `establishments` | `district` | REQUIRED_ALWAYS | todo emitente | Snapshot | S1 | IMPLEMENTED_DATA |
| enderEmit | `cMun` | município IBGE | Estabelecimento | `establishments` | `city_ibge_code` | REQUIRED_ALWAYS | todo emitente | Snapshot | S1 | IMPLEMENTED_DATA |
| enderEmit | `xMun` | município | Estabelecimento | `establishments` | `city_name` | REQUIRED_ALWAYS | todo emitente | Snapshot | S1 | IMPLEMENTED_DATA |
| enderEmit | `UF` | UF | Estabelecimento | `establishments` | `state` | REQUIRED_ALWAYS | todo emitente | Snapshot | S1 | IMPLEMENTED_DATA |
| enderEmit | `CEP` | CEP | Estabelecimento | `establishments` | `postal_code` | OPTIONAL | conforme leiaute | Snapshot | S1 | IMPLEMENTED_DATA |
| enderEmit | `cPais` | código país | Estabelecimento | `establishments` | `country_code` | OPTIONAL/DERIVED | padrão Brasil quando cabível | Snapshot | S1 | IMPLEMENTED_DATA |
| enderEmit | `xPais` | país | Estabelecimento | `establishments` | `country_name` | OPTIONAL | quando informado | Snapshot | S1 | IMPLEMENTED_DATA |
| enderEmit | `fone` | telefone | Estabelecimento | `establishments` | `phone` | OPTIONAL | quando informado | Snapshot | S1 | IMPLEMENTED_DATA |
| dest | `CNPJ`/`CPF`/`idEstrangeiro` | identificação | Pessoa | `clientes` | `cpf_cnpj`/`foreign_id` | REQUIRED_WHEN | tipo/local do destinatário | Snapshot | S1/S4/S7 | IMPLEMENTED_DATA |
| dest | `xNome` | nome/razão | Pessoa | `clientes` | `nome` | REQUIRED_WHEN | regras de modelo/ambiente | Snapshot | S1 | EXISTING |
| dest | `indIEDest` | indicador IE | Pessoa fiscal | `clientes` | `state_registration_indicator` | REQUIRED_ALWAYS | destinatário informado | Snapshot | S1 | IMPLEMENTED_DATA |
| dest | `IE` | inscrição estadual | Pessoa fiscal | `clientes` | `inscricao_estadual` | REQUIRED_WHEN | contribuinte ICMS | Snapshot | S1 | IMPLEMENTED_DATA |
| dest | `ISUF` | SUFRAMA | Pessoa fiscal | `clientes` | `suprama` | REQUIRED_WHEN | operação SUFRAMA | Snapshot | S1 | IMPLEMENTED_DATA |
| dest | `IM` | inscrição municipal | Pessoa fiscal | `clientes` | `im` | OPTIONAL/CONDITIONAL | conforme operação | Snapshot | S1 | IMPLEMENTED_DATA |
| dest | `email` | e-mail | Pessoa | `clientes` | `email` | OPTIONAL | quando informado | Snapshot | S1 | EXISTING |
| enderDest | `xLgr` | logradouro | Pessoa | `clientes` | `logradouro` | REQUIRED_WHEN | endereço exigido | Snapshot | S1 | EXISTING |
| enderDest | `nro` | número | Pessoa | `clientes` | `numero` | REQUIRED_WHEN | endereço exigido | Snapshot | S1 | EXISTING |
| enderDest | `xBairro` | bairro | Pessoa | `clientes` | `bairro` | REQUIRED_WHEN | endereço exigido | Snapshot | S1 | EXISTING |
| enderDest | `cMun` | município IBGE | Pessoa | `clientes` | `codigo_ibge` | REQUIRED_WHEN | destinatário nacional/endereço exigido | Snapshot | S1 | IMPLEMENTED_DATA |
| enderDest | `xMun` | município | Pessoa | `clientes` | `municipio`/`cidade` | REQUIRED_WHEN | endereço exigido | Snapshot | S1 | EXISTING |
| enderDest | `UF` | UF | Pessoa | `clientes` | `uf`/`estado` | REQUIRED_WHEN | destinatário nacional | Snapshot | S1 | EXISTING |
| enderDest | `CEP` | CEP | Pessoa | `clientes` | `cep` | OPTIONAL | quando informado | Snapshot | S1 | EXISTING |
| enderDest | `cPais`/`xPais` | país | Pessoa | `clientes` | `country_code`/`country_name` | REQUIRED_WHEN | exterior | Snapshot | S1 | IMPLEMENTED_DATA |
| enderDest | `fone` | telefone | Pessoa | `clientes` | `fone_principal`/`telefone` | OPTIONAL | quando informado | Snapshot | S1 | EXISTING |
| det/prod | `cProd` | código interno | Produto | `produtos` | `codigo` | REQUIRED_ALWAYS | todo item | Snapshot | S1 | EXISTING |
| det/prod | `cEAN` | GTIN comercial | Produto | `produtos` | `gtin` | REQUIRED_ALWAYS | GTIN ou `SEM GTIN` | Snapshot | S1 | IMPLEMENTED_DATA |
| det/prod | `xProd` | descrição | Produto/operação | `produtos` | `nome` | REQUIRED_ALWAYS | todo item | Snapshot | S1 | EXISTING |
| det/prod | `NCM` | NCM | Produto fiscal | `produtos` | `ncm` | REQUIRED_ALWAYS | uso fiscal | Snapshot | S1 | IMPLEMENTED_DATA |
| det/prod | `NVE` | nomenclatura valor aduaneiro | Produto/operação | futura | `nve` | REQUIRED_WHEN | operações aplicáveis | Snapshot | S1 | GAP |
| det/prod | `CEST` | CEST | Produto fiscal | `produtos` | `cest` | REQUIRED_WHEN | mercadoria/tributação aplicável | Snapshot | S1 | IMPLEMENTED_DATA |
| det/prod | `EXTIPI` | EX TIPI | Produto fiscal | `produtos` | `extipi` | REQUIRED_WHEN | classificação aplicável | Snapshot | S1 | IMPLEMENTED_DATA |
| det/prod | `cBenef` | benefício fiscal | Produto/regra | `produtos` | `tax_benefit_code` | REQUIRED_WHEN | legislação/UF aplicável | Snapshot | S1 | IMPLEMENTED_DATA |
| det/prod | `nFCI` | FCI | Produto fiscal | `produtos` | `fci_number` | REQUIRED_WHEN | produto importado aplicável | Snapshot | S1 | IMPLEMENTED_DATA |
| det/prod | `CFOP` | CFOP efetivo | Operação/item | `cfops`/futuro item | `codigo`/`cfop_code` | REQUIRED_ALWAYS | todo item | Snapshot | S1 | PARTIAL |
| det/prod | `uCom` | unidade comercial | Produto | `produtos` | `unidade` | REQUIRED_ALWAYS | todo item | Snapshot | S1 | IMPLEMENTED_DATA |
| det/prod | `qCom` | quantidade comercial | Item operação | `itens_venda` | `quantidade` | REQUIRED_ALWAYS | todo item | Snapshot | S1 | EXISTING |
| det/prod | `vUnCom` | valor unitário comercial | Item operação | `itens_venda` | `preco_unitario` | REQUIRED_ALWAYS | todo item | Snapshot | S1 | EXISTING |
| det/prod | `vProd` | valor produto | Item operação | `itens_venda` | `subtotal` | REQUIRED_ALWAYS | todo item | Snapshot | S1 | EXISTING |
| det/prod | `cEANTrib` | GTIN tributável | Produto fiscal | `produtos` | `gtin_tributable` | REQUIRED_ALWAYS | GTIN ou `SEM GTIN` | Snapshot | S1 | IMPLEMENTED_DATA |
| det/prod | `uTrib` | unidade tributável | Produto fiscal | `produtos` | `taxable_unit` | REQUIRED_ALWAYS | todo item | Snapshot | S1 | IMPLEMENTED_DATA |
| det/prod | `qTrib` | quantidade tributável | Item fiscal | futura coluna | `tax_quantity` | REQUIRED_ALWAYS | todo item | Snapshot | S1 | GAP |
| det/prod | `vUnTrib` | valor unitário tributável | Item fiscal | futura coluna | `tax_unit_value` | REQUIRED_ALWAYS | todo item | Snapshot | S1 | GAP |
| det/prod | `vFrete`/`vSeg`/`vDesc`/`vOutro` | ajustes item | Item operação | futura | campos próprios | OPTIONAL | quando houver | Snapshot | S1 | GAP |
| det/prod | `indTot` | compõe total | Item operação | futura | `included_in_total` | REQUIRED_ALWAYS | todo item | Snapshot | S1 | GAP |
| imposto | `vTotTrib` | carga tributária aproximada | Cálculo item | futura snapshot | `estimated_tax_total` | OPTIONAL | política legal/aplicável | Snapshot | S1 | GAP |
| ICMS | `orig` | origem mercadoria | Produto fiscal | `produtos` | `merchandise_origin` | REQUIRED_WHEN | grupo ICMS escolhido | Snapshot | S1 | IMPLEMENTED_DATA |
| ICMS | `CST`/`CSOSN` | situação tributária | Motor fiscal | futura `fiscal_item_taxes` | `icms_tax_code` | REQUIRED_WHEN | CRT/grupo ICMS | Snapshot | S1 | GAP |
| ICMS | `modBC`/`vBC`/`pICMS`/`vICMS` | cálculo ICMS | Motor fiscal | futura snapshot | campos estruturados | REQUIRED_WHEN | CST/CSOSN/operação | Snapshot | S1 | GAP |
| ICMS | grupos ST/FCP/Difal | ICMS-ST/FCP/destino | Motor fiscal | futura snapshot | campos estruturados | REQUIRED_WHEN | regra tributária | Snapshot | S1 | GAP |
| IPI | `cEnq`/`CST`/base/alíquota/valor | IPI | Motor fiscal | `product_taxes`/futura snapshot | `ipi` hoje texto | REQUIRED_WHEN | produto/operação tributada | Snapshot | S1 | GAP |
| PIS | `CST`/base/alíquota/valor | PIS | Motor fiscal | `product_taxes`/futura snapshot | `pis` hoje texto | REQUIRED_WHEN | grupo PIS | Snapshot | S1 | GAP |
| COFINS | `CST`/base/alíquota/valor | COFINS | Motor fiscal | `product_taxes`/futura snapshot | `cofins` hoje texto | REQUIRED_WHEN | grupo COFINS | Snapshot | S1 | GAP |
| IBSCBS | `CST` | CST IBS/CBS | Motor fiscal | futura regra/snapshot | `ibs_cbs_cst` | REQUIRED_WHEN | cronograma e operação RTC | Snapshot | S2/S3/S5 | GAP |
| IBSCBS | `cClassTrib` | classificação tributária | Tabela oficial/regra | futura versionada | `tax_classification_code` | REQUIRED_WHEN | CST/operação RTC | Snapshot | S3/S5 | GAP |
| IBSCBS | `vBC` | base IBS/CBS | Motor fiscal | futura snapshot | `ibs_cbs_base` | REQUIRED_WHEN | grupo informado | Snapshot | S3 | GAP |
| IBSCBS | `gIBSUF` | IBS estadual | Motor fiscal | futura snapshot | alíquotas/valores | REQUIRED_WHEN | regra RTC | Snapshot | S3/S5 | GAP |

### FISCAL-05 — TaxResolution

`TaxRuleResolver` agora representa CFOP final e os grupos ICMS, IPI, PIS, COFINS, IBS/CBS (incluindo `cClassTrib`) e IS com regra/versão/fonte/vigência explicáveis. Status: `TAX_RESOLUTION_IMPLEMENTED`; geração XML e conteúdo legal de produção permanecem `GAP`. Bases, alíquotas e valores somente existem quando fornecidos por regra versionada validada e são destinados a snapshot futuro.

### FISCAL-06 — Operação e snapshot

`fiscal_orders` implementa dados de `ide`, pagamento, transporte e totais como `OPERATION_DATA_IMPLEMENTED`. `fiscal_documents` congela `emit`, `dest`, `pag`, `transp`, totais e pendências; `fiscal_document_items` congela `det/prod`, valores e `imposto` como `SNAPSHOT_IMPLEMENTED`, com resolução `TAX_RESOLUTION_IMPLEMENTED` quando existe regra. `infAdic` usa observações do Pedido. Nenhum grupo possui status `XML_GENERATED`.
| IBSCBS | `gIBSMun` | IBS municipal | Motor fiscal | futura snapshot | alíquotas/valores | REQUIRED_WHEN | regra RTC | Snapshot | S3/S5 | GAP |
| IBSCBS | `gCBS` | CBS | Motor fiscal | futura snapshot | alíquotas/valores | REQUIRED_WHEN | regra RTC | Snapshot | S3/S5 | GAP |
| IS | grupo IS | Imposto Seletivo | Motor fiscal | futura snapshot | CST/base/alíquota/valor | REQUIRED_WHEN | produto/operação RTC | Snapshot | S3 | GAP |
| total | `ICMSTot` | totais clássicos | Agregação itens | futura `fiscal_document_totals` | campos estruturados | REQUIRED_ALWAYS | todo documento | Snapshot | S1 | GAP |
| total | `IBSCBSTot` | totais RTC | Agregação itens | futura `fiscal_document_totals` | campos estruturados | REQUIRED_WHEN | IBS/CBS aplicável | Snapshot | S3 | GAP |
| transp | `modFrete` | modalidade frete | Operação | futura `fiscal_transport_snapshots` | `freight_mode` | REQUIRED_ALWAYS | todo documento | Snapshot | S1 | GAP |
| transp | `transporta` | transportador | Transportadora | `transportadoras` | múltiplas | OPTIONAL | transportador informado | Snapshot | S1 | PARTIAL |
| transp | `veicTransp` | veículo | Transporte | futura | placa/UF/RNTC | REQUIRED_WHEN | transporte com veículo | Snapshot | S1 | PARTIAL |
| transp | `vol` | volumes | Operação | futura | quantidade/espécie/marca/peso | OPTIONAL | quando houver | Snapshot | S1 | GAP |
| cobr | `fat` | fatura | Cobrança | futura `fiscal_billing` | número/original/desconto/líquido | OPTIONAL | cobrança faturada | Snapshot | S1 | GAP |
| cobr | `dup` | duplicatas | Cobrança | futura `fiscal_installments` | número/vencimento/valor | OPTIONAL | quando houver | Snapshot | S1 | GAP |
| pag | `detPag/tPag` | meio pagamento | Pagamento | futura `fiscal_payments` | `method_code` | REQUIRED_WHEN | regras do modelo | Snapshot | S1 | GAP |
| pag | `detPag/vPag` | valor pagamento | Pagamento | futura `fiscal_payments` | `amount` | REQUIRED_WHEN | pagamento informado | Snapshot | S1 | GAP |
| pag | `card` | dados integração cartão | Pagamento | futura | integração/bandeira/autorização | REQUIRED_WHEN | cartão conforme regra | Snapshot | S1 | GAP |
| pag | `vTroco` | troco | Pagamento | futura `fiscal_payments` | `change_amount` | OPTIONAL | quando houver | Snapshot | S1 | GAP |
| infAdic | `infAdFisco` | informações ao fisco | Regra/operação | futura snapshot | `tax_authority_notes` | OPTIONAL | quando houver | Snapshot | S1 | GAP |
| infAdic | `infCpl` | informações complementares | Regra/operação | futura snapshot | `additional_info` | OPTIONAL | quando houver | Snapshot | S1 | GAP |

### FISCAL-06B-C1 — assinatura técnica de teste

O builder cobre estruturalmente ICMS, IPI, PIS, COFINS, IBS/CBS, `cClassTrib` e IS a partir do snapshot fornecido. Fixtures `TEST_ONLY` comprovaram CNPJ alfanumérico, assinatura e XSD oficial offline para modelos 55/65. Os códigos RTC usados são somente dados estruturais de teste e não constituem regra tributária de produção. Estados: `GENERATED_UNSIGNED -> SIGNED_TEST_ONLY -> XSD_VALID_TEST_ONLY`; produção, persistência e SEFAZ continuam `GAP`.

## Leitura do status

- `EXISTING`: há coluna útil, ainda dependente de round-trip e validação fiscal.
- `PARTIAL`: há dado aproximado/legado, mas fonte, formato ou completude não sustentam emissão.
- `GAP`: não há fonte persistente adequada.

Campos cadastrais não fiscais como nascimento, gênero, grupos comerciais, vendedor e limites de crédito permanecem no ERP, mas são `NOT_APPLICABLE` ao XML salvo quando alguma regra futura expressamente os promover a informação adicional.
# Defaults da empresa (FISCAL-CONFIG-01)

As configurações da Central Fiscal são fallback/hint e não alimentam diretamente XML histórico. A ordem é TaxRule versionada → produto/pessoa/contexto → default da empresa → `FISCAL_PENDING`. Na criação futura do documento, o resultado efetivamente resolvido deve ser copiado para `tax_context_json`/`tax_resolution_json` e snapshots; alterações posteriores nos defaults não podem alterar documentos congelados.
