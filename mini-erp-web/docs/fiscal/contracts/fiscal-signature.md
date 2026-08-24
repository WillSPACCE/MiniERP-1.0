# Contrato de assinatura fiscal

## Fronteira atual: TEST_ONLY

Entrada: XML `GENERATED_UNSIGNED`, conteúdo binário PFX de teste e senha efêmera. Saída: XML, status `SIGNED_TEST_ONLY`, hashes SHA-256 unsigned/signed, URI `Reference` e subject técnico. Invariantes: `Reference` deve apontar para `infNFe@Id`; XMLDSig deve ser verificável; `infNFe` não pode mudar; falhas de PFX, senha, XML ou assinatura são fail-closed.

`XSD_VALID_TEST_ONLY` somente pode ser atribuído depois de `BUILD -> SIGN -> XSD VALIDATE` com schema oficial local. Esses estados nunca equivalem a `SIGNED_PRODUCTION`, `SENT` ou `AUTHORIZED`.

## Contrato futuro A1

`FISCAL-06B-C2`/`FISCAL-02` deverá definir custódia segura por estabelecimento/tenant, criptografia em repouso, segredo fora do código e logs, autorização, validade/rotação/revogação, auditoria e isolamento. Upload, armazenamento e uso de PFX/P12 real não fazem parte desta task.
