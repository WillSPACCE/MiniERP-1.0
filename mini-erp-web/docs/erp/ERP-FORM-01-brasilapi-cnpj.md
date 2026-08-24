# ERP-FORM-01 — BrasilAPI CNPJ

## Resultado

A consulta usa exclusivamente a BrasilAPI, na rota operacional `https://brasilapi.com.br/api/cnpj/v1/{cnpj}`, por meio de `CnpjLookupService` e `BrasilApiCnpjProvider`. Em 24/08/2026, a variante sem `/api` informada no prompt retornou HTTP 404 e por isso não foi adotada. O navegador chama apenas endpoints internos autenticados. A consulta nunca persiste dados; o usuário confirma em **Salvar**.

## Inventário e cobertura

| Tela | Arquivo/campo | Decisão |
|---|---|---|
| Control-Plane — Nova empresa | `public/plataforma/empresa-nova.php` / `cnpj` | Lookup implementado |
| Control-Plane — Editar empresa | `public/plataforma/empresa-editar.php` / `cnpj` | Lookup implementado |
| Control-Plane — Fiscal | `public/includes/establishment_form.php` / `tax_id` | Lookup implementado |
| ERP — Configuração/Empresa | `public/index.php` / `cnpj` | Lookup implementado |
| ERP — Estabelecimento fiscal | `public/includes/establishment_form.php` / `tax_id` | Lookup implementado |
| ERP — Pessoa PJ | `public/index.php` / `cpf_cnpj` | Lookup implementado; destinado a PJ |
| ERP — Cliente separado | `public/index.php` / `cpf_cnpj` | Lookup implementado para CNPJ; CPF continua manual |
| ERP — Fornecedor | `public/index.php` / `cpf_cnpj` | Lookup implementado para CNPJ |
| ERP — Transportadora | `public/index.php` / `cpf_cnpj` | Lookup implementado para CNPJ |
| Usuário — criar cliente associado | `public/index.php` / `cpf_cnpj_cliente` | Sem lookup: campo misto e formulário não contém dados empresariais a preencher |

Cobertura: 9 de 9 ocorrências de CNPJ empresarial com lookup (100%). Uma ocorrência mista CPF/CNPJ tem justificativa explícita.

## Comportamento

- Campos vazios recebem os dados normalizados.
- Campos iguais permanecem intactos.
- Divergências não são sobrescritas e oferecem **Usar dado consultado**.
- Situação diferente de ATIVA gera aviso, sem bloquear o cadastro.
- Simples, MEI, histórico tributário, QSA e matriz/filial são somente informativos.
- IE, IEST, IM, CRT, CSC, certificado, séries, CFOP, CST e CSOSN nunca são inferidos.

## Segurança e resiliência

Endpoint fixo, sem URL fornecida pelo browser; TLS permanece verificado; timeouts de conexão/total são 5/8 segundos; User-Agent `MiniERP/1.0`; cache local de 15 minutos; limite de 10 consultas por sessão/minuto. Falhas da API não impedem preenchimento manual.
