# Manual test — FISCAL-CERT-02

## Objetivo

Verificar que o certificado A1 funciona corretamente em runtime real, permaneceu persistente entre processos e não vazou segredos.

## Pré-condições

- XAMPP com MariaDB ativo;
- PHP CLI funcional;
- storage local disponível fora de `public/`;
- `FISCAL_SECRET_KEY` ausente ou configurada no ambiente.

## Passos

1. abrir a tela de empresa e acessar o painel de certificado;
2. instalar um PFX/P12 de teste com senha válida;
3. verificar que o arquivo é gravado em `storage/fiscal/certificates` e não em `public/`;
4. confirmar que o banco guarda somente metadados e referências;
5. testar a leitura do arquivo no mesmo processo;
6. reiniciar um processo PHP independente;
7. usar o provedor operacional para ler o certificado ativo;
8. validar a assinatura TEST_ONLY e a identidade;
9. desativar o certificado e confirmar que o histórico preserva o anterior.

## Resultado esperado

- `FISCAL_SECRET_KEY_PERSISTENCE=PASS`
- `CERTIFICATE_CROSS_PROCESS_READ=PASS`
- sem vazamento de senha, chave ou conteúdo do PKCS#12;
- sem comunicação com a SEFAZ.
