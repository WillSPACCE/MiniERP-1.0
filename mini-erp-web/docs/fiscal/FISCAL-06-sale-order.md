# FISCAL-06 — Pedido, Espelho e Documento Fiscal Interno

Fluxo legado: formulário `Pedidos` chamava `save_venda → Repository::createSale → vendas/itens_venda → baixa imediata de estoque`. Entrada usava a mesma tela, sem persistência própria; botões fiscal/financeiro eram visuais.

Fluxo novo: `Salvar Pedido → fiscal_orders/items`; `Gravar Espelho → snapshot versionado imutável`; `Gravar Nota → documento interno e snapshots transacionais`. Nenhuma ação gera XML, chave, nNF, protocolo ou comunicação SEFAZ. `vendas` e sua baixa legada foram preservadas, mas não são usadas pelo novo fluxo.

Sem regra fiscal, o documento é `FISCAL_PENDING`. Somente dados completos e TaxResolution de todos os itens produzem `FISCAL_READY`. Entrada usa `operation_type=ENTRY` e a mesma arquitetura; importação de XML recebido permanece futura e jamais deverá ter tributos sobrescritos silenciosamente pelo TaxEngine.

Futuro: `FISCAL_READY → gerar XML → validar XSD → assinar → transmitir → autorização/protocolo → XML autorizado → DANFE`.

FISCAL-06A fechou o runtime: listagem de `fiscal_orders`, abertura/edição no formulário existente, update transacional, Espelhos v1/v2 visualizáveis, Documento Interno somente leitura e painel de pendências. Alteração após documento marca `DOCUMENT_OUTDATED` sem tocar no snapshot histórico.

ERP-FIX-02 adicionou previews distintos: A4 para modelo 55 e cupom para modelo 65, sempre a partir dos snapshots e com artefatos fiscais indisponíveis até pipeline real.
