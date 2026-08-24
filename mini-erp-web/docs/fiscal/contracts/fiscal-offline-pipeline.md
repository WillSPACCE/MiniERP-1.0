# Contrato do pipeline fiscal offline

## Escopo

Este contrato define a fronteira técnica do pipeline fiscal OFFLINE do Mini ERP:

`Documento Fiscal Interno -> validação de elegibilidade -> certificado ativo -> série -> número -> chave -> DTO -> XML unsigned -> assinatura -> XMLDSig -> XSD -> artifact -> download`

## Status implementado no runtime local

A execução local do pipeline foi consolidada com os seguintes componentes:

- `OfflineFiscalDocumentPipelineService` orquestra o fluxo completo do documento fiscal em um único serviço de runtime, sem transmissão para a SEFAZ.
- `FiscalDocumentDTOFactory` cria `FiscalDocumentDTO` a partir de snapshots persistidos, sem reconsultar registros mutáveis.
- `OperationalCertificateProvider` valida certificado operacional para o tenant/estabelecimento e expõe `certificateReady()` e `resolveOperationalCertificate()`.
- `FiscalArtifactStorage` persiste XML assinado fora de `public/` e garante integridade por SHA-256.
- `FiscalNumberAllocator` aloca a numeração fiscal em um fluxo seguro por tenant + estabelecimento + modelo + série.

## Regras fundamentais

1. `tpAmb` só pode ser `2` (Homologação). `tpAmb=1` é bloqueado no backend.
2. O documento fiscal deve ser resolvido por contexto autenticado e snapshot imutável, nunca pela interface.
3. O certificado deve vir do estabelecimento e do secret storage; a interface não recebe a senha novamente.
4. A série deve ser resolvida obrigatoriamente por tenant + estabelecimento + modelo + ambiente.
5. O número fiscal deve ser alocado pelo `FiscalNumberAllocator`, jamais por `MAX(nNF)+1`, `document_id` ou `order_id`.
6. `FiscalDocumentDTO` deve usar snapshots congelados e nunca reconsultar cadastros mutáveis.
7. `FiscalNfeXmlBuilder` produz XML em memória somente; ele não decide storage, chave ou download.
8. XML validado por XSD representa estrutura válida; não confunde autorização SEFAZ.
9. `artifact` persistido é um arquivo final offline, fora de `public/`, com metadados e SHA-256.
10. Download e preview exigem documento, tenant, artifact e storage válidos de forma segura.
11. Nenhum componente do runtime pode disparar transmissão de nota para a SEFAZ; a chamada de autorização fica explicitamente fora do escopo deste fluxo.

## Estados esperados

- `FISCAL_PENDING` quando faltam dados ou tributos;
- `NUMBER_RESERVED` quando a numeração foi alocada;
- `XML_GENERATED_UNSIGNED` quando o XML foi montado em memória;
- `SIGNED_OFFLINE` após assinatura local;
- `XMLDSIG_VALID` após verificação matemática;
- `XSD_VALID_OFFLINE` após schema local oficial;
- `ARTIFACT_CREATED` quando metadata e arquivo final foram persistidos.

Estados reservados para tarefas futuras e nunca usados nesta task:

- `SENT`, `AUTHORIZED`, `REJECTED`, `DENIED`, `CANCELLED_SEFAZ`.

## Proibição

- chamada SEFAZ;
- QR Code inventado;
- `AUTHORIZED` ou protocolo;
- produção habilitada;
- `pdf`/DANFE com valor fiscal definitivo;
- uso de certificado real não informado explicitamente pelo usuário;
- salto de contexto para documentar dados de runtime vindos diretamente da interface em vez dos snapshots do documento.

## Evidência de validação local

A validação executada no ambiente local confirmou o runtime mínimo do pipeline fiscal offline:

- `tests/FiscalOfflinePipelineRuntimeTest.php` em execução local retorna `FiscalOfflinePipelineRuntime OK`.
- `php -l` dos serviços de runtime e certificados confirmou ausência de erros de sintaxe.
- `tests/Fiscal06BFoundationTest.php` e `tests/FiscalXmlBuilderTest.php` continuam OK.

A task só avança para status de conclusão quando a automação de runtime estiver validada também contra dados reais de banco e certificados operacionais do tenant, sem qualquer chamada de autorização SEFAZ.
