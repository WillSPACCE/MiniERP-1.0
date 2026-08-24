# Contrato da chave de acesso NF-e/NFC-e

Base normativa consultada em 2026-08-21: MOC oficial e NT Conjunta 2025.001, complementada pela NT 2026.004 v1.01. A chave continua com 44 posições, mas pode ser alfanumérica nas posições do CNPJ. Para o DV, cada caractere é convertido pelo código ASCII menos 48 e aplicado módulo 11, com pesos 2–9 da direita para a esquerda.

| Campo | Origem ERP | Posição | Tamanho |
|---|---|---:|---:|
| cUF | snapshot do emitente/IBGE | 1–2 | 2 |
| AAMM | data de emissão reservada | 3–6 | 4 |
| CNPJ | snapshot do emitente | 7–20 | 14 |
| mod | snapshot do documento | 21–22 | 2 |
| serie | `fiscal_series` | 23–25 | 3 |
| nNF | reserva fiscal | 26–34 | 9 |
| tpEmis | configuração da série | 35 | 1 |
| cNF | código criptograficamente seguro persistido | 36–43 | 8 |
| cDV | derivado por módulo 11 alfanumérico | 44 | 1 |

Validação da chave vigente: `[0-9]{6}[A-Z0-9]{12}[0-9]{26}`. O gerador não recebe pedido/documento como substituto para série ou nNF e não persiste sozinho. Fontes: Portal NF-e, NT Conjunta 2025.001 e NT 2026.004 v1.01; Receita Federal, projeto CNPJ Alfanumérico.
