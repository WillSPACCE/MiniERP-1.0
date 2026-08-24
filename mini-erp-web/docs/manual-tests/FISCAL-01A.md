# Teste manual — FISCAL-01A

> **NÃO EXECUTAR SEM BACKUP/CONFIRMAÇÃO.** O tenant 14 contém dados reais. Não cadastre informações fictícias somente para concluir o teste.

## Estado validado em 21/08/2026

- Banco: `mini_erp_tenant_14`.
- Backup anterior ao DDL: `C:\xampp\htdocs\MiniRP\backups\FISCAL-01A\mini_erp_tenant_14-pre-fiscal01a-20260821-114239.sql`.
- SHA-256: `AC872DD81F206AD887F1280CF64936A382F03E8C7DE0FE3F5D31258A3CA8833E`.
- Migration aplicada: `20260821_create_tenant_establishments.sql`.
- Tabela criada e vazia após o teste transacional.

## Conferência para usuário não especialista

1. Confirme que existe backup válido antes de qualquer alteração.
2. Inicie o servidor pela inicialização da plataforma.
3. No phpMyAdmin, confirme que o banco escolhido é `mini_erp_tenant_14`.
4. Abra a aba SQL somente se precisar aplicar a migration; não execute a pasta inteira nem use o banco `mini_erp`.
5. No phpMyAdmin, abra `mini_erp_tenant_14` e confirme a tabela `establishments`.
6. Abra o Painel, entre e selecione Willyan Info → Empresa → Cadastro fiscal.
7. Escolha uma opção segura: preencher os dados reais corretos da empresa ou usar um tenant exclusivamente de teste.
8. Salve, reabra e compare todos os campos.
9. Altere um campo apropriado, salve, reabra e confirme a alteração.
10. Entre no ERP com um usuário do tenant 14 e abra Configuração → Empresa.
11. Confirme que os mesmos valores aparecem e que `city_ibge_code` está separado do município.
12. Confirme CRT como código 1, 2, 3 ou 4 e readiness `INCOMPLETE`.
13. Confirme no MAIN que não houve duplicação dos dados fiscais.
14. Confirme que nenhuma NF-e/NFC-e foi emitida e que certificado/CSC continuam inexistentes.

Não há cleanup de registro real automático. O teste automatizado usa fixture identificável dentro de transação e executa rollback.
