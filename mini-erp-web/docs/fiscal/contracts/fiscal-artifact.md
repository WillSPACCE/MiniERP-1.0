# Contrato de artefato fiscal

Estados em ordem: `GENERATED_UNSIGNED`, `XSD_VALID`, `SIGNED`, `SENT`, `AUTHORIZED`. O fluxo off-line deste projeto é concluído no ponto `SIGNED` e nunca alcança `SENT`/`AUTHORIZED` porque a comunicação com a SEFAZ permanece fora do escopo e bloqueada.

A implementação atual valida o caminho do artefato em runtime e persiste o XML assinado fora de `public/`, em um diretório tenant/estabelecimento/documento controlado e protegido. O `FiscalArtifactStorage` foi estendido para suportar `storeSignedXml()`, `read()` e `assertIntegrity()` com verificação de SHA-256.

Metadados ficam em `fiscal_artifacts`: tenant, estabelecimento, documento, tipo, estado, referência opaca, SHA-256, tamanho, autor e horário. O conteúdo fica em `storage/fiscal/tenant-{id}/establishment-{id}/document-{id}/generated/`, fora de `public/`.

Download futuro consulta o artefato por tenant e documento no backend, resolve apenas a referência persistida sob a raiz configurada, valida SHA-256 e responde `application/xml` com attachment `NFe-{access_key}.xml` ou `NFe-{access_key}-assinado.xml`. A UI nunca envia caminho. Referência absoluta, `..`, artefato cross-tenant ou hash divergente devem falhar.

## Regra de segurança

- `artifact` nunca pode ser gerado a partir de um caminho informado pela UI;
- a raiz de storage é fixa e validada pelo backend;
- o XML assinado só pode ser persistido após `FiscalXmlSigner` e `OfficialNfeXsdValidator` validarem a cadeia local;
- o serviço de runtime não faz autenticação na SEFAZ e mantém `tpAmb=2` em homologação.

## Evidência operacional

- `OfflineFiscalDocumentPipelineService` integra a criação do DTO, a escolha do certificado, a série, a reserva do número e o armazenamento do XML final.
- `FiscalOfflinePipelineRuntimeTest.php` confirma a criação do DTO a partir de snapshots sem consulta viva de registros.
- `php -l` em `OfflineFiscalDocumentPipelineService.php`, `FiscalDocumentDTOFactory.php`, `OperationalCertificateProvider.php` e `FiscalArtifactStorage.php` confirmou ausência de erros de sintaxe.
