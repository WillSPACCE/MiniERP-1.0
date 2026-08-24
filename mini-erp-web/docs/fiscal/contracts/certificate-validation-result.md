# Contrato — resultado de validação de certificado

## Estrutura de retorno

```php
[
  'file_name' => 'empresa.pfx',
  'extension' => 'pfx',
  'sha256' => '...',
  'fingerprint_sha256' => '...',
  'subject' => '...',
  'issuer' => '...',
  'serial_number' => '...',
  'tax_id' => '12345678000195',
  'valid_from' => '2026-08-22 17:55:01',
  'valid_until' => '2026-08-24 17:55:01',
  'days_remaining' => 2,
  'status' => 'EXPIRING_SOON',
  'operational' => true,
  'code' => 'CERTIFICATE_EXPIRING_SOON',
  'message' => 'Certificado A1 próximo do vencimento.',
  'diagnostic' => 'Certificado A1 está próximo do vencimento; será aceito apenas como validação offline.'
]
```

## Semântica

- `status`: resultado do ciclo de validade (`VALID`, `EXPIRING_SOON`, `EXPIRED`).
- `operational`: indica se o certificado pode ser ativado no fluxo operacional.
- `code`: código estável para UI, logs e testes automatizados.
- `message`: mensagem curta e segura para o usuário.
- `diagnostic`: detalhamento amigável, sem revelar material sensível do certificado.

## Regras

- código `CERTIFICATE_VALID` exige metadados consistentes e teste criptográfico local aprovado;
- código `CERTIFICATE_EXPIRING_SOON` aceita apenas como validação offline de diagnóstico;
- código `CERTIFICATE_EXPIRED` bloqueia ativação;
- qualquer falha em senha, arquivo, identidade ou X.509 deve resultar em exceção com mensagem segura e não persistir material.
