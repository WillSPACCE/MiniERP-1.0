# FISCAL-01D — Fechamento dos campos do emitente

Todos os campos solicitados possuem `USER_INPUT`, formulário funcional compartilhado por Painel/ERP, `EstablishmentData`, coluna em `establishments`, repository e round-trip. IEST, IM, CNAE, complemento e e-mail permanecem condicionais/opcionais. Nenhuma chave, total, CST/CSOSN, segredo ou retorno SEFAZ virou input.

| Tag XML | Campo UI | Coluna | Origem | Obrigatoriedade | Round-trip | Status |
|---|---|---|---|---|---|---|
| `emit/CNPJ` | CNPJ | `tax_id` | USER_INPUT | Sempre | Sim | IMPLEMENTED_DATA |
| `emit/xNome` | Razão social | `legal_name` | USER_INPUT | Sempre | Sim | IMPLEMENTED_DATA |
| `emit/xFant` | Nome fantasia | `trade_name` | USER_INPUT | Opcional | Sim | IMPLEMENTED_DATA |
| `emit/IE` | Inscrição estadual | `state_registration` | USER_INPUT | Sempre para emissão | Sim | IMPLEMENTED_DATA |
| `emit/IEST` | IEST | `st_registration` | USER_INPUT | Quando aplicável | Sim | IMPLEMENTED_DATA |
| `emit/IM` | Inscrição municipal | `municipal_registration` | USER_INPUT | Quando aplicável | Sim | IMPLEMENTED_DATA |
| `emit/CNAE` | CNAE | `cnae` | USER_INPUT | Quando aplicável | Sim | IMPLEMENTED_DATA |
| `emit/CRT` | CRT com descrição | `tax_regime_code` | USER_INPUT | Sempre | Sim | IMPLEMENTED_DATA |
| `enderEmit/xLgr` | Logradouro | `street` | USER_INPUT | Sempre | Sim | IMPLEMENTED_DATA |
| `enderEmit/nro` | Número | `number` | USER_INPUT | Sempre | Sim | IMPLEMENTED_DATA |
| `enderEmit/xCpl` | Complemento | `complement` | USER_INPUT | Opcional | Sim | IMPLEMENTED_DATA |
| `enderEmit/xBairro` | Bairro | `district` | USER_INPUT | Sempre | Sim | IMPLEMENTED_DATA |
| `enderEmit/cMun` | Código IBGE | `city_ibge_code` | USER_INPUT | Sempre | Sim | IMPLEMENTED_DATA |
| `enderEmit/xMun` | Município | `city_name` | USER_INPUT | Sempre | Sim | IMPLEMENTED_DATA |
| `enderEmit/UF` | UF | `state` | USER_INPUT | Sempre | Sim | IMPLEMENTED_DATA |
| `enderEmit/CEP` | CEP | `postal_code` | USER_INPUT | Conforme leiaute | Sim | IMPLEMENTED_DATA |
| `enderEmit/cPais` | Código do país | `country_code` | CONFIGURATION/USER_INPUT | Brasil=1058 | Sim | IMPLEMENTED_DATA |
| `enderEmit/xPais` | País | `country_name` | CONFIGURATION/USER_INPUT | Quando informado | Sim | IMPLEMENTED_DATA |
| `enderEmit/fone` | Telefone | `phone` | USER_INPUT | Opcional | Sim | IMPLEMENTED_DATA |

O XML real citado não estava disponível nos anexos desta execução. A estrutura foi conferida contra o mapa aprovado; nenhum dado real foi copiado.

## Backlogs derivados — não implementados

### FISCAL-03 — Pessoa/Destinatário

CPF/CNPJ, `idEstrangeiro`, nome/razão, `indIEDest`, IE, ISUF, IM, e-mail, telefone, endereço, IBGE, município, UF e país. Preservar pessoa física, aniversário, gênero, tipo, grupo, contatos e observações. Separar cadastro atual de snapshot do destinatário.

### FISCAL-04 — Produto

Código, GTIN comercial, descrição, NCM, CEST, EX TIPI, benefício fiscal, origem, unidade comercial/tributável, GTIN tributável e fator de conversão. Separar produto permanente × regra tributária × operação × snapshot.

### FISCAL-06 — Saída

Natureza, modelo, série, finalidade, consumidor final, presença, cliente, itens, quantidades, valores, desconto, frete, seguro, outras despesas, transporte, volumes, pagamento e observações. A lista será refinada contra o mapa/XML na task própria.

### FISCAL-07 — Entrada

Fornecedor, chave recebida, modelo, série, número, data, itens, quantidades, valores, tributos, transporte, totais e estoque. Prever importação futura de XML de entrada sem implementá-la agora.

### FISCAL-TECH-01 — Responsável Técnico / CSRT

Backlog separado para `infRespTec`: CNPJ do responsável, contato, e-mail, telefone, idCSRT e hashCSRT. Não pertence ao emitente, certificado A1 ou CSC.
