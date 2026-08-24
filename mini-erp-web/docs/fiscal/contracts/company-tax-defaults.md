# Contrato — defaults tributários

ICMS é normalizado por UF e vigência. PIS/COFINS e IPI persistem CST como string, preservando zero inicial. IPI possui aplicabilidade e cEnq separado. RTC tem escopo ALL/55/65. cClassTrib e classificação IS devem vir de `fiscal_classifications`; ausência da referência oficial é pendência, nunca convite a inventar códigos.

Funrural, crédito SN e exclusão do ICMS da base PIS/COFINS apenas alimentam contexto aplicável. Nenhum deles é aplicado universalmente.
