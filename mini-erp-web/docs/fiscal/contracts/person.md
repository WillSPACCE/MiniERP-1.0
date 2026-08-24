# Contrato fiscal de Pessoa

Modelo real: `clientes` é a Pessoa canônica do ERP e aceita múltiplos papéis; `fornecedores` e `transportadoras` permanecem legados independentes, sem migração automática. Não existe tabela de grupos: `group_id` é gap.

| Campo UI | name | Backend | Tabela/coluna | Origem | XML | Obrigatoriedade | Round-trip | Status |
|---|---|---|---|---|---|---|---|---|
| Tipo fiscal | `person_type` | PersonFiscalData | `clientes.person_type` | USER_INPUT | escolhe CPF/CNPJ/idEstrangeiro | sempre | sim | IMPLEMENTED_DATA |
| CPF/CNPJ | `cpf_cnpj` | PersonFiscalData | `clientes.cpf_cnpj` | USER_INPUT | `dest/CPF` ou `dest/CNPJ` | PF/PJ | sim | IMPLEMENTED_DATA |
| Identificação estrangeira | `foreign_id` | PersonFiscalData | `clientes.foreign_id` | USER_INPUT | `dest/idEstrangeiro` | estrangeiro | sim | IMPLEMENTED_DATA |
| Nome/Razão | `nome` | Repository | `clientes.nome` | USER_INPUT | `dest/xNome` | sempre | sim | IMPLEMENTED_DATA |
| Fantasia | `nome_fantasia` | Repository | `clientes.nome_fantasia` | USER_INPUT | cadastral | opcional | sim | IMPLEMENTED_DATA |
| Indicador IE | `state_registration_indicator` | PersonFiscalData | coluna homônima | USER_INPUT | `dest/indIEDest` | sempre | sim | IMPLEMENTED_DATA |
| IE | `inscricao_estadual` | PersonFiscalData | coluna homônima | USER_INPUT | `dest/IE` | indicador 1 | sim | IMPLEMENTED_DATA |
| RG | `rg` | PersonFiscalData | `clientes.rg` | USER_INPUT | não aplicável | PF/opcional | sim | IMPLEMENTED_DATA |
| ISUF | `suprama` | PersonFiscalData | `clientes.suprama` | USER_INPUT | `dest/ISUF` | condicional | sim | IMPLEMENTED_DATA |
| IM | `im` | PersonFiscalData | `clientes.im` | USER_INPUT | `dest/IM` | condicional | sim | IMPLEMENTED_DATA |
| E-mail | `email` | Repository | `clientes.email` | USER_INPUT | `dest/email` | opcional | sim | IMPLEMENTED_DATA |
| Telefones | `fone_principal`,`fone_2`,`fone_3` | Repository | colunas homônimas | USER_INPUT | `enderDest/fone` usa principal | opcional | sim | IMPLEMENTED_DATA |
| Endereço | `logradouro`,`numero`,`complemento`,`bairro` | Repository | colunas homônimas | USER_INPUT | `enderDest/xLgr,nro,xCpl,xBairro` | conforme operação | sim | IMPLEMENTED_DATA |
| Município/IBGE | `municipio`,`codigo_ibge` | Repository/PersonFiscalData | colunas homônimas | USER_INPUT | `enderDest/xMun,cMun` | nacional | sim | IMPLEMENTED_DATA |
| UF/CEP | `estado`,`cep` | Repository | `clientes.uf`,`cep` | USER_INPUT | `enderDest/UF,CEP` | nacional | sim | IMPLEMENTED_DATA |
| País | `country_name`,`country_code` | PersonFiscalData | colunas homônimas | USER_INPUT/CONFIGURATION | `enderDest/xPais,cPais` | sempre | sim | IMPLEMENTED_DATA |
| Papéis | `tipo_pessoa[]` | PersonFiscalData | `role_customer/supplier/seller/carrier` | USER_INPUT | não aplicável | um ou mais | sim | IMPLEMENTED_DATA |
| Contato/observações | `nome_contato`,`observations` | Repository/PersonFiscalData | colunas homônimas | USER_INPUT | não aplicável | opcional | sim | IMPLEMENTED_DATA |
| Nascimento/gênero/data | campos homônimos | Repository | colunas homônimas | USER_INPUT/DERIVED | não aplicável | opcional | sim | EXISTING_ERP |

## Campos visíveis legados preservados

Estes campos não compõem o núcleo fiscal de destinatário, mas continuam no mesmo cadastro e no mesmo round-trip legado. A tarefa não os remove nem muda sua semântica:

| Área | `name` visíveis | Backend / persistência | Status |
|---|---|---|---|
| Compatibilidade de pessoa | `pessoa_fisica`, `tipo_pessoa[]`, `transportadora` | Repository / `clientes.pessoa_fisica`, flags `role_*` | EXISTING_ERP; `person_type` é a identidade fiscal canônica |
| Situação | `status`, `status_pagamento` | Repository / colunas homônimas | EXISTING_ERP |
| Datas e perfil | `aniversario`, `genero` | Repository / colunas homônimas | EXISTING_ERP |
| Comercial | `vendedor`, `forma_pagamento`, `pagamento`, `desconto`, `comissao_percentual`, `comissao_volume` | Repository / colunas homônimas | EXISTING_ERP |
| Crédito | `limite_credito` | Repository / coluna homônima | EXISTING_ERP |
| Frete e veículo | `frete`, `valor_frete`, `antt`, `placa`, `placa_uf` | Repository / colunas homônimas | EXISTING_ERP |
| Regulação | `anvisa_codigo`, `anvisa_data_venc` | Repository / colunas homônimas | EXISTING_ERP |
| Outros dados legados | `funeral`, `ponto_referencia` | Repository / colunas homônimas | EXISTING_ERP |

Os controles internos `action`, `csrf_token` e `id` não são dados fiscais. `id` é definido pelo backend e nenhum identificador de tenant ou nome de banco é aceito pelo formulário.

Saída selecionará `clientes.id`; os dados fiscais atuais serão copiados para snapshot futuro. Alterações posteriores não podem reescrever documento autorizado. Fornecedor e transportadora poderão selecionar pessoas pelos respectivos papéis nas tasks de Entrada/Transporte.
