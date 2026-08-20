# T3-01-I01 — Diagnóstico do fluxo real de login, empresa, tenant e banco

## Objetivo

Documentar, com evidência no código atual, o fluxo real de:

login → usuário autenticado → empresa/tenant → seleção de empresa → sessão → resolução do tenant → resolução do nome/banco → conexão efetivamente utilizada.

Esta task é de investigação apenas. Não há correção, não há alteração de produção, não há alteração de banco, nem execução de migration/seed.

## Arquivos analisados

- [mini-erp-web/config.php](../config.php)
- [mini-erp-web/app/Database.php](../app/Database.php)
- [mini-erp-web/app/Repository.php](../app/Repository.php)
- [mini-erp-web/public/login.php](../public/login.php)
- [mini-erp-web/public/index.php](../public/index.php)
- [mini-erp-web/docs/f0-t01-estado-atual-mini-erp.md](f0-t01-estado-atual-mini-erp.md)

## Resumo executivo

O fluxo real do sistema mistura três fontes de verdade:

1. o usuário autenticado (`usuarios` e `user_id`);
2. o tenant/empresa selecionado (`tenant_id`, `current_company_id` e slug da URL);
3. o database efetivo da conexão (`Database::$tenantDbName` e `Database::$connection`).

A aplicação não usa um único objeto `TenantContext` nem um contrato único. Em vez disso, a sessão, o `Database` estático e a tabela `tenants` são usados como substitutos de contexto.

A evidência mais forte está em:

- [mini-erp-web/public/index.php](../public/index.php#L1-L60): a sessão é lida cedo e o tenant é resolvido por URL e por `$_SESSION['tenant_id']`.
- [mini-erp-web/public/index.php](../public/index.php#L470-L520): no `case 'login'`, o sistema define `$_SESSION['user_id']`, `$_SESSION['tenant_id']` e eventualmente `$_SESSION['current_company_id']`.
- [mini-erp-web/app/Repository.php](../app/Repository.php#L78-L151): `requireTenantId()` valida `$_SESSION['tenant_id']` e, se necessário, permite fallback administrativo.
- [mini-erp-web/app/Database.php](../app/Database.php#L7-L49): `Database::getConnection()` usa `self::$tenantDbName` para decidir qual banco abrir, e `setTenantDbName()` destrói a conexão estática para forçar recriação.

## Mapa completo do fluxo real

### A. Fluxo de login e autenticação

USUÁRIO -> formulário no [public/login.php](../public/login.php#L13-L74)
→ envia POST para /?page=login
→ [public/index.php](../public/index.php#L450-L520)
→ `findUsuarioByEmail($email)`
→ valida senha com `password_verify()`
→ valida status e `email_verified`
→ escreve `$_SESSION['user_id']`
→ prioriza `user['tenant_id']`
→ fallback para `user['company_id']`
→ fallback final `$_SESSION['tenant_id'] = 1`
→ redireciona para `?page=dashboard`

### B. Fluxo de resolução do tenant a partir da URL

REQUEST -> [public/index.php](../public/index.php#L11-L54)
→ `parse_url($_SERVER['REQUEST_URI'])`
→ `pathSegments[0]`
→ `findTenantBySlug($first)`
→ se encontrado: `$_SESSION['tenant_id'] = $t['id']`
→ `$_SESSION['current_company_id'] = $t['id']`
→ `Database::setTenantDbName($t['db_name'])` se houver `db_name`
→ `currentTenant = $t`

### C. Fluxo de seleção de empresa

ADMIN/USUÁRIO -> escolha de empresa em [public/index.php](../public/index.php#L180-L198)
→ `select_empresa`
→ `$_SESSION['tenant_id'] = $id`
→ `$_SESSION['current_company_id'] = $id`
→ `findTenantById($id)`
→ `Database::setTenantDbName($t['db_name'])` se existir
→ redireciona para `?page=company&id=<id>`

### D. Fluxo de resolução do banco efetivo

[public/index.php](../public/index.php#L11-L23) e [app/Database.php](../app/Database.php#L7-L49)
→ se `$_SESSION['tenant_id']` existe, consulta `tenants` no DB principal em `SELECT db_name FROM tenants WHERE id = :id`
→ `Database::setTenantDbName($db_name)`
→ na próxima chamada de `Database::getConnection()`, o método usa `self::$tenantDbName ?? $config['db']['database']`
→ cria DSN para `mysql:host=...;dbname=<db_to_use>`
→ `new PDO(...)`
→ `initializeSchema()` executa schema/seeds no banco conectado

### E. Fluxo de acesso ao ERP após seleção

[public/index.php](../public/index.php#L525-L548)
→ se usuário autenticado, carrega `$currentUser = $repo->findUsuarioById($_SESSION['user_id'])`
→ toda operação do ERP usa o `$repo` já criado com a conexão atual
→ `Repository` usa `requireTenantId()` em várias operações para impor `$_SESSION['tenant_id']` como scoping
→ o banco efetivo continua sendo o banco em `Database::$connection`, que foi definido globalmente

## Fluxo de login

### Cenário A — usuário comum / empresa

1. O usuário acessa [public/login.php](../public/login.php#L13-L74).
2. O formulário envia para `/?page=login` com `action=login`.
3. Em [public/index.php](../public/index.php#L468-L520), o `case 'login'` executa:
   - lê e-mail e senha;
   - busca usuário com `findUsuarioByEmail($email)`;
   - verifica `password_verify()` ou fallback automático para `admin@localhost`/senha `admin`;
   - rejeita se `status !== 'ativo'`;
   - rejeita se `email_verified = 0` para usuários não admin.
4. Na autenticação bem-sucedida:
   - `$_SESSION['user_id'] = (int)$user['id']`;
   - se `tenant_id` existe, `$_SESSION['tenant_id'] = (int)$user['tenant_id']`;
   - `Database::setTenantDbName($t['db_name'])` para o tenant do usuário;
   - se não houver `tenant_id`, tenta `company_id` e converte por `findCompany()`;
   - se tudo falhar, atribui `$_SESSION['tenant_id'] = 1`.
5. Redireciona para `?page=dashboard`.

### Cenário B — administrador global

A lógica de login considera o padrão principal em [public/index.php](../public/index.php#L468-L520):

- o usuário `admin@localhost` é aceito sem `password_verify()` se a senha for `admin`;
- `requireTenantId()` também usa fallback administrativo em [app/Repository.php](../app/Repository.php#L78-L151): se o usuário autenticado for `admin@localhost` ou `role = admin`, retorna `1`.

Isso significa que a aplicação aceita um admin global com tenant default, mesmo quando o contexto do tenant não estiver consistente.

## Fluxo de seleção de empresa

### Cenário C — seleção pela tela administrativa

Existem dois caminhos principais:

1. URL slug resolve tenant em [public/index.php](../public/index.php#L11-L54):
   - para `/mercado-silva/...`, o site chama `findTenantBySlug('mercado-silva')`;
   - salva `$_SESSION['tenant_id']` e `$_SESSION['current_company_id']`;
   - se `db_name` existir, chama `Database::setTenantDbName($t['db_name'])`.

2. seleção explícita em formulário em [public/index.php](../public/index.php#L180-L198):
   - `case 'select_empresa'` lê `$_POST['company_id']`;
   - grava `$_SESSION['tenant_id'] = $id`;
   - grava `$_SESSION['current_company_id'] = $id`;
   - chama `findTenantById($id)` e aplica `Database::setTenantDbName($t['db_name'])`.

### Efeito prático

A sessão passa a atuar como “empresa/tenant atual”. Porém o valor de `$_SESSION['tenant_id']` e o banco ativo em `Database::$tenantDbName` podem divergir dependendo do último ponto que alterou a sessão e do momento em que a conexão foi construída.

## Fluxo de resolução de tenant

### Origem 1 — sessão

Em [app/Repository.php](../app/Repository.php#L78-L151):

- `requireTenantId()` inicia sessão;
- lê `$_SESSION['tenant_id']`;
- se vazio, lança exceção;
- valida a existência do tenant em `tenants`;
- valida que o `user_id` autenticado pertence ao tenant.

### Origem 2 — URL slug

Em [public/index.php](../public/index.php#L11-L54), `findTenantBySlug($first)` resolve tenant por slug da URL.

### Origem 3 — usuário autenticado

Em [public/index.php](../public/index.php#L482-L505), a autenticação usa `user['tenant_id']` primeiro e `user['company_id']` depois. Se nada existir, cai para `1`.

### Origem 4 — tabela `tenants`

A referência canônica de tenant é a tabela `tenants`, consultada em `findTenantById()`, `findTenantBySlug()` e `findTenantByCnpj()` em [app/Repository.php](../app/Repository.php#L452-L470) e [app/Repository.php](../app/Repository.php#L1291-L1295).

### Precedência real observada

1. `$_SESSION['tenant_id']` quando é lido por `requireTenantId()`; esse valor tem precedência em quase toda a lógica operacional.
2. `user['tenant_id']` no login, quando o usuário entra pela autenticação.
3. `user['company_id']` como compatibilidade.
4. URL slug, quando há roteamento por subdomínio/URL.
5. fallback fixo `1`.

A precedência de fato fica “sessão > usuario > compatibilidade > URL > fallback fixo”, mas o código não normaliza isso em um único contexto.

## Fluxo de resolução do banco

### Origem do nome do banco

O nome do banco real é definido em duas camadas:

- `Database::$tenantDbName` em [app/Database.php](../app/Database.php#L7-L16);
- o valor `db_name` em `tenants` consultado em [public/index.php](../public/index.php#L11-L23) e [public/index.php](../public/index.php#L180-L198).

### Regra de conexão

No `Database::getConnection()` em [app/Database.php](../app/Database.php#L18-L49):

- se `self::$connection` já existe, retorna a conexão atual;
- se `driver === mysql`, usa `self::$tenantDbName ?? $config['db']['database']`;
- cria `CREATE DATABASE IF NOT EXISTS` para esse banco;
- monta DSN `mysql:host=...;dbname=<dbToUse>`;
- instancia `new PDO()`;
- chama `initializeSchema()` imediatamente.

### Estado global e risco

`Database::setTenantDbName()` em [app/Database.php](../app/Database.php#L104-L109) faz:

- `self::$tenantDbName = $dbName;`
- `self::$connection = null;`

Isso torna o banco conectado dependente do último estado global alterado. Em uma aplicação multi-request, esse padrão pode reusar ou trocar contexto sem isolamento por request.

## Tabela de variáveis de contexto encontradas

| Variável | Origem | Escrita em | Lida em | Significado real | Precedência | Fallback | Risco |
|---|---|---|---|---|---|---|---|
| `user_id` | `usuarios.id` após login | [public/index.php](../public/index.php#L482-L520) | [public/index.php](../public/index.php#L525-L548), [app/Repository.php](../app/Repository.php#L78-L151) | usuário autenticado | sessão | nenhum. Só existe após login | se a sessão for inconsistente, validações podem operar com usuário errado |
| `tenant_id` | usuário (`usuarios.tenant_id`), URL slug, sessão, escolha de empresa | [public/index.php](../public/index.php#L15-L27), [public/index.php](../public/index.php#L43-L50), [public/index.php](../public/index.php#L188-L198), [public/index.php](../public/index.php#L482-L505) | [app/Repository.php](../app/Repository.php#L78-L151), [app/Repository.php](../app/Repository.php#L1276-L1288) | tenant selecionado/ativo na sessão | sessão > usuario > compatibilidade > fallback | `1` em login e administrador | troca de tenant sem validação forte |
| `company_id` | campo legado, compatibilidade, `usuarios.company_id` | [public/index.php](../public/index.php#L191-L197), [public/index.php](../public/index.php#L498-L500) | [app/Repository.php](../app/Repository.php#L1276-L1288), [app/Repository.php](../app/Repository.php#L1762-L1815) | empresa antiga / compatibilidade | usa como fallback ao `tenant_id` | usa `tenant_id` primeiro | mistura de modelos |
| `tenant_slug` / slug da URL | `tenants.slug` | `findTenantBySlug()` e no set da sessão | [public/index.php](../public/index.php#L37-L50) | caminho usado para resolver tenant da URL | URL primeiro, se existir slug e não for ignorado | se não encontrar, nada | pequenos slugs podem resolver para tenant errado se colidirem |
| `db_name` de tenant | coluna `tenants.db_name` | [public/index.php](../public/index.php#L15-L23), [public/index.php](../public/index.php#L43-L50), [public/index.php](../public/index.php#L191-L198) | [app/Database.php](../app/Database.php#L18-L49) | banco específico do tenant | valor em `tenants` para o tenant atual | usa `config['db']['database']` | estado global pode reusar banco anterior |
| `$_SESSION['current_company_id']` | compatibilidade | [public/index.php](../public/index.php#L46-L47), [public/index.php](../public/index.php#L191-L197), [public/index.php](../public/index.php#L498-L500) | [app/Repository.php](../app/Repository.php#L1279-L1288), [app/Repository.php](../app/Repository.php#L1762-L1815) | empresa corrente em sessão | tem valor paralelo ao `tenant_id`| se ausente, usa `null` | pode divergir do tenant real |
| `Database::$tenantDbName` | `setTenantDbName()` | [app/Database.php](../app/Database.php#L104-L109) | [app/Database.php](../app/Database.php#L18-L49) | banco ativo global da conexão | último valor definido | usa config principal | risco de reuso entre fluxos |
| `Database::$connection` | `new PDO()` | [app/Database.php](../app/Database.php#L18-L49), [app/Database.php](../app/Database.php#L104-L109) | qualquer `$repo->pdo` construído pelo `Repository` | conexão PDO ativa | primeira conexão criada ou após reset | se nula, cria nova | acoplamento global |

## Precedências e fallbacks encontrados

### Ordem real do login

No código, a precedência do tenant em login é:

1. `user['tenant_id']`;
2. `user['company_id']`;
3. fallback `1`.

Isso está em [public/index.php](../public/index.php#L482-L505).

### Ordem real da sessão

O fluxo de operações operacionais usa:

1. `$_SESSION['tenant_id']` em `requireTenantId()`;
2. `$_SESSION['current_company_id']` em compatibilidade;
3. `Database::$tenantDbName` para escolher conexão;
4. fallback `1` para admin e desenvolvimento.

Isso está em [app/Repository.php](../app/Repository.php#L78-L151) e [app/Database.php](../app/Database.php#L18-L49).

### Fallbacks permissivos

Há vários fallbacks que mascaram inconsistência:

- login: se não houver tenant, usa `1` em [public/index.php](../public/index.php#L503-L505);
- `requireTenantId()`: se o admin estiver autenticado, retorna `1` em [app/Repository.php](../app/Repository.php#L112-L124);
- `findUsuarioByEmail()`: tenta consultar conexão atual e, se não achar, pula para o DB principal em [app/Repository.php](../app/Repository.php#L1349-L1388);
- `findTenantById()`: tenta conexão atual e cai em fallback para DB principal em [app/Repository.php](../app/Repository.php#L452-L470);
- `requireTenantId()` ignora erro de verificação de pertencimento do usuário ao tenant quando a validação falha em DB principal, em [app/Repository.php](../app/Repository.php#L131-L151).

## Diferenças entre tenant autenticado, tenant selecionado e tenant efetivo

### 1) Tenant autenticado

É o `tenant_id` do usuário no registro `usuarios` ou o ID do tenant que ficou em `$_SESSION['tenant_id']` após login.

Evidência: [public/index.php](../public/index.php#L482-L505).

### 2) Tenant selecionado pelo admin

É o ID enviado em `select_empresa`, gravado em `$_SESSION['tenant_id']` e `$_SESSION['current_company_id']`.

Evidência: [public/index.php](../public/index.php#L180-L198).

### 3) Tenant efetivo para banco

É o valor de `Database::$tenantDbName`, normalmente vindo de `tenants.db_name` correspondente ao ID atual em sessão.

Evidência: [public/index.php](../public/index.php#L15-L23) e [app/Database.php](../app/Database.php#L18-L49).

### Conclusão

O sistema permite que o tenant em sessão e o banco efetivo sejam reescritos em momentos diferentes. Não existe uma regra única que garanta congruência entre:

- usuário autenticado;
- tenant da sessão;
- company_id/tenant_id de compatibilidade;
- `tenants.db_name`;
- DB em `Database::$connection`.

## Riscos de isolamento encontrados

1. Usuário do tenant A usando tenant B
   - possível se a sessão for sobrescrita por URL ou seleção; a validação somente verifica `$_SESSION['tenant_id']` e, em alguns pontos, faz fallback para o admin.
   - evidência: [public/index.php](../public/index.php#L15-L27), [public/index.php](../public/index.php#L180-L198), [public/index.php](../public/index.php#L482-L505), [app/Repository.php](../app/Repository.php#L78-L151).

2. Tenant selecionado diferente do tenant autenticado
   - possível porque `select_empresa` escreve `$_SESSION['tenant_id']` independentemente do tenant do usuário autenticado.
   - evidência: [public/index.php](../public/index.php#L188-L198).

3. Sessão apontando para tenant diferente do banco atual
   - possível porque a sessão e a conexão são alteradas em pontos distintos e `Database::$connection` é global.
   - evidência: [public/index.php](../public/index.php#L11-L23), [public/index.php](../public/index.php#L188-L198), [app/Database.php](../app/Database.php#L7-L49).

4. Database reutilizando conexão de tenant anterior
   - possível porque `Database::$connection` é estático e `setTenantDbName()` o zera para recriar na próxima chamada.
   - evidência: [app/Database.php](../app/Database.php#L7-L16), [app/Database.php](../app/Database.php#L104-L109).

5. Fallback mascarando ausência/inconsistência
   - possível porque login e `requireTenantId()` caem em `1` ou permitem admin.
   - evidência: [public/index.php](../public/index.php#L503-L505), [app/Repository.php](../app/Repository.php#L112-L124), [app/Repository.php](../app/Repository.php#L131-L151).

6. Criação de usuário sem associação correta ao tenant
   - possível, especialmente no fluxo administrativo de criação/associação de usuário por empresa.
   - evidência: [public/index.php](../public/index.php#L221-L283) e [app/Repository.php](../app/Repository.php#L1276-L1288).

## Evidências relacionadas ao problema atual de criação de usuário por empresa

O principal ponto de evidência está em [public/index.php](../public/index.php#L221-L283) e em [app/Repository.php](../app/Repository.php#L1276-L1288):

- o fluxo `create_tenant_user` busca `tenant` por ID e depois faz `assignUserToCompany((int)$user['id'], $id)`;
- `assignUserToCompany()` exige `requireTenantId()` para obter o tenant da sessão;
- depois escreve `UPDATE usuarios SET company_id = :cid, tenant_id = :tid WHERE id = :id` usando o tenant atual da sessão;
- porém a sessão pode ter sido temporariamente sobrescrita com `$_SESSION['tenant_id'] = $id` e `$_SESSION['current_company_id'] = $id` no bloco `try/finally` do fluxo; isso revela dependência do estado de sessão para a associação do usuário.

Em outras palavras, a associação do usuário ao tenant/empresa é derivada do estado de sessão em tempo de execução, e não de um contexto imutável. Isso explica por que a criação de usuário por empresa pode parecer funcionar em alguns casos e falhar ou ser atribuída ao tenant errado em outros.

O código também cria um admin por tenant em [app/Repository.php](../app/Repository.php#L1085-L1120), com e-mail normalizado como `admin@{slug}` e `tenant_id = $tenantId`. Isso confirma a intenção de isolar o usuário pelo tenant, mas o fluxo de vínculo ainda depende da sessão atual e do estado global.

## Pontos NÃO CONFIRMADOS

- Não foi possível confirmar, apenas lendo o código, se existe um cenário real de produção em que o usuário do tenant A consegue acessar o banco do tenant B sem que a sessão seja manualmente sobrescrita.
- Não foi possível confirmar se o `db_name` de um tenant sempre corresponde ao banco de dados real que o sistema usa em produção, porque o código depende do dado em `tenants` e do estado global estático.
- Não foi possível confirmar automaticamente se a criação de usuário por empresa falha sempre, pois isso exigiria execução do fluxo em ambiente funcional e inspeção do banco real.
- Não foi possível confirmar a presença de um cenário de concorrência multi-request no PHP atual, porque a aplicação opera com sessão e `static` no processo atual, sem isolamento por request em camada explícita.

## Arquivos criados

- [mini-erp-web/docs/t3-01-i01-fluxo-real-login-empresa-tenant.md](t3-01-i01-fluxo-real-login-empresa-tenant.md)

## Arquivos modificados

- Nenhum arquivo de produção foi alterado.
- Nenhum arquivo em [mini-erp-web/public](../public), [mini-erp-web/app](../app), [mini-erp-web/config.php](../config.php), [mini-erp-web/database](../database) foi modificado.

## Validações realizadas

- leitura direta dos arquivos relevantes;
- rastreio de `$_SESSION`, `tenant_id`, `company_id`, `db_name` e `Database::$tenantDbName`;
- confirmação das chamadas de login, URL slug, seleção de empresa e autenticação do usuário;
- conferência dos fallbacks e do uso do banco em `Database::getConnection()`;
- comparação com a documentação baseline existente em [mini-erp-web/docs/f0-t01-estado-atual-mini-erp.md](f0-t01-estado-atual-mini-erp.md).

## Confirmação final

- Nenhum código de produção foi alterado.
- Nenhum banco foi alterado.
- Nenhuma migration foi executada.
- Nenhum seed foi executado.
- Nenhuma correção foi aplicada.
- Esta task ficou restrita à investigação e documentação do fluxo real.
- O diagnóstico acima é a evidência registrada para a task T3-01-I01 e a task T3-01-I02 não foi executada.
