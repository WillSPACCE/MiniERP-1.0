# Teste manual FISCAL-CERT-01

1. Definir `FISCAL_SECRET_KEY` com valor local de pelo menos 32 caracteres, fora do Git.
2. Acessar `/plataforma/empresa-fiscal-config.php` com PlatformAdmin autenticado.
3. Enviar um arquivo `.pfx` ou `.p12` com senha correta e confirmar que a tela exibe mensagem de sucesso.
4. Verificar que o certificado exibido contém `file_name`, `subject`, `issuer`, `serial_number`, `tax_id`, `valid_from`, `valid_until` e `status`.
5. Tentar enviar uma senha incorreta e confirmar que a UI mostra diagnóstico seguro: `Senha do certificado inválida.`
6. Tentar um PFX corrompido e confirmar que a mensagem mostra `Certificado PFX/P12 inválido ou corrompido.`
7. Tentar um certificado cujo CNPJ/CPF diverge do estabelecimento e confirmar que a mensagem mostra `O certificado não pertence ao estabelecimento fiscal informado.`
8. Confirmar que a senha não aparece em banco, histórico, log ou HTML.
9. Validar o caso de expiração e confirmar que o upload é bloqueado com `CERTIFICATE_EXPIRED`.
10. Confirmar que o histórico do certificado anterior permanece preservado e que o armazenamento fica fora de `public/`.
