# Contrato de dados — Establishment

| Campo ERP | Coluna | Tag XML | Obrigatoriedade | Condição | Status | Validação |
|---|---|---|---|---|---|---|
| CNPJ | `tax_id` | `emit/CNPJ` | Sempre | Emitente PJ | Implementado | 14 posições; 12 alfanuméricas + 2 DV |
| Razão social | `legal_name` | `emit/xNome` | Sempre | — | Implementado | obrigatório, 150 |
| Nome fantasia | `trade_name` | `emit/xFant` | Opcional | informado | Implementado | 150 |
| IE | `state_registration` | `emit/IE` | Sempre | emissão | Implementado | obrigatório, 30 |
| IEST | `st_registration` | `emit/IEST` | Condicional | substituto | Implementado | 30 |
| IM | `municipal_registration` | `emit/IM` | Condicional | aplicável | Implementado | 30 |
| CNAE | `cnae` | `emit/CNAE` | Condicional | aplicável | Implementado | até 7 dígitos |
| CRT | `tax_regime_code` | `emit/CRT` | Sempre | — | Implementado | códigos 1–4 |
| Logradouro | `street` | `enderEmit/xLgr` | Sempre | — | Implementado | obrigatório |
| Número | `number` | `enderEmit/nro` | Sempre | — | Implementado | obrigatório |
| Complemento | `complement` | `enderEmit/xCpl` | Opcional | informado | Implementado | 100 |
| Bairro | `district` | `enderEmit/xBairro` | Sempre | — | Implementado | obrigatório |
| Código município | `city_ibge_code` | `enderEmit/cMun` | Sempre | — | Implementado | 7 dígitos |
| Município | `city_name` | `enderEmit/xMun` | Sempre | — | Implementado | obrigatório |
| UF | `state` | `enderEmit/UF` | Sempre | — | Implementado | 2 letras |
| CEP | `postal_code` | `enderEmit/CEP` | Opcional XML | cadastro base | Implementado | 8 dígitos |
| Código país | `country_code` | `enderEmit/cPais` | Opcional/derivado | Brasil=1058 | Implementado | numérico |
| País | `country_name` | `enderEmit/xPais` | Opcional | informado | Implementado | 60 |
| Telefone | `phone` | `enderEmit/fone` | Opcional | informado | Implementado | 30 |
| E-mail | `email` | uso ERP | Opcional | informado | Implementado | e-mail válido |
| Tipo | `establishment_type` | contexto do emitente | Sempre | FISCAL-01 usa MATRIZ | Implementado | valor controlado pelo backend |
| Principal | `is_primary` | contexto do emitente | Sempre | uma matriz nesta fase | Implementado | `1` controlado pelo backend |
| Status | `status` | não aplicável | Sempre | cadastro operacional | Implementado | ativo/inativo |
| Readiness | `fiscal_readiness` | não aplicável | Sempre | separado do lifecycle | Implementado | `INCOMPLETE` nesta fase |

Os status acima significam round-trip do cadastro, não geração de XML, que permanece fora do escopo.
