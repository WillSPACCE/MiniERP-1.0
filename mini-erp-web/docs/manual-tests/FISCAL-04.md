# Teste manual FISCAL-04

1. Entrar no ERP Willyan Info e abrir Cadastro → Produtos.
2. Criar produto de teste preenchendo todos os campos visíveis, inclusive dois GTINs, NCM, CEST, origem, EX TIPI, cBenef, FCI, unidades, conversão, custo, venda e estoques.
3. Salvar, localizar na lista e reabrir a edição.
4. Conferir o round-trip de todos os valores; alterar descrição, NCM, GTIN, CEST, origem, unidade e preços; salvar e reabrir.
5. Conferir a linha no MariaDB exclusivamente em `mini_erp_tenant_14.produtos` e executar `ProductFiscalCompleteness`.
6. Confirmar ausência no MAIN e nos demais tenants.
7. Inativar a fixture e confirmar `status=inativo`; remover definitivamente somente a fixture manual autorizada após a evidência.

Não emitir XML, assinar, transmitir ou acessar SEFAZ.
