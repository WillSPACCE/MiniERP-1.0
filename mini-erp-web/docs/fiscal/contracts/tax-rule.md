# Contrato de regra tributária

Cada linha em `tax_rule_versions` é uma versão imutável tenant-scoped. Alteração fiscal cria nova versão; não há fallback global ou tenant 1.

Campos obrigatórios: `rule_code`, `rule_version`, `priority`, `valid_from`, fonte/documento, versão/data da fonte, condições, CFOP final e resultados separados de ICMS, IPI, PIS, COFINS, IBS/CBS e IS. `valid_to` é opcional.

Precedência determinística: maior `priority`, depois maior especificidade, depois vigência mais recente. Empate integral falha com `More than one fiscal rule matches this operation.` Ausência também falha fechada.

`fixture_kind=TEST_ONLY` nunca representa legislação real. `product_taxes` é `LEGACY_TAX_DATA` e não é candidata automática.
