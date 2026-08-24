# Avaliação NFePHP (`nfephp-org/sped-nfe`)

Data da avaliação: 21/08/2026. Decisão: **ADOPT_WITH_CONDITIONS**. Nenhum pacote foi instalado.

## Evidência upstream

- Repositório oficial: https://github.com/nfephp-org/sped-nfe
- Releases: https://github.com/nfephp-org/sped-nfe/releases
- Guia: https://github.com/nfephp-org/sped-nfe/blob/master/docs/StartGuide.md
- Exemplo RTC: https://github.com/nfephp-org/sped-nfe/blob/master/examples/Example_RTC_XML.php
- Instalação upstream sugerida: Composer, linha estável `^5.0` segundo README atual.
- O projeto upstream apresenta suporte NF-e/NFC-e 4.00 e exemplo de tags RTC/IBS/CBS, inclusive `CST`, `cClassTrib`, IBS UF/município e CBS.

O repositório upstream não é fonte normativa. Compatibilidade fiscal deve ser demonstrada contra o pacote XSD/NT oficial fixado pelo projeto, não inferida apenas da existência de métodos na biblioteca.

## Ambiente local encontrado

- PHP CLI: 8.2.12 ZTS x64 (XAMPP).
- Não há `composer.json`, `composer.lock` ou `vendor/` no projeto.
- `composer` não está disponível no PATH.
- Extensões presentes relevantes: curl, dom, json, libxml, mbstring, openssl, PDO/MySQL, SimpleXML, xml/xmlreader/xmlwriter.
- Ausentes no inventário: soap, gd e zip; mcrypt também ausente e é extensão obsoleta. Requisitos reais devem ser confirmados pelo `composer.json` da release escolhida, não por documentação antiga.

## Condições para adoção

1. Selecionar uma release/tag estável específica após matriz de compatibilidade com PHP 8.2.
2. Conferir `composer.json` dessa tag e instalar extensões realmente exigidas.
3. Criar `composer.json`/lock reproduzíveis e processo de atualização controlado.
4. Executar testes contra NF-e 55, NFC-e 65, pacote oficial 010e, NT 2025.002 v1.50 e NT 2026.004 v1.01.
5. Comparar XML gerado byte/tag a tag com XSD e regras oficiais, cobrindo CNPJ numérico/alfanumérico e cenários RTC.
6. Encapsular biblioteca atrás de contratos próprios (`NfeXmlBuilderContract`, signer, gateway); domínio e DTOs não dependem das classes NFePHP.
7. Proibir transmissão real durante desenvolvimento; usar mocks e homologação somente após FISCAL-13.
8. Avaliar licenças da release e dependências com revisão jurídica/técnica.
9. Fixar e verificar pacote XSD local com checksums.
10. Fazer prova separada de certificado A1 fictício/de teste, sem segredo real no repositório.

## Motivos para não adotar imediatamente

- ambiente Composer incompleto;
- extensões possivelmente ausentes;
- modelo fiscal e snapshots ainda inexistentes;
- cadastros não sustentam DTO válido;
- regras RTC continuam versionadas e mudaram em 2026;
- nenhuma política de certificado, CSC, storage, séries ou homologação está implementada.

`sped-da` pode ser avaliado em FISCAL-14 após XML autorizado. Não deve dirigir o modelo de dados nem ser instalado nesta task.
