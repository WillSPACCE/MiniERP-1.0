# FISCAL-06B-C1 — assinatura XML TEST_ONLY

Status: **CONCLUÍDA como prova técnica local**. Não é emissão, homologação nem produção.

## Pipeline comprovado

`FiscalDocumentDTO -> FiscalNfeXmlBuilder -> GENERATED_UNSIGNED -> FiscalXmlSigner -> SIGNED_TEST_ONLY -> OfficialNfeXsdValidator -> XSD_VALID_TEST_ONLY`

O signer usa as APIs oficiais do NFePHP v5.2.8: `NFePHP\Common\Certificate::readPfx()` e `NFePHP\Common\Signer::sign()`, apontando `Reference` para o `Id` de `infNFe`. O algoritmo usado pela integração NF-e da biblioteca é RSA-SHA1 com digest SHA-1 e canonicalização XMLDSig. Nenhum arquivo de `vendor/` foi alterado.

## Certificado e segurança

O teste gera localmente RSA 2048, CSR, X.509 autassinado de dois dias e PFX via extensão OpenSSL do PHP, com subject `CN=MINIERP FISCAL TEST ONLY` e organização `TEST ONLY - NO LEGAL VALUE`. A senha aleatória, chave, certificado e PFX vivem somente em memória e são descartados ao fim. Nada é gravado em `public/`, storage ou Git; `.gitignore` protege eventuais artefatos locais `*.test-only.pfx/p12` e `storage/test-secrets/`.

## Evidências e limites

NF-e 55 e NFC-e 65 unsigned são corretamente rejeitadas pelo XSD completo pela ausência de `ds:Signature`. Após assinatura, ambas passam no schema oficial local `010e v1.02` e na verificação XMLDSig. `infNFe` canonicalizado permanece idêntico antes/depois. Uma alteração posterior de valor invalida a assinatura.

As fixtures cobrem CNPJ alfanumérico, ICMS, IPI, PIS e COFINS. Um cenário estrutural separado cobre IBS, CBS, `cClassTrib` e IS; seus códigos e valores são exclusivamente `TEST_ONLY`, sem pretensão de regra fiscal real.

Não houve banco, reserva de numeração, persistência, certificado real, CSC/QR, chamada SEFAZ, protocolo, autorização, DANFE oficial ou habilitação de UI.
