# PLATFORM-01-T01 — Bootstrap autenticado e somente leitura do Control-Plane

## Evidências do runtime legado

- A autenticação termina no caso `login` de `public/index.php`: o usuário é localizado por e-mail, a senha e o status são verificados e o identificador é gravado em `$_SESSION['user_id']`.
- A sessão legada também pode receber `tenant_id` e `current_company_id` durante o login. Esses campos pertencem ao contexto do ERP legado e não são usados pelo novo entrypoint para autorizar ou selecionar empresa.
- `admin@localhost` com senha de compatibilidade e a isenção por `role=admin` continuam no login legado. Eles não constituem política confiável de PlatformAdmin.
- Não existe no schema atual uma autorização persistida própria de PlatformAdmin. Por isso, a nova fronteira é fail-closed e não deriva autorização de e-mail, role, company ou tenant.
- A listagem legada de empresas usa `Repository::listCompanies()` e a tabela `tenants`. O novo fluxo não reutiliza `Repository`, pois construí-lo pode inicializar schema e alterar estrutura.
- O banco configurado em `config.php` (`DB_NAME`, padrão `mini_erp`) representa o banco principal/control-plane. Bancos cujo nome termina em `_tenant_<id>` são rejeitados pela factory do novo fluxo.

## Implementação mínima

O painel possui entradas próprias em `public/plataforma/`. O login consulta uma credencial ativa no MAIN, usa `password_verify()`, aplica a política explícita e grava somente `$_SESSION['platform_user_id']` após regenerar o ID da sessão. O dashboard revalida identidade e autorização antes de listar empresas.

`AuthenticatedPlatformAdmin` carrega apenas ID, e-mail e nome. Não contém tenant, empresa, banco, conexão, senha, request ou sessão.

## Autorização transitória e fail-closed

Como não existe autorização persistida própria, `ConfiguredPlatformAdminAuthorizer` lê a allowlist `PLATFORM_ADMIN_USER_IDS`. A variável contém IDs positivos separados por vírgula.

Por decisão operacional temporária, `admin@localhost` com senha `admin` também é aceito sem allowlist, desde que o registro exista e esteja ativo no MAIN. Essa exceção está isolada no authorizer/autenticador, não concede tenant e deve ser removida quando existir identidade PlatformAdmin persistida. Para as demais identidades, a senha continua sendo validada pelo hash persistido.

Esta é uma compatibilidade transitória de bootstrap, não o modelo definitivo. Fora da exceção explícita de `admin@localhost`, nem `role=admin` nem `tenant_id=1` concedem acesso implicitamente.

## Limites

- Não há seleção de tenant ou composição de `AdministrativeContext` nesta task.
- Não há acesso ao ERP, lifecycle, provisionamento, escrita, auditoria, suporte/master ou administração de usuários.
- A tabela de credenciais ainda é a tabela central `usuarios`, mas o formulário, o serviço de autenticação, a sessão e o logout do painel são próprios.
- A definição de uma identidade e autorização persistidas de PlatformAdmin permanece pendente.
