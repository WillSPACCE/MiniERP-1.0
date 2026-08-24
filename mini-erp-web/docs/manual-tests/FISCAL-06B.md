# Teste manual FISCAL-06B

## Estado atual bloqueado

1. Abrir um Documento `FISCAL_PENDING`: preparação deve permanecer indisponível.
2. Abrir um Documento `FISCAL_READY`: enquanto Composer/NFePHP não estiverem instalados e validados, não deve existir ação que alegue gerar XML.
3. Confirmar que a prévia mantém `SEM VALOR FISCAL`, chave indisponível, QR indisponível e nenhum protocolo.
4. Confirmar que nenhum arquivo foi criado em `public/` ou `storage/fiscal/` pelo fluxo HTTP.

## Após o desbloqueio do builder (task futura)

Executar o roteiro 55/65 de preparar, conferir chave de 44 posições, baixar XML não assinado, conferir snapshots/impostos/totais, ausência de `Signature`, `protNFe` e SEFAZ; repetir a preparação e confirmar mesmo número/chave/hash. Tentar documento de outro tenant e path adulterado. NFC-e continua sem QR Code até implementação oficial de CSC/QR.
