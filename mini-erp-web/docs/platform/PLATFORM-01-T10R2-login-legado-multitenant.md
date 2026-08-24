# PLATFORM-01-T10R2 — Login legado estilizado no fluxo multi-tenant

## Problema corrigido

Embora a T10R já entregasse o contexto seguro ao dashboard histórico, o botão do Painel ainda abria o formulário simplificado criado na T10A. Além disso, `public/login.php` resolvia a empresa pela sessão legada e construía um `Repository`, permitindo exibir o tenant anterior ou “Default Tenant”.

## Fluxo oficial

Painel → `/login.php?empresa={slug}` → UI histórica MiniERPWeb → `ErpAuthenticationService` → validação do PDO pelo `TenantConnectionResolver` → sessão `erp_user_id`/`erp_tenant_id` → `/?page=dashboard` → `ErpLegacyBootstrap` → ERP histórico completo.

O slug é consultado no MAIN apenas para identificar a empresa pública e validar a correspondência com o `tenant_id` natural do usuário. Ele não fornece autorização nem `db_name`. Slug inexistente falha fechado e não cai no tenant 1.

O arquivo `public/login.php` continua usando os mesmos `style.css`, `login.css`, `login.js`, logo, containers e componentes. Somente o controller e o destino do formulário mudam quando `?empresa=` está presente. `/erp/login.php` agora é apenas um redirect de compatibilidade.

## Bridges e isolamento

- autenticação: uma única regra em `ErpAuthenticationService`;
- sessão: `erp_user_id` e `erp_tenant_id` são canônicos; `user_id` e `tenant_id` são derivados somente no bootstrap legado;
- `current_company_id`: removido e nunca usado como autoridade;
- banco: o login valida a conexão e o bootstrap injeta no legado o PDO resolvido pelo contexto;
- logout: limpa sessão ERP/compatibilidade, preserva `platform_user_id` e retorna ao login da mesma empresa pelo slug não autoritativo previamente validado.

## Dívida técnica

O login global sem `?empresa=` e o controller antigo de login permanecem como compatibilidade legada isolada. Os CRUDs ainda precisam das tasks ERP-CRUD verticais. `public/index.php`, `Repository` e alguns caminhos antigos de `Database::setTenantDbName()` continuam legados.
