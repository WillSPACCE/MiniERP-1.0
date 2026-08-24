# PLATFORM-AUTH-01 — autenticação exclusiva do Control-Plane

O caminho anterior consultava `usuarios`, armazenava `platform_user_id`, autorizava IDs ERP por `PLATFORM_ADMIN_USER_IDS` e aceitava `admin@localhost/admin`. Esse caminho foi removido exclusivamente do runtime `/plataforma/`; o ERP legado não foi alterado.

O MAIN agora contém `platform_admin_users` e `platform_admin_audit_log`. Senhas usam `password_hash`/`password_verify`; após cinco falhas a conta fica bloqueada por 15 minutos. Sucesso, falha, logout, criação e alteração de senha são auditados sem senha, hash ou token.

A sessão usa cookie `MINIERP_PLATFORM`, path `/plataforma`, HttpOnly, SameSite Strict e Secure quando HTTPS. O namespace `platform_admin` contém somente admin_id, email, role e authenticated_at. O ID é relido no MAIN em cada rota protegida.

Não existe credencial padrão. Quando a tabela está vazia, o login informa que nenhum administrador foi configurado. Criação e reset ocorrem por CLI, com senha solicitada via entrada padrão e nunca em argumento.

Roles reconhecidas: SUPER_ADMIN, SUPPORT, DATABASE_ADMIN e AUDITOR. Esta task não inventa permissões finas; todas representam identidades administrativas persistidas e ativas.

Database Manager continua read-only. A identidade usada pelas Operações Multi-tenant vem da sessão administrativa, nunca de usuário ERP.
