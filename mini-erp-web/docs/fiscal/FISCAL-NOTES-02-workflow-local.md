# FISCAL-NOTES-02 — workflow fiscal local

**Status: CONCLUÍDA pelo Prompt nº 027.** O Prompt nº 026 permaneceu corretamente registrado como bloqueado naquele momento; os dois critérios restantes foram fechados posteriormente por testes HTTP concorrentes e pela matriz automatizada de logo DANFE.

## Operação

- **Gravar Espelho** persiste um snapshot interno imutável, abre `/fiscal_mirror.php` em uma nova guia e não cria reserva, nNF, cNF, chave, protocolo ou autorização.
- **Gravar Nota** cria/reutiliza o Documento pelo token de idempotência e executa `OfflineFiscalDocumentPipelineService` automaticamente.
- O pipeline usa o storage privado definitivo, valida certificado, assinatura, XMLDSig, XSD e SHA-256. O estado final canônico é `PENDING_TRANSMISSION` (equivalente a “Aguardando transmissão” e compatível com a coluna de 20 caracteres).
- **Tentar novamente** reutiliza Documento, reserva e artifact válidos. Os eventos de retry e do pipeline são somente anexados.
- O navegador abre o placeholder antes do `fetch`; em sucesso navega para o DANFE e, em erro, fecha a guia e destaca o Documento na Central.
- O formulário permanece na página quando há falha anterior à criação. Pedido, itens, pagamento e transporte nunca são apagados pelo pipeline.

## Endpoints

- `POST /fiscal_action.php`: `note`, `mirror` e `retry`; resposta JSON sem paths internos ou dados de conexão.
- `GET /fiscal_xml.php?artifact_id=…&mode=inline|download`: tenant/session, referência somente do repositório e SHA-256 obrigatório.
- `GET /fiscal_danfe.php?artifact_id=…&mode=inline|download`: modelo 55, cache privado e verificação de integridade.
- `GET /fiscal_mirror.php?mirror_id=…`: prévia HTML “SEM VALOR FISCAL”, sem numeração definitiva.

## Segurança e limites

Todos os acessos são resolvidos pelo tenant autenticado. Referências arbitrárias de arquivo são rejeitadas. PFX, senha, chave privada, master key e CSC não entram em eventos ou respostas. Modelo 65 pode ser listado, mas DANFC-e não faz parte desta entrega.

As actions fiscais exigem sessão autenticada e cookie CSRF aleatório, `HttpOnly` e `SameSite=Strict`, comparado em tempo constante com o token da sessão. A sessão é liberada antes do pipeline para que a idempotência do backend seja efetivamente exercitada.

Logos DANFE são resolvidas exclusivamente por `FiscalDanfeLogoStorage`, sob o prefixo do tenant. Path traversal, paths absolutos e referências cross-tenant são bloqueados. Logo ausente ou inválida gera fallback sem logo e `DANFE_LOGO_UNAVAILABLE`, sem invalidar o XML.

Não existe chamada a `StatusServico`, `Autorizacao`, `RetAutorizacao` ou `ConsultaProtocolo`. O fluxo não cria status `AUTHORIZED`.

## Banco e rollout

Nenhuma estrutura nova foi necessária além de `fiscal_document_events`; portanto não houve migration, DDL ou backup adicional nos tenants 5/14.
