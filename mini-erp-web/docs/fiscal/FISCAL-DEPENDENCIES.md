# Dependências fiscais

| Package | Versão travada | Finalidade | Origem | PHP/extensões | Readiness |
|---|---|---|---|---|---|
| Composer | 2.10.2 | resolução reproduzível | getcomposer.org | PHP 7.2+ | OK |
| nfephp-org/sped-nfe | v5.2.8 | API NF-e/NFC-e | Packagist/GitHub oficial | PHP >=7.4; DOM, JSON, libxml, OpenSSL, SimpleXML, SOAP, zlib | API OK; XSD vigente não comprovado |
| nfephp-org/sped-common | v5.1.17 | chave, assinatura e utilitários | transitiva/Packagist | conforme lock | OK para chave alfanumérica |
| nfephp-org/sped-gtin | v1.1.2 | validação GTIN | transitiva/Packagist | conforme lock | instalado |
| justinrainbow/json-schema | 5.3.4 | configuração JSON | transitiva/Packagist | conforme lock | instalado |
| psr/log | 3.0.2 | contrato de log | transitiva/Packagist | PHP >=8 | instalado |

`vendor/` permanece ignorado; `composer.json` e `composer.lock` são a fonte reproduzível. O runtime fiscal deve usar `vendor/autoload.php` por um bootstrap central quando o builder for liberado.

## XSD offline

Download somente administrativo a partir do Portal NF-e, com identificador do pacote, data, URL oficial e SHA-256 registrados. O pacote aprovado deve ficar em storage/versionamento controlado e ser validado no deploy. Emissão nunca baixa schemas da internet.

## sped-da

Não instalado nesta task. A extensão GD oficial está habilitada. Antes de uso, selecionar release estável, lockar pelo Composer e comprovar compatibilidade com chave alfanumérica e leiautes 55/65 vigentes.
