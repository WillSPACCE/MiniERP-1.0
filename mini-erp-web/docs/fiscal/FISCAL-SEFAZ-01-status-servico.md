# FISCAL-SEFAZ-01 — StatusServico em homologação

## Limite operacional

Esta implementação permite exclusivamente `NFeStatusServico4`, ambiente `tpAmb=2`, modelo 55. Não há código de autorização, retorno de autorização, consulta de protocolo, evento ou inutilização no adapter.

O teste real somente ocorre após seleção explícita da empresa na Plataforma, certificado A1 operacional e clique confirmado em **Testar comunicação SEFAZ**. Nenhum teste real é disparado por suíte, cron ou carregamento da página.

## Toolchain auditada

- PHP 8.2.12, OpenSSL 3.0.11, extensões OpenSSL/cURL/SOAP habilitadas.
- CA bundle: `C:\xampp\apache\bin\curl-ca-bundle.crt` configurado em `openssl.cafile` e `curl.cainfo`.
- `nfephp-org/sped-nfe` 5.2.8; `sped-common` 5.1.17; `sped-da` 1.1.6.
- API: `new NFePHP\NFe\Tools($configJson, $certificate)`, `model(55)` e `sefazStatus($uf, 2, true)`.
- Transporte: `SoapNative`, timeout de 15 segundos e `disableSecurity(false)`. A validação TLS permanece habilitada.
- Endpoints: tabela `Webservices` do próprio sped-nfe, identificada no resultado como `NFePHP_WEBSERVICES_TABLE`; nenhuma URL foi duplicada no MiniERP.

As fontes oficiais conferidas foram a [relação nacional de Web Services NF-e](https://www.nfe.fazenda.gov.br/portal/webservices.aspx?AspxAutoDetectCookieSupport=1) e, para SP, a [relação oficial da SEFAZ/SP](https://portal.fazenda.sp.gov.br/servicos/nfe/Paginas/URL-WEBSERVICES.aspx). Ambas publicam `NfeStatusServico` versão 4.00 e distinguem homologação de produção.

## Preflight e segurança

Antes da rede são verificados tenant, estabelecimento, cadastro/endereço/identidade fiscal, UF, série 55 ativa em homologação e read-back do certificado instalado. O provider relê PFX e segredo do storage privado, valida PKCS#12, chave privada, validade e identidade CNPJ. Produção é bloqueada antes do cliente; certificado ausente/expirado/divergente e UF inválida não abrem rede.

A auditoria usa `fiscal_certificate_audit`: `SEFAZ_STATUS_REQUESTED`, `SEFAZ_STATUS_OK` e `SEFAZ_STATUS_FAILED`. Guarda apenas tenant implícito, estabelecimento, certificado, ator, UF, ambiente, serviço, resultado, latência e horário. Não guarda PFX, senha, chave privada, master key, certificado ou SOAP completo. O rate limit é uma consulta por estabelecimento a cada 15 segundos.

## Estado do aceite

A suíte fake cobre 107, 108, 109, timeout, TLS, DNS, XML inválido, certificado ausente/expirado/divergente, UF, produção, tenant e rate limit. `RUN_REAL_SEFAZ_HOMOLOGATION_TESTS` não escolhe empresa; o teste operacional deve ser iniciado na Central Fiscal autenticada.

Implementação técnica: concluída. Aceite real SEFAZ: pendente de ação explícita do usuário.
