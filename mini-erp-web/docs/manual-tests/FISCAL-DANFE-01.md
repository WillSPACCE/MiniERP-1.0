# Teste manual FISCAL-DANFE-01

1. Autenticar no ERP do tenant correto.
2. Preparar um XML modelo 55 até `XSD_VALID_OFFLINE`.
3. Abrir `/fiscal_danfe.php?artifact_id=ID&mode=inline` e confirmar PDF A4 em nova guia.
4. Confirmar emitente, destinatário, itens, totais, série, número e chave.
5. Para XML offline, confirmar `NF-e NÃO PROTOCOLADA` e `SEM VALOR FISCAL` e ausência de protocolo.
6. Repetir com `mode=download` e confirmar attachment.
7. Em outro tenant, confirmar 404 para o mesmo artifact id.
