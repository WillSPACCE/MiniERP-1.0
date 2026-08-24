# Teste manual — PLATFORM-01-T04

Este teste executa criação real de banco somente depois da confirmação humana.

1. Abra o CMD.
2. Execute `cd /d C:\xampp\htdocs\MiniRP\mini-erp-web`.
3. Execute `start-platform-server.bat <ID_REAL_AUTORIZADO>`.
4. Abra `http://localhost:8000/plataforma/`.
5. Valide: [ ] sidebar [ ] header [ ] cards [ ] empresas [ ] status [ ] ações [ ] logout.
6. Localize tenant 14, Willyan Info.
7. Confirme `status = cadastrada` e `ambiente = Não provisionado`.
8. Clique em Provisionar.
9. Confira tenant 14 e banco derivado `mini_erp_tenant_14` na confirmação.
10. Clique em Confirmar provisionamento apenas se deseja criar o banco agora.
11. Aguarde o resultado, sem fechar ou repetir a requisição.
12. Retorne ao painel.
13. Confirme `status = ativa` e `ambiente = Banco dedicado disponível`.
14. Abra o phpMyAdmin e confirme `mini_erp_tenant_14`.
15. Inspecione as tabelas.
16. Confirme ausência de clientes, produtos, fornecedores, funcionários, vendas, movimentações e usuários de outra empresa.
17. Confirme: [ ] tenant_id continua 14 [ ] CNPJ não mudou [ ] slug não mudou [ ] blocked continua 0 [ ] db_name correto [ ] não pode provisionar novamente [ ] nenhum usuário criado [ ] ERP legado funcionando.

Se houver erro após a criação física, não apague nem adote o banco automaticamente. Registre a mensagem, o estado da empresa e as tabelas existentes para diagnóstico.

Data:

Testado por:

Ambiente:

Resultado:

Problemas encontrados:
