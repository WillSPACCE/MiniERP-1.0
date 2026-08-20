# F0-T01 — Inventário do estado atual e hotspots de tenant/database

## Objetivo

Registrar o baseline real do MiniERP antes de qualquer refatoração arquitetural. Esta documentação descreve o comportamento observado no código atual, sem alterar produção, sem mover arquivos e sem aplicar migrações.

## Alcance da análise

A análise foi feita sobre os artefatos abaixo, sem alterar produção nem executar a aplicação em modo de escrita.

### Arquivos analisados

- [mini-erp-web/config.php](../config.php)
- [mini-erp-web/app/Database.php](../app/Database.php)
- [mini-erp-web/app/Repository.php](../app/Repository.php)
- [mini-erp-web/public/index.php](../public/index.php)
- [mini-erp-web/public/login.php](../public/login.php)
- [mini-erp-web/database/schema.sql](../database/schema.sql)
- [mini-erp-web/database/seeds.sql](../database/seeds.sql)
- [mini-erp-web/migrations/README_MIGRATION_TENANT.md](../migrations/README_MIGRATION_TENANT.md)
- [mini-erp-web/migrations/20260814_add_tenant_id.sql](../migrations/20260814_add_tenant_id.sql)
- [mini-erp-web/scripts/add_tenant_columns.php](../scripts/add_tenant_columns.php)
- [mini-erp-web/scripts/create_default_tenant.php](../scripts/create_default_tenant.php)
- [mini-erp-web/scripts/check_tenants.php](../scripts/check_tenants.php)
- [mini-erp-web/scripts/provision_tenants.php](../scripts/provision_tenants.php)

## Resumo executivo

O código atual ainda está fortemente ancorado em uma arquitetura monolítica procedural/OO simples. A criação de conexão com PDO, o tenant atual, a sessão HTTP e a lógica de negócio convivem em um mesmo fluxo principal, especialmente em [mini-erp-web/public/index.php](../public/index.php) e [mini-erp-web/app/Repository.php](../app/Repository.php).

Os pontos mais críticos observados são:

- uso direto de `$_SESSION` para tenant e autenticação;
- `Database::getConnection()` como ponto central de criação/reuso de conexão, mas com estado global mutável (`$tenantDbName`);
- `Repository` validando tenant e fazendo consultas de autenticação/empresa sem isolamento explícito em referência a `TenantContext`;
- bootstrap e schema executados em `Database::getConnection()` e `initializeSchema()`;
- vários `new PDO(...)` em código legado em `Repository` e `Database` para conexões de admin/tenant;
- mistura entre persistência, regra de negócio e validação de sessão em um único repositório.

## Onde as conexões PDO são criadas

### 1) [mini-erp-web/app/Database.php](../app/Database.php)

Este arquivo é o principal ponto de criação de PDO em código atual.

- `Database::getConnection()` cria a conexão inicial:
  - se driver for MySQL, monta o DSN com host, porta e database;
  - se for SQLite, cria o arquivo e abre `sqlite:<path>`.
- `Database::ensureDatabaseExists()` cria o banco principal com `CREATE DATABASE IF NOT EXISTS` usando `new PDO($serverDsn, ...)`.
- `Database::ensureDatabaseExistsByName()` faz o mesmo para um database específico do tenant.
- O processo de bootstrap também chama `initializeSchema()` dentro de `getConnection()`, o que significa que criação de schema e seeds acontece no momento de abertura da conexão.

### 2) [mini-erp-web/app/Repository.php](../app/Repository.php)

Há diversas conexões manuais criadas diretamente com `new PDO(...)`, fora do `Database::getConnection()` central:

- `setCompanyBlocked()` cria `$pdoMain` com DSN do banco principal para atualizar `tenants` e `usuarios`.
- `requireTenantId()` cria `$pdoMain` para validação de tenant e usuário em banco principal.
- `requireTenantId()` também faz uma segunda validação em `usuarios` consultando `tenant_id`.
- `hasColumn()` usa `$this->pdo`, mas a estrutura ainda é global e dependente do estado do Database.

### 3) [mini-erp-web/public/index.php](../public/index.php)

A criação de conexão direta também aparece em rotas de fluxo de app:

- ao iniciar, se houver `$_SESSION['tenant_id']`, o arquivo tenta conectar ao banco principal para consultar `SELECT db_name FROM tenants WHERE id = :id`;
- em vários pontos, a lógica usa `new PDO(...)` para atualizar ou provisionar dados de tenant;
- `saveCompany()`, `assignUserToCompany()`, `createOrUpdateAdmin()`, e outros fluxos delegam para `$repo` e não criam PDO diretamente no front-end, mas a lógica de tenant e sessão depende de `Repository` e do estado global do Database.

### Observação real

A regra de “um único caminho para obter PDO” não está completamente consistente com o estado atual: a classe Database atua como centro, mas Repository e index.php criam conexões extras diretamente com `new PDO(...)` em vários pontos.

## Onde `Database.php` cria, reutiliza ou troca conexões

### `Database::getConnection()`

- Usa uma propriedade estática: `private static ?PDO $connection = null;`
- Se a conexão já existe, a retorna imediatamente.
- Caso contrário, lê `config.php`, monta DSN e cria PDO com `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` e `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`.
- Depois cria schema em `initializeSchema()`.

### `Database::setTenantDbName()`

- Define `self::$tenantDbName = $dbName;`
- Reseta a conexão estática `self::$connection = null;`
- A próxima chamada a `Database::getConnection()` cria uma nova conexão com o banco do tenant.

### Efeito real sobre o sistema

Esse mecanismo cria um “estado global de conexão” por tenant em memória. Em conjunto com `Repository`, isso permite mudar a conexão de banco em tempo de execução, mas sem um contexto de request/tenant explícito; depende do estado mutable do objeto estático.

## Onde `Repository.php` acessa o banco

O arquivo [mini-erp-web/app/Repository.php](../app/Repository.php) é o principal ponto de acesso ao banco do legado. O construtor faz:

- `$this->pdo = Database::getConnection();`
- chama `ensureClienteColumns()`, `ensureFornecedorColumns()`, `ensureUsuarioColumns()`, `ensureTenantsColumns()`

Além disso, o repositório contém lógica de consulta e persistência em vários métodos, notadamente:

- `setCompanyBlocked()`
- `requireTenantId()`
- `ensureUsuarioColumns()`
- `ensureDefaultAdmin()`
- `ensureClienteColumns()`
- `ensureFornecedorColumns()`
- `ensureTenantsColumns()`
- chamadas como `saveCliente()`, `saveProduto()`, `saveUsuario()`, `saveCompany()`, `createSale()`, `findTenantBySlug()`, `findUsuarioByEmail()`, `listClientes()`, etc.

### Observação real

Esse arquivo mistura:

- validação de tenant;
- validação de sessão (`$_SESSION`);
- conexão com banco;
- regras de negócio simples;
- ajuste do schema do banco;
- manipulação de dados por tabela.

Essa é uma área crítica de hotspot para a próxima arquitetura, porque o futuro Controller → Service → Repository exige separar isso de forma progressiva.

## Todos os pontos que usam `tenant_id`

### A) Definição de tabela e migração

No schema atual, `tenant_id` não aparece em `usuarios` e `produtos` nas tabelas principais de [mini-erp-web/database/schema.sql](../database/schema.sql). Porém o projeto contém migração explícita em [mini-erp-web/migrations/20260814_add_tenant_id.sql](../migrations/20260814_add_tenant_id.sql):

- `ALTER TABLE produtos ADD COLUMN IF NOT EXISTS tenant_id INT NULL;`
- `ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS tenant_id INT NULL;`
- índices `ix_produtos_tenant_id` e `ix_usuarios_tenant_id`

Também há script de compatibilidade em [mini-erp-web/scripts/add_tenant_columns.php](../scripts/add_tenant_columns.php), que adiciona `tenant_id` e popula registros existentes com `1`.

### B) Validação e uso em runtime

Em [mini-erp-web/app/Repository.php](../app/Repository.php):

- `requireTenantId()` lê `$_SESSION['tenant_id']`;
- valida se o tenant existe na tabela `tenants`;
- valida se o usuário atual pertence ao tenant via `SELECT id FROM usuarios WHERE id = :id AND tenant_id = :tid`;
- se não existir a coluna, ele tenta compatibilidade alternativa e fallback administrativo.

### C) Sessão e contexto de aplicação

Em [mini-erp-web/public/index.php](../public/index.php):

- `$_SESSION['tenant_id']` é setado quando um slug de URL resolve para um tenant;
- `$_SESSION['current_company_id']` também é usado como compatibilidade;
- em vários pontos o arquivo compara ou reatribui a sessão para a empresa selecionada.

### D) Associação de usuário a tenant

No código ainda há uso de `company_id` e `tenant_id` misturados:

- em `database/schema.sql`, `usuarios` e `produtos` usam `company_id` no schema principal;
- em runtime, a lógica usa `$_SESSION['tenant_id']` e também `company_id` como compatibilidade;
- em `Repository::assignUserToCompany()`, `setCompanyBlocked()`, e `createTenantAdmin()`, o sistema alterna entre os nomes de campos sem consistência total.

### Observação real

O estado atual não é um modelo completamente uniforme de multi-tenant. Há uma transição em andamento entre `company_id` e `tenant_id`. A presença de compatibilidade e fallback mostra que o sistema foi evoluindo com overlays de estado e não com um único contract formal.

## Todos os pontos que acessam `$_SESSION` relacionados a tenant/autenticação

### Em [mini-erp-web/public/index.php](../public/index.php)

A sessão é usada em vários trechos:

- início do arquivo: `if (session_status() === PHP_SESSION_NONE) session_start();`
- leitura de `$_SESSION['tenant_id']` para determinar db do tenant;
- escrita de `$_SESSION['tenant_id']` ao resolver slug do URL;
- escrita de `$_SESSION['current_company_id']` como compatibilidade;
- leitura de `$_SESSION['user_id']` no login e em vários fluxos;
- no `case 'login'`, o código define `$_SESSION['user_id']`, `$_SESSION['tenant_id']` e `$_SESSION['current_company_id']`;
- no `case 'logout'`, `session_unset(); session_destroy();`.

### Em [mini-erp-web/public/login.php](../public/login.php)

- `session_start()` quando necessário;
- leitura de `$_SESSION['tenant_id']` para mostrar a empresa logada;
- o login é um form de POST para `/?page=login` com action `login`.

### Em [mini-erp-web/app/Repository.php](../app/Repository.php)

- `requireTenantId()` lê `$_SESSION['tenant_id']`;
- verifica `$_SESSION['user_id']` para validar se o usuário pertence ao tenant;
- usa sessão como base da decisão de tenant; a validação de acesso depende do estado da sessão e não de um contexto explícito.

### Observação real

A sessão é um “estado parcialmente compartilhado” entre autenticação e tenant. Isso é um grande hotspot para a próxima fase de `TenantContext`, porque o código usa a sessão como fonte de verdade sem encapsulamento.

## Como o login cria e mantém a sessão

### Fluxo atual observado

1. O usuário acessa [mini-erp-web/public/login.php](../public/login.php), que renderiza o formulário de login.
2. O form envia POST para `/?page=login` com `action=login`.
3. Em [mini-erp-web/public/index.php](../public/index.php), no bloco `switch ($action)`, o case `login` executa o fluxo:
   - pega email e senha;
   - consulta `$repo->findUsuarioByEmail($email)`;
   - valida `password_verify($senha, $user['senha'])` ou fallback para `admin@localhost`/senha `admin`;
   - verifica se o status do usuário é `ativo`;
   - verifica `email_verified` para usuários que não são admin;
   - se tudo ok, define:
     - `$_SESSION['user_id'] = (int)$user['id']`
     - `$_SESSION['tenant_id'] = (int)$user['tenant_id']` se existir
     - `$_SESSION['current_company_id'] = (int)$user['company_id']` se existir
   - se não houver tenant em usuário, tenta usar `company_id` como fallback
   - se ainda assim não houver tenant, atribui `1` como fallback manual e temporário.
4. Depois o código redireciona para `?page=dashboard`.

### Manutenção de sessão

- A sessão é iniciada no início de [mini-erp-web/public/index.php](../public/index.php) e [mini-erp-web/public/login.php](../public/login.php).
- O logout faz `session_unset()` e `session_destroy()`.
- Não há `session_regenerate_id()` real no fluxo atual documentado.
- Não há uso explícito de `HttpOnly`, `Secure` e `SameSite` em código PHP observável no fluxo principal.

### Observação real

O login atual funciona com uma sessão compartilhada e mutável. A autenticação e a seleção de tenant são acopladas, e isso aumenta risco de inconsistência se o usuário estiver em mais de um tenant ou se o tenant da sessão divergir do tenant do usuário.

## Como o tenant é identificado e validado

### Identificação

O tenant pode ser identificado em três fluxos distintos:

1. via URL slug em [mini-erp-web/public/index.php](../public/index.php)
   - exemplo: `/mercado-silva/login` pode levar para resolver `tenant slug = mercado-silva`;
   - `findTenantBySlug($first)` é chamado;
   - se encontrar, grava `$_SESSION['tenant_id']` e `$_SESSION['current_company_id']`.

2. via sessão (`$_SESSION['tenant_id']`)
   - em várias partes do código, o `tenant_id` da sessão é o valor central usado para operação.

3. via campo `company_id` ou `tenant_id` em usuário
   - ao efetuar login, o sistema tenta ler `user['tenant_id']`; se não existir, usa `user['company_id']`.

### Validação

A validação ocorre em [mini-erp-web/app/Repository.php](../app/Repository.php):

- `requireTenantId()` verifica a existência do tenant na tabela `tenants`;
- depois verifica se o usuário do `$_SESSION['user_id']` pertence ao tenant consultando `usuarios` e `tenant_id`;
- se a coluna não existir, tenta compatibilidade e fallback para `admin@localhost`/role admin.

### Observação real

A validação de tenant é muito dependente do estado de sessão e do banco atual; ela tenta “consertar” cenários de compatibilidade e legado em vez de ter uma regra única e formalizada.

## Onde existe risco de acesso entre tenants

Os riscos observados são os seguintes:

### 1) Uso de sessão como fonte única sem contexto formal

`$_SESSION['tenant_id']` é lido e escrito em vários lugares. Se a sessão for inconsistenta ou alterada em um fluxo incompleto, o sistema pode operar com tenant errado sem verificação rígida em todas as rotas.

### 2) Estado global de banco por tenant

`Database::setTenantDbName($dbName)` altera um atributo estático global. Isso pode fazer com que a próxima conexão use um banco do tenant errado se o estado global for reutilizado indevidamente em outra requisição ou fluxo.

### 3) Fallbacks permissivos

Em `requireTenantId()` há fallback para `admin@localhost` e `role = admin`, além de compatibilidade com `company_id`. Isso reduz a rigidez da regra e pode permitir acesso em cenários de desenvolvimento ou recuperação, mas também abre brecha para comportamento misturado sem um contrato formal.

### 4) `tenant_id` e `company_id` coexistindo

O schema principal usa `company_id`, mas o código e as migrações estão migrando para `tenant_id`. Isso significa que as validações e os filtros podem depender de um campo diferente dependendo do ponto de execução.

### 5) Conexões extras criadas manualmente

O uso direto de `new PDO(...)` em [mini-erp-web/app/Repository.php](../app/Repository.php) e em alguns pontos do bootstrap cria múltiplas vias para acessar o mesmo estado. Isso aumenta a chance de queries rodarem no banco errado ou com tenant errado.

### 6) Inicialização de schema no bootstrap

Como `Database::initializeSchema()` é disparado em `getConnection()`, o banco pode realizar alterações estruturais e dados iniciais quando a aplicação abre conexão. Isso é um risco de contaminação de ambiente e de mistura de dados quando a lógica depende de startup automático.

## Onde `initializeSchema()`, `schema.sql` ou seeds são executados

### `initializeSchema()`

Em [mini-erp-web/app/Database.php](../app/Database.php), `initializeSchema()`:

- lê [mini-erp-web/database/schema.sql](../database/schema.sql)
- `splitSqlStatements()` divide SQL em instruções
- executa cada statement com `self::$connection->exec($statement)`
- depois lê [mini-erp-web/database/seeds.sql](../database/seeds.sql) e executa também;
- cria um usuário administrador padrão se `usuarios` estiver vazio;
- tenta aplicar migrações legadas como `ALTER TABLE produtos ADD COLUMN company_id` e `ALTER TABLE usuarios ADD COLUMN company_id`;
- tenta migrar dados de `data/empresas.json` e de `companies` para a tabela `tenants`.

### Quando isso acontece

- em `Database::getConnection()`;
- quando a conexão ainda não existe;
- nas chamadas de `Database::setTenantDbName()` após reset de conexão;
- uma vez por aplicação, por reuso do objeto estático.

### Observação real

O bootstrap de aplicação está fazendo operações de schema e seeds em runtime, e isso contrasta com a regra do plano técnico de separar bootstrap e runner de migração. Isso é um hotspot crucial para as próximas fases.

## Quais arquivos e rotas alteram dados

### Fluxo principal em [mini-erp-web/public/index.php](../public/index.php)

O principal roteador de ação realiza gravações em banco por meio de `$repo` nos casos:

- `save_cliente`
- `delete_cliente`
- `save_produto`
- `delete_produto`
- `save_venda`
- `save_empresa`
- `assign_user_company`
- `select_empresa`
- `create_tenant_user`
- `create_tenant_admin`
- `delete_empresa`
- `toggle_block_empresa`
- `save_product_taxes`
- `save_cfop`
- `delete_cfop`
- `save_fornecedor`
- `delete_fornecedor`
- `save_motorista`
- `delete_motorista`
- `save_transportadora`
- `delete_transportadora`
- `save_usuario`
- `delete_usuario`
- `approve_usuario`
- `login`
- `logout`

### Observação real

Muitas destas ações fazem escrita, validação e tenant-selection em um mesmo ponto de entrada. Isso aumenta a mistura de responsabilidades e dificulta separação de regras fiscais, autenticação e camada de persistência.

## Quais pontos misturam regra de negócio com persistência

### [mini-erp-web/app/Repository.php](../app/Repository.php)

Este arquivo mistura claramente:

- seleção de tenant e validação de acesso;
- criação de conexão;
- manipulação de estrutura SQL (`ALTER TABLE`);
- migração de dados;
- autenticação de usuário (`ensureDefaultAdmin()`);
- consultas/relatórios (`getDashboardData()` entre outras);
- regras de domínio e atualizações de status.

### [mini-erp-web/public/index.php](../public/index.php)

Além de controlar a UI e POST actions, o arquivo também:

- aplica preenchimento automático via BrasilAPI;
- resolve tenant por slug da URL;
- decide se a sessão é válida para fazer login;
- aplica regras de status, e-mail verificado e sessão;
- orquestra a criação de usuários e empresa em um único ponto de entrada.

### Observação real

A mistura de negócio e persistência é um forte hotspot para a arquitetura alvo, especialmente nas próximas fases de anti-corruption layer e services wrappers.

## Hotspots identificados para as próximas fases

### Hotspot 1 — Tenant e sessão
- [mini-erp-web/public/index.php](../public/index.php)
- [mini-erp-web/public/login.php](../public/login.php)
- [mini-erp-web/app/Repository.php](../app/Repository.php)

### Hotspot 2 — Database e conexão
- [mini-erp-web/app/Database.php](../app/Database.php)
- [mini-erp-web/config.php](../config.php)

### Hotspot 3 — Bootstrap e schema
- [mini-erp-web/app/Database.php](../app/Database.php)
- [mini-erp-web/database/schema.sql](../database/schema.sql)
- [mini-erp-web/database/seeds.sql](../database/seeds.sql)

### Hotspot 4 — Multi-tenant e validação
- [mini-erp-web/app/Repository.php](../app/Repository.php)
- [mini-erp-web/scripts/check_tenants.php](../scripts/check_tenants.php)
- [mini-erp-web/scripts/provision_tenants.php](../scripts/provision_tenants.php)

### Hotspot 5 — Regras de negócio + persistência
- [mini-erp-web/public/index.php](../public/index.php)
- [mini-erp-web/app/Repository.php](../app/Repository.php)

### Hotspot 6 — Migração e compatibilidade
- [mini-erp-web/migrations](../migrations)
- [mini-erp-web/scripts](../scripts)

## Arquivos que provavelmente serão afetados nas próximas tasks

- [mini-erp-web/app/Database.php](../app/Database.php)
- [mini-erp-web/app/Repository.php](../app/Repository.php)
- [mini-erp-web/public/index.php](../public/index.php)
- [mini-erp-web/public/login.php](../public/login.php)
- [mini-erp-web/config.php](../config.php)
- [mini-erp-web/database/schema.sql](../database/schema.sql)
- [mini-erp-web/database/seeds.sql](../database/seeds.sql)
- [mini-erp-web/migrations](../migrations)
- [mini-erp-web/scripts/add_tenant_columns.php](../scripts/add_tenant_columns.php)
- [mini-erp-web/scripts/check_tenants.php](../scripts/check_tenants.php)
- [mini-erp-web/scripts/provision_tenants.php](../scripts/provision_tenants.php)

## Arquivos alterados/criados

### Arquivos criados (documentação)

- [mini-erp-web/docs/f0-t01-estado-atual-mini-erp.md](f0-t01-estado-atual-mini-erp.md)

### Código de produção alterado

Nenhum. Esta task foi restrita à análise e documentação do baseline atual.

## Confirmação final

- nenhum arquivo de produção foi alterado;
- nenhum código foi movido;
- nenhuma migration foi executada;
- nenhum banco foi alterado;
- nenhuma nova camada de TenantContext, Service ou Repository foi criada;
- o documento acima registra o comportamento real observado como baseline para as próximas tasks.

## Estado do baseline documentado

Este documento funcione como baseline objetivo para F0-T02, F0-T03, F0-T04 e demais fases posteriores. As próximas tasks devem consumir este registro real, preservando compatibilidade e evitando alterações de comportamento sem antes capturar regressões por testes.
