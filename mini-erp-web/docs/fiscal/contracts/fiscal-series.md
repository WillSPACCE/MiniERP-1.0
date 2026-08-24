# Contrato de séries fiscais

Identidade: `tenant_id + establishment_id + model + series`, com ambiente de homologação 2. Modelos permitidos: 55 e 65. O browser configura, mas `FiscalNumberAllocator` é a autoridade exclusiva do número.

Antes de uso, edição controlada exige motivo. Após reserva, contador e ambiente são imutáveis; outra identidade exige nova série. Auditoria registra `CREATE`, `UPDATE`, `ACTIVATE` ou `DEACTIVATE`, ator, motivo e estados anterior/posterior. Reserva usa transação, lock da série e unicidade de documento e número.
