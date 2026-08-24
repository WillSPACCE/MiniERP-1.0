# FISCAL-03 — Pessoa/Destinatário/Fornecedor

Decisão incremental: `clientes` passa a ser Pessoa canônica com múltiplos papéis. As tabelas legadas `fornecedores` e `transportadoras` são preservadas, mas não são fonte nova nem recebem duplicação automática. Uma reconciliação futura poderá referenciá-las por `person_id` após análise dos dados.

PF usa CPF; PJ usa CNPJ numérico ou alfanumérico; estrangeiro usa `foreign_id`. `indIEDest` é canônico: `1` contribuinte, `2` isento, `9` não contribuinte. IE é exigida pelo backend quando indicador 1. IBGE permanece separado do município.

Duplicidade é bloqueada no repository para CPF/CNPJ preenchido dentro do banco tenant. Não há unicidade global. Documentos vazios legados e estrangeiros impedem índice simples; por isso a regra não foi transformada em índice incorreto.

Não existe estrutura real de grupos. Grupo permanece gap. Delete da UI virou inativação para preservar referências futuras. Nenhum Pedido, Entrada, Transporte fiscal, snapshot ou XML foi implementado.
