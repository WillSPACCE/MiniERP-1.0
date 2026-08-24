# Contrato fiscal de Produto

Identificador canônico: `product_id` = `produtos.id` no banco dedicado do tenant. O cadastro é compartilhado por Entrada e Saída; não existe segundo cadastro para compras.

| Campo ERP | Coluna | Tag XML / uso | Origem | Obrigatoriedade | Round-trip | Status |
|---|---|---|---|---|---|---|
| `codigo` | `produtos.codigo` | `det/prod/cProd` | PRODUCT_MASTER | sempre | sim | IMPLEMENTED_DATA |
| `nome` | `produtos.nome` | `det/prod/xProd` | PRODUCT_MASTER | sempre | sim | IMPLEMENTED_DATA |
| `gtin` | `produtos.gtin` | `det/prod/cEAN` | PRODUCT_MASTER | GTIN ou `SEM GTIN` | sim | IMPLEMENTED_DATA |
| `gtin_tributable` | coluna homônima | `det/prod/cEANTrib` | PRODUCT_MASTER | GTIN ou `SEM GTIN` | sim | IMPLEMENTED_DATA |
| `ncm` | `produtos.ncm` | `det/prod/NCM` | PRODUCT_MASTER | uso fiscal | sim | IMPLEMENTED_DATA |
| `cest` | `produtos.cest` | `det/prod/CEST` | PRODUCT_MASTER | quando aplicável | sim | IMPLEMENTED_DATA |
| `extipi` | coluna homônima | `det/prod/EXTIPI` | PRODUCT_MASTER | quando aplicável | sim | IMPLEMENTED_DATA |
| `tax_benefit_code` | coluna homônima | `det/prod/cBenef` | PRODUCT_MASTER/TAX_RULE | quando regulamentado | sim | IMPLEMENTED_DATA |
| `merchandise_origin` | coluna homônima | entrada para ICMS/orig | PRODUCT_MASTER | uso fiscal | sim | IMPLEMENTED_DATA |
| `fci_number` | coluna homônima | `det/prod/nFCI` | PRODUCT_MASTER | quando aplicável | sim | IMPLEMENTED_DATA |
| `unidade` | `produtos.unidade` | `det/prod/uCom` | PRODUCT_MASTER | sempre | sim | IMPLEMENTED_DATA |
| `taxable_unit` | coluna homônima | `det/prod/uTrib` | PRODUCT_MASTER | uso fiscal | sim | IMPLEMENTED_DATA |
| `conversion_factor` | coluna homônima | converte `qCom` → `qTrib` | PRODUCT_MASTER | maior que zero | sim | IMPLEMENTED_DATA |
| `cfop_padrao` | coluna homônima | sugestão para `det/prod/CFOP` | TAX_RULE | opcional; nunca final universal | sim | IMPLEMENTED_DATA |
| `categoria` | coluna homônima | seleção comercial | PRODUCT_MASTER | opcional | sim | EXISTING_ERP |
| `cost_price` | coluna homônima | custo comercial | PRODUCT_MASTER | opcional | sim | IMPLEMENTED_DATA |
| `preco` | `produtos.preco` | sugestão de `vUnCom` | PRODUCT_MASTER | comercial | sim | EXISTING_ERP |
| `estoque_atual` | coluna homônima | disponibilidade | CALCULATED/ESTOQUE | comercial | sim | EXISTING_ERP |
| `minimum_stock` | coluna homônima | alerta de estoque | PRODUCT_MASTER | opcional | sim | IMPLEMENTED_DATA |
| `status` | coluna homônima | seleção Entrada/Saída | PRODUCT_MASTER | sempre | sim | EXISTING_ERP |

`qCom`, `vUnCom` final, `vProd`, `qTrib`, `vUnTrib`, `vFrete`, `vSeg`, `vDesc`, `vOutro`, `indTot`, `xPed` e `nItemPed` são OPERATION_INPUT/CALCULATED. CFOP final, CST/CSOSN, bases, alíquotas, PIS, COFINS, IPI, IBS, CBS e `cClassTrib` são TAX_RULE/resultado do futuro TaxEngine. Todos os valores efetivamente usados serão SNAPSHOT do item fiscal; editar o Produto não alterará documento histórico.
