# Teste manual — PLATFORM-01-T10A

Pré-condições: MAIN e banco dedicado já existentes; empresa ativa, não bloqueada e provisionada; usuário ativo no MAIN com `tenant_id` da empresa. Este roteiro não cria nem altera dados.

1. No Painel da Plataforma, acione **Acessar ERP** para uma empresa ativa.
2. Confirme redirecionamento para `/erp/login.php?empresa={slug}` e ausência de login automático.
3. Tente senha incorreta e confirme mensagem genérica, sem revelar existência do usuário ou banco.
4. Tente usuário de outra empresa e confirme negação.
5. Entre com usuário ativo da empresa e confirme dashboard com nome da empresa e identidade do usuário.
6. Abra Clientes e Produtos e confira que os dados pertencem ao banco dedicado esperado.
7. Bloqueie a empresa por procedimento já existente, sem alterar este roteiro; uma sessão ERP subsequente deve ser recusada. Desfaça o bloqueio pelo mesmo procedimento.
8. Abra simultaneamente uma sessão do Painel e uma do ERP; saia do ERP e confirme que o Painel permanece autenticado.
9. Confirme que a URL/formulário não aceitam `tenant_id`, `company_id` ou `db_name` como autoridade.

Resultado esperado: autenticação isolada por empresa, contexto canônico e conexão dedicada, sem fallback, impersonação ou mutação da sessão legada.
