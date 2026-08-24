# FISCAL-CERT-01 — validação segura de certificado A1

## Objetivo

Corrigir a cadeia de validação do upload de certificado A1 para que a senha, o arquivo, a identidade e a validade sejam avaliados antes de qualquer persistência.

## Regras de segurança

- o formulário deve usar `enctype="multipart/form-data"`;
- o arquivo deve ser PFX/P12 e não ultrapassar 5 MB;
- a senha nunca deve ser gravada em banco, log ou armazenamento de metadados;
- a validação deve ocorrer antes de ativar o certificado;
- a identidade do certificado deve coincidir com o `tax_id` do estabelecimento;
- a validade deve ser verificada antes da ativação e o histórico preservado em caso de falha;
- erro de senha, arquivo corrompido, identidade divergente e expiração devem ser diagnosticos distintos e seguros.

## Fluxo operacional

1. Receber upload do PFX/P12 e a senha em `empresa-fiscal-config.php`.
2. Verificar extensão e tamanho do arquivo.
3. Validar presença da senha e tentativas de leitura com `Certificate::readPfx()`.
4. Extrair o X.509 e confirmar que a chave pública está íntegra.
5. Comparar o CNPJ/CPF do certificado com o `tax_id` do estabelecimento.
6. Validar cronologia de validade e executar teste criptográfico local.
7. Persistir apenas após sucesso e manter o certificado anterior em histórico.
8. Desativar somente com PlatformAdmin + CSRF + motivo administrativo.

## Diagnóstico de retorno

O inspetor retorna um payload com campos estruturados, incluindo:

- `code`: código estável do resultado (`CERTIFICATE_VALID`, `CERTIFICATE_EXPIRING_SOON`, `CERTIFICATE_EXPIRED` e variações de falha);
- `message`: mensagem curta para logs e UI;
- `diagnostic`: descrição amigável sem expor segredos do certificado;
- metadados do certificado (assunto, emissor, serial, SHA-256, validade e tax_id).

## Restrições

- não há download do certificado em produção; a operação continua bloqueada;
- nenhuma chamada de rede para SEFAZ acontece neste passo;
- o `FISCAL_SECRET_KEY` continua obrigatório e deve estar fora do Git;
- o status operacional é fail-closed: qualquer falha de validação impede a ativação.
