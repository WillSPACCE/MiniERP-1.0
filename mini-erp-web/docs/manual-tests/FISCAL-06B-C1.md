# Teste manual FISCAL-06B-C1

Pré-requisitos: PHP 8.2 do XAMPP, extensões DOM/OpenSSL, dependências do `composer.lock` e schemas oficiais já presentes localmente.

1. Em `mini-erp-web`, executar `C:\xampp\php\php.exe tests\FiscalXmlBuilderTest.php`.
2. Confirmar `FiscalXmlBuilder OK`.
3. Confirmar que não surgiram PFX/P12, chave privada ou XML em `public/`, `storage/` ou no Git.
4. Confirmar que nenhuma variável de ambiente/senha foi impressa.
5. Executar os testes locais listados no relatório da task; não habilitar testes MariaDB.

O teste gera e descarta credenciais em memória, comprova 55/65, XSD offline, XMLDSig, CNPJ alfanumérico, grupos clássicos, IPI, RTC/IS e adulteração. Não iniciar servidor, banco ou chamada SEFAZ.
