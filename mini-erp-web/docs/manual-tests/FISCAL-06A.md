# Teste manual FISCAL-06A

1. Iniciar servidor, entrar na Willyan Info e abrir Pedidos → Saída.
2. Criar Pedido com cliente, Produto e pagamento; salvar.
3. Abrir a lista Pedidos, localizar, abrir/editar, alterar quantidade e salvar.
4. Criar Espelho #1 e visualizar/imprimir; alterar Pedido; confirmar #1 imutável; criar #2.
5. Gravar Nota, abrir Documento Interno e conferir emitente, destinatário, itens, tributação, totais e pendências.
6. Alterar Pedido após Documento e conferir `DOCUMENT_OUTDATED`; preparar nova versão somente deliberadamente.
7. Alterar ID na URL para recurso de outro tenant e confirmar acesso negado.
8. Confirmar estoque inalterado, MAIN sem Pedido, nenhum XML/chave/protocolo e nenhuma SEFAZ.
