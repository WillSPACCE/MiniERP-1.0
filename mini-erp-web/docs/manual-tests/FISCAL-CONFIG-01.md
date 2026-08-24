# Teste manual FISCAL-CONFIG-01

1. Criar banco/tenant `TEST_ONLY`, aplicar a migration e abrir Painel → Empresa → Central Fiscal.
2. Salvar ambiente 2/modelo; quatro CFOPs cadastrados coerentes; CSC HOMO; ICMS PR e SP com vigências; PIS/COFINS; IPI; IBS/CBS; IS.
3. Atualizar e confirmar round-trip, zeros dos CSTs e decimais.
4. Confirmar token CSC ausente do HTML/banco/log e apenas sufixo visível.
5. Tentar CFOP de prefixo errado, decimal inválido e ambiente 1; conferir erro e preservação dos demais campos, sem CSC reapresentado.
6. Consultar com outro tenant e confirmar isolamento. Verificar auditoria before/after com CSC redigido.
7. Confirmar production_ready falso e zero chamadas SEFAZ.
