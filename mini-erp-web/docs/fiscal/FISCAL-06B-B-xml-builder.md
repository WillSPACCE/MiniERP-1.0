# FISCAL-06B-B — XSD oficial e builder XML

## Continuidade em FISCAL-06B-C1

O bloqueio foi tecnicamente fechado por assinatura XMLDSig exclusivamente `TEST_ONLY`: XMLs 55 e 65 seguem `BUILD -> SIGN -> XSD VALIDATE` e passam no pacote oficial local `010e v1.02`. O unsigned continua corretamente `GENERATED_UNSIGNED` e rejeitado pelo schema completo. Isso não habilita certificado real, QR/CSC, SEFAZ, protocolo, autorização ou emissão.

Decisão em 2026-08-21: **BLOCKED**.

Foram obtidos diretamente do Portal Nacional NF-e os pacotes oficiais `010e v1.02` e `010d v1.03`, versionados intactos em `resources/fiscal/xsd/nfe/`. O `010e v1.02` foi selecionado para NF-e/NFC-e 4.00 porque contém RTC (`IBS`, `CBS`, `IS`, `cClassTrib`) e os tipos alfanuméricos de CNPJ/chave.

O builder encapsulado `FiscalNfeXmlBuilder` demonstrou construção estrutural unsigned para modelos 55 e 65, exclusivamente a partir do DTO/snapshots, com CFOP da resolução tributária e CNPJ alfanumérico preservado. Nenhum XML de teste foi persistido.

## Bloqueio normativo

O tipo `TNFe` do XSD oficial `010e v1.02/NFe/leiauteNFe_v4.00.xsd` exige `ds:Signature` (`minOccurs` implícito igual a 1). Consequentemente, `nfe_v4.00.xsd` rejeita corretamente uma `NFe` unsigned com “Missing child element ... Signature”. A task simultaneamente proíbe assinatura e exige XML completo XSD válido; os critérios são incompatíveis.

Não foi inserida assinatura fictícia, não foi modificado XSD/vendor e não foi reclassificado XML unsigned como `XSD_VALID`. Migration, série, reserva, storage HTTP e UI não foram ativados. A próxima task deve antecipar a assinatura controlada ou aprovar formalmente validação estrutural parcial de `infNFe` como estado distinto de XSD válido.
