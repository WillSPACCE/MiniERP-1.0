# Teste manual — PLATFORM-02A2

1. Inicie o Painel da Plataforma e autentique-se.
2. Confirme que Default Tenant e Empresa Teste 1, 2 e 3 não aparecem.
3. Confirme INFOCASE (tenant 5) e willyan info (tenant 14).
4. Busque cada empresa e confira a paginação.
5. Abra INFOCASE → Banco de Dados e confirme `READ-ONLY` e `OUTDATED_OR_DRIFT` com 34 diferenças.
6. Confira clientes 3, produtos 4, vendas 2 e itens 2.
7. Confira as tabelas fiscais novas e a ausência de regras, certificados, séries, documentos e artifacts.
8. Abra willyan info → Banco de Dados e confirme `READ-ONLY` e o diff reportado, sem executar correção.
9. Não use SQL write. A conferência visual ficou pendente nesta execução por indisponibilidade de navegador conectado.
10. O teste automatizado de provisionamento TEST_ONLY já confirmou schema v1, zero-data e cleanup.
