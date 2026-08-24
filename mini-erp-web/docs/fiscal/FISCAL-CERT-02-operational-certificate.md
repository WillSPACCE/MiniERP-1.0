# FISCAL-CERT-02 — Fechamento operacional do certificado A1

## Visão geral

Esta tarefa fecha o ciclo operacional do certificado A1 em runtime local, mantendo a arquitetura fail-closed e sem comunicação com a SEFAZ. A garantia principal é que a senha do certificado e o material de arquivo PFX/P12 não sejam expostos no banco, em logs nem na interface.

## Origem da chave mestre

A aplicação aceita a chave mestre por ambiente externo (`FISCAL_SECRET_KEY`) e, quando ela não existe, gera uma chave local persistente em `storage/fiscal/master.key` fora de `public/`.

- `FISCAL_SECRET_KEY_SOURCE=ENV` quando a variável de ambiente está presente e válida;
- `FISCAL_SECRET_KEY_SOURCE=LOCAL_SECRET_FILE` quando a aplicação usa o arquivo de desenvolvimento local.

A chave é persistente entre processos e reaproveitada pelo mesmo arquivo em disco. Não é exposta em interface, log nem relatório.

## Storage Definido

- Certificados: `storage/fiscal/certificates/tenant-{tenant_id}/establishment-{establishment_id}`
- Segredos: `storage/fiscal/secrets/tenant-{tenant_id}/establishment-{establishment_id}`
- Chave mestre: `storage/fiscal/master.key`

Todos os diretórios ficam fora de `public/` e com permissões restritivas quando suportadas.

## Lifecycle do certificado

1. upload do PFX/P12 via UI;
2. validação offline do certificado (senha, estrutura, identidade, validade e assinatura criptográfica);
3. escrita no storage privado;
4. read-back do arquivo gravado;
5. cálculo de SHA-256 e comparação da entrada vs. leitura;
6. armazenamento do segredo criptografado em `LocalEncryptedSecretStorage`;
7. ativação do registro em `fiscal_certificates` com `storage_reference` e `secret_reference`;
8. read-back em processo seguinte para validação e assinatura TEST_ONLY;
9. desativação preservando histórico.

## Regras de segurança

- Sem senha em texto puro no banco;
- Sem BLOB do PFX/P12 no banco;
- Sem uso de SEFAZ em homologação/produção;
- Sem CSC ou QR Code fictícios;
- Sem stack trace para o usuário final;
- Sem `../` ou caminho absoluto arbitrário em storage.

## Read-back e ready

`certificate_ready` só torna-se verdadeiro quando todos os elos do pipeline passam:
- registro ativo existe;
- arquivo real existe no storage privado;
- read-back funciona;
- SHA-256 confere;
- segredo recupera com a chave mestre;
- PKCS#12 abre corretamente;
- chave privada existe;
- identidade corresponde ao estabelecimento;
- não expirou;
- teste criptográfico passa.

## Diagnóstico

Erros são convertidos para diagnósticos administráveis, como:
- `CERTIFICATE_PASSWORD_INVALID`
- `CERTIFICATE_FILE_INVALID`
- `CERTIFICATE_FILE_CORRUPTED`
- `CERTIFICATE_PRIVATE_KEY_MISSING`
- `CERTIFICATE_IDENTITY_MISMATCH`
- `CERTIFICATE_EXPIRED`
- `CERTIFICATE_STORAGE_READ_FAILED`
- `CERTIFICATE_HASH_MISMATCH`
- `CERTIFICATE_SECRET_STORAGE_FAILED`
- `CERTIFICATE_CRYPTO_TEST_FAILED`

## Resultado

A operação local é validada sem depender de autorização de produção ou comunicação com a SEFAZ.

FISCAL_SECRET_KEY_PERSISTENCE=PASS
