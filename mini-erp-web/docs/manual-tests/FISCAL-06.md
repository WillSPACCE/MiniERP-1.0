# Teste manual FISCAL-06

1. Iniciar servidor, autenticar no tenant 14 e abrir Pedidos → Saída.
2. Selecionar cliente, Produto, quantidade, preço, desconto, frete, pagamento, vencimento e dados fiscais pretendidos.
3. Salvar Pedido, reabrir e conferir; confirmar que estoque não baixou.
4. Gravar Espelho, alterar cadastro/novo Pedido e conferir que o Espelho anterior não mudou.
5. Gravar Nota e conferir `FISCAL_PENDING` e pendências enquanto não houver regra real.
6. Em teste transacional, carregar regra `TEST_ONLY` e conferir `FISCAL_READY`, snapshots e idempotência.
7. Repetir em Entrada selecionando fornecedor; não importar XML ainda.
8. Conferir banco tenant, isolamento, zero dados no MAIN e ausência de XML/chave/protocolo/SEFAZ.
