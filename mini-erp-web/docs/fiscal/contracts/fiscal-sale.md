# Contrato de Pedido Fiscal

`fiscal_orders` é a operação comercial editável, tenant-scoped, com estados `DRAFT/SAVED/CANCELLED` e status fiscal separado. Guarda tipo ENTRY/EXIT, pessoa, establishment, natureza, modelo, finalidade, consumidor, presença, pagamento, transporte e totais recalculados no backend com BCMath. Não baixa estoque e não emite.
