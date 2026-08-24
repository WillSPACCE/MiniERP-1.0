# Teste manual FISCAL-06B-C2

## Status atual

`BLOCKED` em runtime real. A prova local de assinatura e XSD foi validada em `FISCAL-06B-C1`, mas o pipeline de tenant/estabelecimento ainda não foi integrado à operação real do ERP.

## Roteiro real, quando reaberto

1. Fazer login do Platform Admin.
2. Abrir a empresa e confirmar o estabelecimento correto.
3. Importar ou cadastrar o certificado do estabelecimento em `Certificado Digital`.
4. Validar `certificate_ready` no backend.
5. Configurar série 55/65 em ambiente de Homologação.
6. Entrar no ERP e criar/abrir um pedido ou documento elegível.
7. Verificar que o documento fiscal interno gera `FISCAL_READY` e não `FISCAL_PENDING`.
8. Preparar XML.
9. Confirmar que `nNF`, `cNF`, chave, série, modelo e certificado do estabelecimento são preenchidos pelo backend.
10. Validar `FiscalDocumentDTO` usando snapshots.
11. Verificar XML unsigned em memória.
12. Validar assinatura A1.
13. Verificar `XMLDSig` com alteração do valor do item para falhar.
14. Validar XSD offline.
15. Confirmar artifact persistido fora de `public/`.
16. Validar SHA-256 e download seguro.
17. Conferir preview 55/65 e chave visível.
18. Confirmar mensagem de `SEM VALOR FISCAL` e `NÃO TRANSMITIDO À SEFAZ`.
19. Confirmar que nenhuma chamada SEFAZ foi executada.

## Critérios de bloqueio atuais

- sem `certificate_ready=true` validado no backend;
- sem série ativa em runtime real;
- sem número alocado de forma idempotente;
- sem storage seguro e artifact em produção;
- sem download protegido;
- sem QR/CSC reais;
- sem chamada SEFAZ.

Até estes critérios serem atendidos, o teste manual deve ser considerado como roteiro futuro e não como execução concluída.
