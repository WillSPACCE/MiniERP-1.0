# PLATFORM-02A1 — reconciliação dos tenants históricos

Evidência decisiva encontrada no Git, commit `34654d6`: `scripts/create_test_tenants.php` criou três empresas sequenciais, bancos `_tenant_1..3`, CNPJs `10000000000001..3` e administradores `admin1..3@localhost`. Como Default Tenant já era ID 1, os registros receberam IDs 2, 3 e 4. Assim, 2→banco1, 3→banco2 e 4→banco3 são associações intencionais do script, não offset acidental a corrigir.

- Tenant 1: Default Tenant, usuário `admin@localhost`, TEST_CONFIRMED. Compartilha a fixture banco1 com tenant 2; não separar nesta task.
- Tenant 2: Empresa Teste 1, TEST_CONFIRMED, banco1 contém Eletrônicos/gerente1/cliente1 conforme `seed_tenants_demo.php`.
- Tenant 3: Empresa Teste 2, TEST_CONFIRMED, banco2 contém Supermercado/gerente2/cliente2.
- Tenant 4: Empresa Teste 3, TEST_CONFIRMED, banco3 contém Escritório/gerente3/cliente3.
- Tenant 5: INFOCASE, usuários MAIN `admin@infocase` e `willyan@infocase.com`; banco5 tem dados próprios fora das fixtures sequenciais. USER_PRESERVE_CONFIRMED.

Nenhum banco/registro 1–5 foi alterado. Plano: preservar/migrar tenant 5 após backup; manter 1–4 como fixtures legadas até task explícita de limpeza, sem separar ou renomear. Todas as quatro bases antigas carecem das 15 tabelas fiscais e divergem em colunas/índices legados; extras `tenants`, `usuarios`, `password_resets` são legado útil, não erro automático.
