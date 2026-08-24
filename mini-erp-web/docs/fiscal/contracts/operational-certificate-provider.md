# OperationalCertificateProvider

## Contrato

O provedor operacional recebe apenas um escopo de tenant e estabelecimento e resolve os artefatos necessários sem expor implementação interna de storage ou senha.

## Entrada

- `tenant_id`
- `establishment_id`

## Saída

- `file_name`
- `extension`
- `sha256`
- `fingerprint_sha256`
- `subject`
- `issuer`
- `serial_number`
- `tax_id`
- `valid_from`
- `valid_until`
- `status`
- `operational`
- `code`
- `message`
- `diagnostic`

## Regras

- resolve o certificado ativo e o storage reference;
- lê o arquivo do storage privado;
- monta a chave de descriptografia usando a chave mestre persistente;
- recupera a senha protegida;
- abre o `PKCS#12` no runtime;
- valida a assinatura local TEST_ONLY;
- não devolve senha, chave privada ou conteúdo bruto do arquivo.

## Uso

O pipeline XML e o rastreio fiscal consumem essa interface sem conhecer o caminho físico do arquivo nem a senha do certificado.
