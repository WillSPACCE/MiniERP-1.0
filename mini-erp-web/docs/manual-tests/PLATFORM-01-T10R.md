# Teste manual — PLATFORM-01-T10R

1. Execute `start-platform-server.bat`.
2. Abra `http://localhost:8000/plataforma/` e autentique o PlatformAdmin.
3. Localize **Willyan Info** e clique em **Acessar ERP**.
4. Confirme `/erp/login.php?empresa=willyaninfo` e que não houve login automático.
5. Entre com o usuário ativo criado para a empresa.
6. Confirme o redirecionamento para `http://localhost:8000/?page=dashboard`.
7. Confirme o dashboard, sidebar, perfil, menu e estilização históricos.
8. Abra Cadastro → Pessoas, Produtos e Fornecedores.
9. Execute o teste automatizado de integração para confirmar `tenant_id=14` e `DATABASE()=mini_erp_tenant_14`; o nome do banco não é exibido na UI.
10. Saia pelo menu do ERP.
11. Volte ao Painel e confirme que `platform_user_id` permaneceu autenticado.

## Checklist

- [ ] login empresa funciona
- [ ] dashboard legado abriu
- [ ] layout, menu e perfil antigos foram preservados
- [ ] Clientes/Pessoas abre
- [ ] Produtos abre
- [ ] Fornecedores abre
- [ ] tenant é 14
- [ ] banco é `mini_erp_tenant_14`
- [ ] nenhum dado operacional do MAIN aparece
- [ ] nenhum fallback tenant 1
- [ ] logout ERP funciona
- [ ] sessão do Painel permanece
