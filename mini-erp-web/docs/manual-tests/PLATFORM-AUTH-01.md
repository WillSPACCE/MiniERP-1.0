# Teste manual — PLATFORM-AUTH-01

1. Sem admins, abra `/plataforma/login.php` e confirme a mensagem de configuração ausente.
2. Execute `C:\xampp\php\php.exe bin\create-platform-admin.php` e escolha nome, e-mail e senha forte.
3. Entre com senha incorreta e confira a mensagem genérica.
4. Entre corretamente e confira “Administrador”, role, Minha conta e Sair.
5. Abra o ERP em sessão separada e confirme que login/logout não se misturam.
6. Em Minha conta, teste CSRF, senha atual incorreta, confirmação divergente e troca válida.
7. Saia e confirme que a rota protegida redireciona ao login.
8. Para reset CLI, execute `C:\xampp\php\php.exe bin\reset-platform-admin-password.php seu-email`.
9. Confira `platform_admin_audit_log` sem expor metadata sensível.
10. Confirme Database Manager READ-ONLY e EXECUTAR OPERAÇÃO desabilitado.
