# Teste manual — PLATFORM-01-T06

> Usuário criado com sucesso no painel não significa ainda que o botão Acessar ERP/login da empresa esteja implementado. Não teste login ERP nesta task.

1. Abra `cmd.exe`.
2. Execute `cd /d C:\xampp\htdocs\MiniRP\mini-erp-web`.
3. Execute `start-platform-server.bat` ou informe um ID autorizado.
4. Abra `http://localhost:8000/plataforma/`.
5. Faça login no Painel da Plataforma.
6. Localize tenant 14, Willyan Info.
7. Clique em Usuários.
8. Clique em Novo usuário e use um e-mail artificial exclusivo, por exemplo `teste.t06.20260820@example.invalid`, e senha temporária que tenha ao menos oito caracteres, letra e número.
9. Confirme “Usuário criado com sucesso” e sua presença apenas na listagem do tenant 14.
10. Edite somente o nome e salve.
11. Clique em Desativar e confirme status `inativo`.
12. Clique em Ativar e confirme status `ativo`.
13. Abra Redefinir senha, informe e confirme uma nova senha compatível.
14. Confirme que a URL e o cabeçalho continuam mostrando tenant 14.
15. Abra outro tenant e confirme que o usuário artificial não aparece.
16. No phpMyAdmin, abra `mini_erp` → `usuarios`; localize o e-mail artificial e confirme `tenant_id = 14` e `company_id = 14`. Não visualize/copiei o hash.
17. Confirme que `mini_erp_tenant_14.usuarios` não recebeu cópia.

## Limpar somente o usuário artificial

Primeiro confirme exatamente o alvo:

```sql
SELECT id, email, tenant_id FROM mini_erp.usuarios
WHERE email = 'teste.t06.20260820@example.invalid' AND tenant_id = 14;
```

Somente se retornar exatamente o registro artificial criado por você, remova-o pelo phpMyAdmin ou execute conscientemente:

```sql
DELETE FROM mini_erp.usuarios
WHERE email = 'teste.t06.20260820@example.invalid' AND tenant_id = 14;
```

Não use condição ampla e não remova usuários reais.

Resultado esperado: criação, edição, status e senha funcionam no MAIN; nenhum outro tenant, banco tenant, lifecycle, bloqueio ou sessão ERP é alterado.

Data:

Testado por:

Ambiente:

Resultado:

Problemas encontrados:
