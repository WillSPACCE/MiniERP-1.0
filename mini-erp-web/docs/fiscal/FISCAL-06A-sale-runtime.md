# FISCAL-06A — Runtime funcional da Saída

`Pedidos` novo usa `fiscal_orders`; `vendas/itens_venda` continuam legado sem conversão. A listagem oferece abrir/editar, Espelhos e Documentos. Toda consulta exige o tenant do contexto; trocar IDs na URL não atravessa tenant.

UPDATE de Pedido é transacional, recalcula itens/totais e não baixa estoque. Se já houver Documento Interno, o Pedido recebe `DOCUMENT_OUTDATED`; snapshots anteriores permanecem históricos. Novo Espelho sempre cria versão. Nova versão fiscal exige ação e token deliberados.

Financeiro conserva condição, meio e vencimento; parcelas ficam em `ERP-FINANCE-01`. Transporte conserva modalidade, transportadora e motorista; volumes/pesos continuam backlog. Entrada permanece somente compatível, sem aprofundamento FISCAL-07.
