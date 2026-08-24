# Teste manual FISCAL-06B-B

1. Conferir `resources/fiscal/xsd/nfe/manifest.json` e os SHA-256 dos ZIPs oficiais.
2. Executar `tests/FiscalXmlBuilderTest.php` e confirmar construção estrutural 55/65, CNPJ alfanumérico e ausência de assinatura/protocolo.
3. Confirmar que a validação oficial rejeita ambos pela ausência obrigatória de `ds:Signature`.
4. Confirmar que não existem botão Preparar XML, chave, download ou artifact novo no runtime.
5. Confirmar que migration 06B não foi aplicada e nenhum número foi reservado.
