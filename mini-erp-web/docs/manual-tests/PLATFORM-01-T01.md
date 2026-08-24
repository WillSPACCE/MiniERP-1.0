# PLATFORM-01-T01 — Teste Manual

## Pré-requisitos

- XAMPP/MariaDB ativo: necessário para validar credenciais e listar empresas no banco principal.
- PHP em `C:\xampp\php\php.exe`.
- Projeto em `C:\xampp\htdocs\MiniRP\mini-erp-web`.
- Navegador com cookies habilitados.
- Uma identidade ativa já existente em `mini_erp.usuarios`, com senha persistida por hash e conhecida pelo testador.
- Temporariamente, um registro ativo `admin@localhost` pode usar a senha padrão `admin` sem allowlist. Outras identidades exigem ID em `PLATFORM_ADMIN_USER_IDS`.

Nenhuma identidade ou senha foi criada nesta task. `admin@localhost` é uma exceção transitória explicitamente autorizada; `role=admin` e tenant 1 continuam sem conceder acesso automaticamente.

## Como iniciar

Abra o Prompt de Comando e execute, substituindo apenas pelo ID real autorizado:

```bat
cd /d C:\xampp\htdocs\MiniRP\mini-erp-web
start-platform-server.bat
```

Sem argumento, o iniciador usa a compatibilidade temporária de `admin@localhost`. Com um ID como argumento, configura a allowlist para o processo. Em ambos os casos, executa:

```bat
C:\xampp\php\php.exe -S 0.0.0.0:8000 -t C:\xampp\htdocs\MiniRP\mini-erp-web\public
```

## URL exata do painel

Login: `http://localhost:8000/plataforma/login.php`

Dashboard: `http://localhost:8000/plataforma/`

`http://localhost:8000/platform.php` permanece somente como redirecionamento de compatibilidade.

## Teste sem login

1. Abra uma janela anônima.
2. Acesse o dashboard.

Resultado esperado: redirecionamento para o login próprio, nenhuma empresa exibida e nenhum banco tenant aberto.

## Teste de login

A identidade precisa estar ativa no banco principal. Temporariamente, `admin@localhost` pode usar a senha padrão sem allowlist. Para outras identidades, é necessário possuir hash de senha conhecido e ID na allowlist. Para localizar com segurança o ID, um responsável pode executar somente:

```sql
SELECT id, email, nome, status FROM mini_erp.usuarios ORDER BY id;
```

Não copie hashes e não coloque senha na configuração. Se nenhuma identidade adequada e com credencial conhecida existir, o login autorizado é **NÃO TESTÁVEL MANUALMENTE NESTA TASK**, pois criar ou alterar usuário exigiria escrita fora do escopo.

1. Abra `/plataforma/login.php`.
2. Informe e-mail e senha já conhecidos da identidade autorizada.
3. Clique em “Entrar no Control-Plane”.

Resultado esperado: sessão regenerada, `platform_user_id` registrado e redirecionamento ao dashboard.

## Teste com usuário comum

1. Use uma identidade ativa cujo ID não esteja na allowlist.
2. Mesmo que possua `role=admin` em tenant, tente entrar pelo login do painel.

Resultado esperado: mensagem genérica de credenciais inválidas ou identidade não autorizada; nenhum dashboard exibido.

## Teste do dashboard

Após login autorizado, devem aparecer cabeçalho, botão “Sair”, título “Dashboard”, Empresas e os módulos Usuários, Provisionamento, Bloqueios, Auditoria e Acesso técnico marcados “Em implementação”. Os módulos pendentes não possuem ações funcionais.

## Teste da lista de empresas

1. No dashboard, confira a tabela do módulo Empresas.
2. Confira `tenant_id`, Razão social, Nome fantasia, CNPJ, Slug e Status.
3. Confirme que não existem botões de criar, editar, excluir, bloquear ou provisionar.

Não devem aparecer senha, hash, token de usuário, DSN, credenciais, segredo ou `db_name`.

## Teste de logout administrativo

1. Clique em “Sair”.
2. Tente voltar diretamente ao dashboard.

Resultado esperado: `platform_user_id` removido, redirecionamento para o login e sessão ERP não destruída. GET direto em `/plataforma/logout.php` retorna HTTP 405.

## Teste de isolamento

Execute sem banco:

```bat
C:\xampp\php\php.exe tests\PlatformControlPlaneTest.php
C:\xampp\php\php.exe tests\PlatformEntrypointIsolationTest.php
```

Os testes comprovam que `tenant_id` e `current_company_id` são preservados, a sessão usa `platform_user_id`, nenhum seletor de banco tenant participa e o reader contém somente `SELECT`.

## Teste do ERP antigo

1. Em outra aba, abra `http://localhost:8000/login.php`.
2. Use o fluxo legado com uma conta de empresa conhecida.
3. Confirme que o ERP continua separado do painel.
4. Faça logout apenas do painel e confirme que o contexto ERP permanece.

O teste funcional do ERP depende dos dados locais e não foi automatizado nesta task.

## Cenários negativos

### Acesso direto sem login

Passos: abra `/plataforma/` em janela anônima. Resultado esperado: redirecionamento ao login próprio.

### Usuário comum ou admin de tenant

Passos: autentique identidade fora da allowlist. Resultado esperado: acesso negado de forma genérica e fail-closed.

### Sessão expirada

Passos: remova o cookie ou encerre o navegador e reabra o dashboard. Resultado esperado: retorno ao login.

### URL inválida ou parâmetros externos

Passos: acesse `/plataforma/inexistente` e `/plataforma/?tenant_id=1&db_name=mini_erp_tenant_1`. Resultado esperado: 404 na primeira; parâmetros ignorados na segunda.

### CSRF inválido

Passos: submeta login ou logout sem o token do formulário. Resultado esperado: login rejeitado ou logout HTTP 403.

## Checklist final

- [ ] painel possui login separado
- [ ] painel abre
- [ ] usuário não autorizado não entra
- [ ] dashboard aparece
- [ ] empresas aparecem
- [ ] nenhuma empresa pode ser alterada nesta task
- [ ] nenhum banco tenant foi aberto
- [ ] sessão tenant não mudou
- [ ] nenhum dado sensível aparece
- [ ] ERP legado continua funcionando

## Resultado manual

Data:

Testado por:

Ambiente:

Resultado:

Problemas encontrados:
