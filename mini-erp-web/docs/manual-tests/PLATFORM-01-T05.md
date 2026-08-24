# Teste manual — PLATFORM-01-T05

## Painel e tenant 14

1. Abra o CMD.
2. Execute `cd /d C:\xampp\htdocs\MiniRP\mini-erp-web`.
3. Execute `start-platform-server.bat <ID_REAL_AUTORIZADO>`.
4. Abra `http://localhost:8000/plataforma/`.
5. Abra Detalhes do tenant 14.
6. Confirme: ambiente disponível, banco `mini_erp_tenant_14` e versão do schema “Não identificada”.
7. Confirme que a empresa continua ativa e que Usuários/Acessar ERP continuam indisponíveis.

No phpMyAdmin, confirme que `mini_erp_tenant_14` continua existindo, suas 12 tabelas permanecem presentes e os registros operacionais continuam com as mesmas contagens. A T05 não recria, limpa ou reaplica schema nesse banco.

## Template oficial

1. Abra `database/tenant-template/v1/schema.sql`.
2. Confirme as nove tabelas operacionais.
3. Pesquise `INSERT`, `admin@localhost`, clientes/produtos demonstrativos e credenciais: nenhum deve existir.
4. Confirme que `tenants`, `usuarios` e `password_resets` não são criadas pelo template v1.

## Testes PHP seguros

No CMD, execute:

```bat
C:\xampp\php\php.exe tests\TenantSchemaTemplateTest.php
C:\xampp\php\php.exe tests\ProvisionPlatformTenantServiceTest.php
C:\xampp\php\php.exe tests\PlatformProvisioningEntrypointTest.php
```

Resultado esperado: todos terminam com `OK`. Não execute `TenantConnectionResolverTest.php`, `TenantContextIntegrationTest.php`, `TenantIsolationTest.php` nem qualquer teste/script de provisionamento real.

## MIGRATION — NÃO EXECUTAR SEM CONFIRMAÇÃO

Arquivo: `migrations/20260820_add_tenant_schema_version.sql`.

Após backup e aprovação administrativa, o comando previsto é:

```bat
C:\xampp\mysql\bin\mysql.exe -u root mini_erp < migrations\20260820_add_tenant_schema_version.sql
```

Efeito: adiciona a coluna anulável `schema_version`. Não classifica tenants existentes. Antes da execução, novos provisionamentos T05 ficam bloqueados de forma segura.

## Falhas

É falha se tenant 14 aparecer como v1 sem reconciliação, se algum dado desaparecer, se o template contiver INSERT/seeds, se versão vier da URL ou se um provisionamento começar sem a coluna no MAIN.

Data:

Testado por:

Ambiente:

Resultado:

Problemas encontrados:
