# Teste manual — FISCAL-03

Antes de usar tenant 14, faça backup e use somente dados reais ou fixture autorizada.

- PJ: ERP → Cadastro → Pessoas → Nova Pessoa; marque Cliente, escolha PJ, preencha campos aplicáveis, salve, reabra, edite e confira banco.
- PF: repita com PF/CPF e RG opcional.
- Fornecedor: marque o papel Fornecedor; confirme round-trip e futura disponibilidade para Entrada.
- Transportadora: marque o papel correspondente; não espere transporte fiscal nesta task.
- Estrangeiro: escolha Estrangeiro, deixe CPF/CNPJ vazio e informe identificação/país.
- Inative pela ação de remoção e confirme `status=inativo`.
- Confirme que outro tenant não vê o registro e que o MAIN não recebeu Pessoa operacional.
- Confirme ausência de XML, certificado e chamada SEFAZ.
