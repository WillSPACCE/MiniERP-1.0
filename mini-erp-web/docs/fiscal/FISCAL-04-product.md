# FISCAL-04 — Produto comercial e fiscal

`produtos` é o cadastro canônico no data-plane. Campos permanentes alimentam o futuro TaxEngine, mas não armazenam imposto final da operação. O produto comercial pode ser salvo incompleto; `ProductFiscalCompleteness` informa pendências de NCM, origem, unidades e conversão.

`product_taxes` foi preservada sem migração destrutiva. Seus textos `ipi`, `icms`, `pis` e `cofins` são LEGACY_DATA: não têm contexto, vigência, CST, base ou operação e não são fonte autoritativa para emissão. CFOP padrão é somente sugestão.

A remoção passou a inativar. Entrada e Saída futuras selecionarão `product_id`, código, descrição, preço, unidade, estoque e status. Documento fiscal futuro deverá materializar snapshot integral do item.
