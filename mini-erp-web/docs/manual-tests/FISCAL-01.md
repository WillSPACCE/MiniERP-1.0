# Teste manual — FISCAL-01

> **NÃO EXECUTAR SEM BACKUP/CONFIRMAÇÃO** se os passos forem realizados no tenant 14 (`mini_erp_tenant_14`) ou com quaisquer dados reais.

1. Faça backup do banco artificial/alvo e aplique manualmente `migrations/20260821_create_tenant_establishments.sql` somente no banco dedicado.
2. Inicie o servidor e abra o Painel da Plataforma.
3. Entre, localize Willyan Info, abra Empresa e depois Cadastro fiscal.
4. Em ambiente apropriado, preencha dados fiscais de teste, salve e reabra.
5. Confira todos os campos e o checklist `INCOMPLETE`.
6. Entre no ERP do mesmo tenant e abra Configuração → Empresa.
7. Confira os mesmos dados, altere um valor de teste, salve e reabra nas duas superfícies.
8. Consulte `establishments` no banco dedicado e confirme `tenant_id`; confirme que o MAIN não recebeu dados fiscais operacionais.
9. Confirme que certificado, CSC, NF-e/NFC-e e homologação continuam pendentes e que nenhuma emissão/transmissão ocorreu.

Cleanup: reverta somente os dados artificiais criados, de forma consciente e após identificar exatamente o banco. Nunca execute cleanup contra tenant real.
