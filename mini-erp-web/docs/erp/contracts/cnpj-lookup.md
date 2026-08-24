# Contrato de consulta CNPJ

Fluxo: formulário → endpoint interno autenticado → `CnpjLookupService` → `BrasilApiCnpjProvider` → `https://brasilapi.com.br/api/cnpj/v1/{cnpj}` → `CnpjLookupResult`.

Endpoints: ERP `GET /ajax_cnpj.php?cnpj=...`; Control-Plane `GET /plataforma/ajax-cnpj.php?cnpj=...`.

O DTO expõe `tax_id`, `legal_name`, `trade_name`, situação/abertura, endereço, `city_ibge_code`, país, CNAE, telefones, e-mail, porte, natureza, capital, Simples/MEI, matriz/filial, CNAEs secundários, QSA e histórico tributário. `city_ibge_code` vem exclusivamente de `codigo_municipio_ibge`. O tipo do logradouro não é duplicado.

CNPJ é normalizado em maiúsculas removendo apenas pontuação; letras são preservadas. Formato aceito: 12 caracteres alfanuméricos mais dois dígitos verificadores.

Erros: `CNPJ_INVALID` (400), `CNPJ_NOT_FOUND` (404), `CNPJ_RATE_LIMIT` (429), `CNPJ_SERVICE_TIMEOUT` (504), `CNPJ_SERVICE_UNAVAILABLE` (503), `CNPJ_PROVIDER_INVALID_RESPONSE` (502).
