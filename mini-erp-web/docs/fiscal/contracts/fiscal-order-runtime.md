# Contrato de runtime do Pedido

Rotas de lista, abertura, update, Espelho e Documento recebem apenas o ID do recurso; `tenant_id` vem da autenticação. Estados amigáveis correspondem a `DRAFT`, `SAVED`, `CANCELLED`, `FISCAL_PENDING`, `FISCAL_READY` e `DOCUMENT_OUTDATED`. Nenhuma visualização usa termos “autorizada”, chave ou protocolo.
