# ERP-CRUD-01 — Clientes no ERP legado

## Diagnóstico

O formulário histórico em `public/index.php`, aba `Cadastro → Pessoas`, envia POST com `action=save_cliente`; edição inclui `id` e exclusão envia `action=delete_cliente`. O mesmo controller chama `Repository::saveCliente()`, `listClientes()`, `findCliente()` e `deleteCliente()`.

O banco `mini_erp_tenant_14` é fisicamente isolado e sua tabela `clientes` não possui `tenant_id`. Antes desta task, listagem capturava a falha e fazia fallback sem filtro, enquanto busca individual, INSERT, UPDATE e DELETE sempre referenciavam `tenant_id`. Além disso, `requireTenantId()` podia devolver tenant 1 para usuários com role `admin`. O formulário usa `fone_principal`, mas o schema também mantém `telefone`.

## Arquitetura

O login e `ErpLegacyBootstrap` continuam responsáveis por entregar o PDO resolvido pelo `TenantContext`. O CRUD nunca abre MAIN, nunca troca banco, não chama `Database::setTenantDbName()` e ignora qualquer tenant/database vindo do formulário.

No limite legado, `requireTenantId()` aceita uma sessão ERP apenas se `erp_user_id=user_id` e `erp_tenant_id=tenant_id`. Para bancos dedicados sem coluna `tenant_id`, o isolamento é o próprio PDO físico. Para schemas antigos que ainda possuam a coluna, as queries adicionam o predicado tenant-scoped.

## Campos do formulário existente

`nome`, `nome_fantasia`, `tipo_pessoa[]`, `pessoa_fisica`, `cpf_cnpj`, `aniversario`, `genero`, `nome_contato`, `email`, `fone_principal`, `fone_2`, `fone_3`, `status`, `cep`, `logradouro`, `numero`, `bairro`, `cidade`, `estado`, `complemento`, `ponto_referencia`, `codigo_ibge`, `suprama`, `im`, `vendedor`, `status_pagamento`, `pagamento`, `anvisa_data_venc`, `anvisa_codigo`, `comissao_percentual`, `comissao_volume`, `forma_pagamento`, `limite_credito`, `desconto`, `funeral`, `transportadora`, `placa`, `placa_uf`, `antt`, `frete` e `valor_frete`.

Somente colunas realmente existentes no schema são incluídas no SQL. `telefone` e `fone_principal` são sincronizados explicitamente no backend, de modo que o formulário atual funcione sem alteração visual e schemas mínimos preservem o telefone na coluna disponível.

## Operações

- INSERT: campos existentes em `clientes`, no PDO dedicado.
- SELECT/listagem: banco dedicado; filtro opcional por nome, fantasia, documento ou e-mail.
- SELECT individual: `id` no banco dedicado, acrescentando `tenant_id` somente se a coluna existir.
- UPDATE: mesmo conjunto dinâmico de campos e escopo físico/canônico.
- DELETE: `id` no banco dedicado e tenant predicate quando aplicável.

Salvar e excluir usam CSRF e POST/Redirect/GET. Em erro de validação, o payload é reapresentado no formulário. Nome, CPF/CNPJ válido, CEP, logradouro e telefone principal seguem as obrigatoriedades já expressas pelo formulário completo.

## Prova real

`ErpClientCrudIntegrationTest.php` resolve tenant 14, confirma `DATABASE()`, executa INSERT, SELECT, UPDATE, SELECT, DELETE e SELECT, testa busca, telefone, validações, sessão adulterada e ausência no tenant 5. O registro usa marcador aleatório e é removido; há cleanup adicional em `finally`.

## Limitações

Filtros avançados, exportação PDF/Excel/e-mail e validação dos demais CRUDs não pertencem a esta task. O `Repository` monolítico permanece dívida técnica.

## Reconciliação Fiscal-First

ERP-CRUD-01 comprova persistência e isolamento, mas não declara Pessoa fiscalmente completa. Toda evolução deve atualizar `docs/fiscal/NFE-XML-DATA-MAP.md` e provar campo XML, persistência, round-trip, uso na operação e snapshot. Gaps principais: CNPJ alfanumérico, `idEstrangeiro`, `indIEDest`, país e normalização canônica de município/UF/IE.
