# Teste manual — PLATFORM-01-T10R2

1. Execute `start-platform-server.bat <ID_PLATFORM_ADMIN>`.
2. Abra `http://localhost:8000/plataforma/`.
3. Localize **Willyan Info** e clique em **Acessar ERP**.
4. Confirme a URL `http://localhost:8000/login.php?empresa=willyaninfo`.
5. Confirme logo MiniERPWeb, layout azul/branco histórico e **Empresa: Willyan Info**.
6. Confirme que **Entrar em Default Tenant** não aparece.
7. Entre com o usuário ativo do tenant 14.
8. Confirme `http://localhost:8000/?page=dashboard` e o dashboard histórico.
9. Confira sidebar, perfil, cards, gráficos, Dashboard, Pedidos, Cadastro e Configuração.
10. Abra Pessoas/Clientes, Produtos e Fornecedores sem executar gravações durante esta validação.
11. Execute a prova automatizada de conexão: tenant 14 deve retornar `mini_erp_tenant_14`.
12. Teste senha errada e usuário pertencente a outro tenant.
13. Saia pelo ERP e confirme retorno para `/login.php?empresa=willyaninfo`.
14. Retorne ao Painel e confirme que o PlatformAdmin continua autenticado.

## Checklist

- [ ] login antigo estilizado utilizado
- [ ] empresa correta no login
- [ ] Default Tenant não aparece
- [ ] usuário tenant 14 autentica
- [ ] dashboard, sidebar e menus antigos aparecem
- [ ] banco dedicado do tenant 14 é usado
- [ ] cross-tenant é negado
- [ ] logout retorna à mesma empresa
- [ ] sessão Platform é preservada
- [ ] nenhuma UI ERP paralela aparece
