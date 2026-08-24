# FISCAL-06B-C2 — base operacional offline do pipeline fiscal

## Status

`BLOCKED`.

A prova de conceito local da assinatura XML e do XSD foi concluída em `FISCAL-06B-C1`, mas o pipeline real do documento fiscal ainda não está integrado ao runtime do tenant e do estabelecimento. Não há evidência de `certificate_ready`, `FiscalDocumentDTO` montado a partir de snapshots reais, reserva de número persistente, artifact persistido fora de `public/`, ou download seguro validado para o usuário/tenant correto.

## O que já existe

- `FiscalDocumentDTO` com snapshots e fail-closed para status `FISCAL_PENDING`;
- `NfeAccessKeyGenerator` com DV e CNPJ alfanumérico suportado;
- `FiscalNumericCodeGenerator` e `FiscalNumberAllocator` em estrutura planejada/isolada;
- `FiscalNfeXmlBuilder` gerando XML unsigned em memória;
- `FiscalXmlSigner` validado em teste local `TEST_ONLY`;
- `OfficialNfeXsdValidator` validando XSD local sem runtime;
- `FiscalArtifactStorage` com referência opaca e SHA-256 calculado em memória/test-only.

## O que ainda não está validado em runtime

- certificado ativo do estabelecimento e `certificate_ready=true` no backend;
- `SecretStorage` recuperando senha do PFX do estabelecimento no fluxo real;
- resolução de série por tenant + establishment + modelo + ambiente;
- alocação idempotente de `nNF` e `cNF` no banco;
- concatenado de `FiscalDocumentDTO` + snapshots imutáveis;
- XML unsigned + assinatura A1 do certificado real do estabelecimento;
- XMLDSig verificada sobre XML persistido;
- XSD oficiaI validando arquivo final do artifact;
- storage fora de `public/` e `download` protegido;
- preview com chave legível e sem valor fiscal;
- IDOR, concorrência, falhas pós-reserva e substituição de certificado.

## Decisão de arquitetura

A task deve seguir estritamente a regra da plataforma: o tenant e o estabelecimento devem ser resolvidos a partir do contexto autenticado e do documento fiscal, nunca por `tenant_id`, `establishment_id`, `db_name` vindo da interface. Produção continua bloqueada. A única execução permitida nesta etapa é `tpAmb=2`/Homologação e sem SEFAZ.

## Próximo gate

Somente quando a fixture controlada mostrar:

- certificado do estabelecimento válido e ativo;
- senha recuperada do storage;
- série real resolvida;
- número/alocação persistente;
- chave 44 dígitos;
- DTO sobre snapshots congelados;
- XML unsigned em memória;
- assinatura válida;
- XMLDSig válido;
- XSD válido offline;
- artifact persistido com SHA-256;
- download seguro;
- preview 55/65 com chave e sem valor fiscal;

é que a task pode ser reaberta.
