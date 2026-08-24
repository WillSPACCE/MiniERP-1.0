# Teste manual FISCAL-06B-A

1. Executar `C:\xampp\php\php.exe scripts/check_fiscal_environment.php`.
2. Confirmar PHP 8.2.12, INI do XAMPP, todas as extensões e `local_toolchain_ok=true`.
3. Executar Composer pelo PHAR oficial: `validate --strict`, `check-platform-reqs` e `audit`.
4. Executar `C:\xampp\php\php.exe tests/FiscalToolchainTest.php`.
5. Confirmar NFePHP v5.2.8, RTC/IBS/CBS/IS/cClassTrib e CNPJ alfanumérico.
6. Confirmar que os schemas instalados param em `PL_010_V1.30` e que a decisão continua `BLOCKED` até reconciliar os pacotes oficiais vigentes.
7. Confirmar ausência de XML, certificado, chamada SEFAZ, alteração de banco e alteração das prévias.
