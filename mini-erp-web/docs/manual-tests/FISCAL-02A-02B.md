# Teste manual FISCAL-02A/02B

1. Definir `FISCAL_SECRET_KEY` com segredo local de pelo menos 32 caracteres, fora do Git.
2. Autenticar como PlatformAdmin e abrir `/plataforma/empresa-fiscal-config.php?id={tenant}`.
3. Usar apenas PFX/P12 TEST_ONLY cujo CPF/CNPJ corresponda ao estabelecimento.
4. Confirmar metadados e executar “Testar certificado offline”.
5. Cadastrar séries 55 e 65 em ambiente 2; confirmar que ambiente 1 está bloqueado.
6. Confirmar que certificado e segredo estão em `storage/fiscal/`, nunca em `public/`, e que a senha não aparece no banco.
7. Não executar download, SEFAZ ou produção.
