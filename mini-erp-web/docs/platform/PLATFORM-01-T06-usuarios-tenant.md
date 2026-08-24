# PLATFORM-01-T06 — Usuários por tenant

## Modelo escolhido

`mini_erp.usuarios` é o diretório canônico. O login legado busca identidade no MAIN sem tenant inicialmente, e a tabela central possui `tenant_id`, índice desse campo, senha, role, status e dados auxiliares. O e-mail possui índice UNIQUE global; portanto, a regra implementada é unicidade global, não apenas por tenant.

O `usuarios` local do tenant 14 tem apenas oito colunas, não possui `tenant_id` e está vazio. Ele é uma representação legada incompleta e não recebe sincronização na T06. Nenhum banco tenant é aberto.

## Relação e compatibilidade

Toda operação recebe um `AdministrativeContext` composto por PlatformAdmin e `SelectedTenant` explicitamente validado. `tenant_id` vem do registro MAIN da empresa selecionada. A UI não envia `tenant_id`, `company_id` ou `db_name`. Na criação, `company_id` recebe o mesmo valor de `tenant_id` exclusivamente como compatibilidade legada; nunca decide escopo.

Listagem usa `WHERE tenant_id = :tenant_id`. Leitura e todas as escritas em usuário existente usam `WHERE id = :id AND tenant_id = :tenant_id`. Não há movimentação entre tenants nem exclusão física.

## Identidade, roles e status

- e-mail: trim, lowercase, validação `FILTER_VALIDATE_EMAIL` e unicidade global;
- senha: nunca recuperada ou exibida; mínimo de oito caracteres com letra e número; `password_hash(PASSWORD_DEFAULT)`;
- roles: `admin` (Administrador da empresa) e `user`; nenhum deles concede PlatformAdmin;
- status: `ativo` e `inativo`;
- contas criadas pelo PlatformAdmin são marcadas `email_verified = 1`, pois a criação já é administrativa.

## Lifecycle

Gestão é permitida para empresas com banco dedicado e status `ativo`/`ativa`, `parcialmente_bloqueada` ou `bloqueada`. Empresa `cadastrada`, `provisionando`, arquivada, desconhecida ou sem `db_name` falha de forma fechada. Gestão de usuários de empresa bloqueada não altera bloqueio e não concede acesso operacional.

## Segurança HTTP

Todas as rotas revalidam PlatformAdmin, tenant e contexto. GET apenas lista/exibe formulários. Criar, editar, ativar/desativar e redefinir senha exigem POST e CSRF. Não há mutação de `tenant_id`/`current_company_id` da sessão ERP, impersonação, fallback tenant 1 ou conexão com banco dedicado.

## Compatibilidade e pendências

O legado ainda possui fluxos que alternam conexão, criam representação local e usam sessão. Eles não foram reutilizados. T07 tratará bloqueio operacional; T08 auditoria; T10 login/entrada no ERP. Uma eventual representação local de usuário só poderá ser introduzida por decisão explícita futura.
