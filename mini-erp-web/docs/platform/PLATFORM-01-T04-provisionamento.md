# PLATFORM-01-T04 — Provisionamento seguro

## Investigação

O legado deriva normalmente `{MAIN}_tenant_{id}`, aceita nome opcional, usa `CREATE DATABASE IF NOT EXISTS`, grava `db_name` antes de validar e chama `Database::initializeSchema()`. Essa inicialização aplica `database/schema.sql`, `database/seeds.sql`, cria `admin@localhost` e executa ajustes oportunistas. Portanto, o fluxo legado não foi reutilizado.

Os bancos tenant existentes possuem as 12 tabelas descritas em `database/schema.sql`, mas carregam dados demonstrativos oriundos de `seeds.sql` e algumas evoluções oportunistas de runtime. O MAIN contém o registro canônico de tenants e usuários; o banco tenant contém estruturas operacionais do ERP. T05 continuará responsável por template/schema formalmente versionado.

## Fonte estrutural

T04 usava exclusivamente `database/schema.sql`, que contém DDL e nenhum `INSERT`. A partir da T05, essa fonte foi substituída pelo template oficial versionado `database/tenant-template/v1/schema.sql`. `database/seeds.sql`, bancos existentes e dados do MAIN nunca são copiados.

## Fluxo e segurança

1. Revalidar PlatformAdmin.
2. Ler o tenant explícito do MAIN.
3. Exigir `cadastrada`, `blocked = 0` e `db_name` vazio.
4. Derivar `mini_erp_tenant_{tenant_id}` no backend.
5. Rejeitar banco já existente.
6. Atualizar condicionalmente para `provisionando`.
7. Executar `CREATE DATABASE` sem `IF NOT EXISTS`.
8. Aplicar apenas o schema estrutural.
9. Validar a lista exata de tabelas.
10. Atualizar condicionalmente `db_name` e `status = ativa`.

GET nunca provisiona. POST requer CSRF. `db_name` não é aceito por GET/POST. Não existe fallback tenant 1, seleção de banco pela sessão ERP, seed, criação de usuário ou entrada no ERP.

## Falhas, compensação e idempotência

Antes do CREATE, qualquer conflito encerra sem escrita física. Depois do início, falhas deixam o registro como `provisionando` e nunca gravam `db_name` nem `ativa`. Banco parcial não é removido automaticamente e uma nova tentativa é bloqueada pela existência física/status, exigindo diagnóstico e cleanup consciente. Tenant ativo, bloqueado, com `db_name` ou banco derivado existente é rejeitado.

## Limitações

Não há ainda `provisioning_status`, `schema_version`, log persistido de etapa/erro ou retry administrativo. T05 tratará template/versionamento; T06, usuários/admin inicial; T10, entrada no ERP. Por isso T04 cria um ambiente estrutural vazio e validado, sem alegar que usuários ou acesso ERP estejam prontos.
