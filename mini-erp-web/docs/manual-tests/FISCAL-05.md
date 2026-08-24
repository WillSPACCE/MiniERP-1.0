# Teste manual FISCAL-05

Executar apenas em transação/ambiente de teste:

1. Inserir versão de regra marcada `TEST_ONLY`, com fonte também `TEST_ONLY`.
2. Montar `FiscalTaxContext` explícito com Establishment, Person, Product e operação.
3. Resolver e conferir regra, versão, CFOP, grupos e `matchedBy`.
4. Executar a prévia decimal e conferir explicação.
5. Alterar o contexto para não casar e confirmar fail closed.
6. Inserir regra empatada e confirmar conflito.
7. Conferir vigência e regra expirada.
8. Usar tenant diferente e confirmar zero candidatos.
9. Fazer rollback e confirmar zero linhas `TEST_ONLY`.
10. Confirmar que nenhum XML, assinatura, certificado ou chamada SEFAZ ocorreu.
