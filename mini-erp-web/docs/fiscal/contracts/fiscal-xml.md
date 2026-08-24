# Contrato do XML fiscal

`FiscalNfeXmlBuilder` recebe somente `FiscalDocumentDTO` congelado e identidade fiscal reservada pelo backend. Ele não consulta cadastros nem aceita modelo, série, número ou chave da URL.

Estados permitidos nesta fronteira: `XML_GENERATED_UNSIGNED` e, somente após validação oficial completa, `XSD_VALID`. O pacote oficial atual exige `ds:Signature`; logo nenhum documento unsigned pode receber `XSD_VALID`.

São proibidos: protocolo, `cStat`, recibo, QR/CSC inventado, certificado, transmissão e alteração silenciosa de XML anterior.
