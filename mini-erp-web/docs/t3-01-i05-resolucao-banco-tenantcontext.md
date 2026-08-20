# T3-01-I05 — Resolução segura da conexão/banco a partir do TenantContext

## Objetivo

Especificar a menor abstração necessária para que a infraestrutura resolva com segurança o banco dedicado do tenant correspondente ao `EffectiveTenant`, sem implementar código nem alterar produção.

O objetivo desta task é documentar a regra conceitual de:

`TenantContext.effectiveTenantId`
→ resolução confiável do tenant
→ `db_name`
→ conexão

e nunca:

`db_name atual`
→ descobrir tenant

ou:

`Database::$tenantDbName`
→ decidir `EffectiveTenant`.

## Base documental obrigatória

- [mini-erp-web/docs/t3-01-i01-fluxo-real-login-empresa-tenant.md](t3-01-i01-fluxo-real-login-empresa-tenant.md)
- [mini-erp-web/docs/t3-01-i02-regra-operacional-tenant.md](t3-01-i02-regra-operacional-tenant.md)
- [mini-erp-web/docs/t3-01-i03-especificacao-tenantcontext.md](t3-01-i03-especificacao-tenantcontext.md)
- [mini-erp-web/docs/t3-01-i04-fronteira-legacy-tenantcontext.md](t3-01-i04-fronteira-legacy-tenantcontext.md)
- [mini-erp-web/docs/f0-t01-estado-atual-mini-erp.md](f0-t01-estado-atual-mini-erp.md)
- [mini-erp-web/docs/roadmap-projeto.md](roadmap-projeto.md)
- [mini-erp-web/app/Database.php](../app/Database.php)
- [mini-erp-web/app/Repository.php](../app/Repository.php)
- [mini-erp-web/config.php](../config.php)
- [mini-erp-web/public/index.php](../public/index.php) (apenas quando necessário para confirmar comportamento legado)

## Restrições desta task

Esta task é de investigação + desenho documental.

Não criar:

- classe `TenantConnectionResolver` concreta;
- connection resolver real;
- repository novo;
- interface nova;
- alterar `Database.php`;
- alterar `Repository.php`;
- alterar `index.php`;
- alterar `login.php`;
- alterar `config.php`;
- alterar banco;
- alterar schema;
- executar migrations;
- executar seeds;
- executar `initializeSchema()` manualmente;
- corrigir criação de usuário;
- alterar sessão;
- executar task posterior.

## Contexto arquitetural já decidido

A arquitetura nova deve seguir:

HTTP/LEGADO
→ LegacyContextAdapter
→ TenantContextResolver
→ TenantContext
→ Service
→ Repository Contract
→ Repository
→ Infrastructure
→ banco do tenant

O `TenantContext` contém o `EffectiveTenant` autorizado.

O `TenantContext` não contém:

- `db_name`;
- DSN;
- PDO;
- credenciais;
- `Database`;
- sessão;
- request.

O banco deve ser consequência do `EffectiveTenant`.

Portanto:

`TenantContext.effectiveTenantId`
→ resolução confiável do tenant
→ `db_name`
→ conexão

e nunca:

`db_name atual`
→ descobrir tenant

ou:

`Database::$tenantDbName`
→ decidir `EffectiveTenant`.

## Comportamento legado observado

### 1. `Database::setTenantDbName()`

No legado, `Database::setTenantDbName()`:

- recebe o nome do banco do tenant;
- grava em `self::$tenantDbName`;
- redefine `self::$connection = null`;
- provoca a próxima chamada de `Database::getConnection()` a abrir nova conexão no banco especificado.

Isso representa estado global mutable em memória.

### 2. `Database::$tenantDbName`

Este valor funciona como referência global de banco ativo.

O problema é que ele mantém estado em objeto estático e faz o sistema parecer que o banco atual “define” o tenant atual. Isso é perigoso porque o banco não deve decidir o tenant; o tenant deve decidir o banco.

### 3. `Database::$connection`

A conexão é reaproveitada quando já aberta.

Isso significa:

- uma conexão pode sobreviver além do tenant correto;
- a troca de tenant pode ser feita pela mutação do estado global;
- a próxima operação do sistema pode reutilizar a conexão correta ou incorreta dependendo do estado ativo atual.

### 4. `Database::getConnection()`

A criação da conexão:

- lê config;
- monta DSN;
- cria PDO;
- se necessário, chama `initializeSchema()`;
- reusa conexão estática.

Esse mecanismo indica que o banco e a conexão são tratados como estado global compartilhado.

### 5. `config.php`

O arquivo de configuração fornece o banco principal/default da aplicação e também o ponto de entrada para criação do DSN.

No desenho atual, esse arquivo serve ao sistema legado e não deve tornar-se fonte de verdade de tenant para o código novo.

### 6. `Repository.php`

`Repository.php` consulta a tabela `tenants` e usa o estado atual para:

- verificar existência do tenant;
- comparar usuário e tenant;
- eventualmente resolver `db_name` em uso ou em compatibilidade;
- fazer validações de acesso e compatibilidade em estratégias legadas.

Isso confirma que a tabela de tenant e seu identificador confiável são o ponto correto para a infraestrutura transformar `tenant` em banco, e não o estado estático da conexão.

### 7. `initializeSchema()`

A inicialização de schema e seeds acontece durante a abertura de conexão. Isso é um forte indício de mistura entre:

- bootstrap;
- schema;
- seed;
- operação de tenant;
- inicialização de infraestrutura.

A arquitetura nova deve separar isso claramente.

## Riscos do estado global/static entre tenants

Os riscos confirmados são:

- `Database::$tenantDbName` atua como estado global compartilhado;
- `Database::$connection` é estática e reutilizada;
- `getConnection()` pode ignorar o tenant corrente se o estado anterior tiver sido mutado;
- fluxo de tenant atual pode ser sobrescrito por outro fluxo sem contexto explícito;
- o banco pode ser tratado como origem de verdade em vez de consequência;
- o mesmo processo pode reutilizar conexão de tenant errado;
- o isolamento entre tenants fica frágil em ambiente de múltiplas requisições ou execução compartilhada.

## Banco principal/control-plane versus banco do tenant/data-plane

### Banco principal / control-plane

Responsável por informações globais necessárias para a resolução do tenant, como:

- tabela `tenants`;
- usuários globais quando aplicável;
- associação necessária para identificar um tenant;
- `db_name` do tenant;
- metadados de controle de empresa / tenant.

### Banco do tenant / data-plane

Responsável pelos dados exclusivos daquela empresa, como:

- produtos;
- clientes;
- fornecedores;
- funcionários quando aplicável ao modelo real;
- vendas;
- transportadoras;
- motoristas;
- configurações específicas da empresa.

### Quando a localização de tabela não está comprovada

Se uma tabela ou modelo não estiver comprovado pela análise do baseline, deve ser marcado como:

NÃO CONFIRMADO

## Regra de resolução conceitual

A resolução segura deve seguir esta ordem:

1. `TenantContext` já foi validado e tem `effectiveTenantId`;
2. o sistema consulta o registro confiável de tenants no control-plane;
3. valida existência do tenant;
4. valida status do tenant quando aplicável;
5. obtém `db_name` de forma segura;
6. valida que o `db_name` corresponde ao tenant esperado;
7. constrói a conexão em infraestrutura;
8. entrega a conexão ao `Repository Implementation` ou ao seu adaptador de dados;
9. operação continua somente com o banco correto.

A regra básica é:

`effectiveTenantId` → tenant válido → `db_name` válido → conexão válida.

Não é permitido:

- usar `db_name` do request;
- usar `db_name` da sessão;
- usar `Database::$tenantDbName` anterior como autoridade;
- aceitar nome arbitrário de banco sem validação.

## Segurança

A resolução não deve aceitar diretamente como autoridade:

- `db_name` vindo de GET;
- `db_name` vindo de POST;
- `db_name` vindo da sessão;
- DSN vindo do request;
- tenant database escolhido pelo usuário;
- `Database::$tenantDbName` anterior;
- nome arbitrário de banco;
- qualquer valor externo que não tenha sido validado no control-plane.

O único ponto de partida para qualquer operação tenant-scoped deve ser o `EffectiveTenant` validado.

## Estado da conexão e prevenção de vazamento entre tenants

### Invariantes desejadas

Para a implementação futura, as invariantes devem ser:

1. uma conexão deve estar vinculada ao tenant para o qual foi construída;
2. troca de tenant não deve mutar silenciosamente uma conexão existente;
3. `Database::$tenantDbName` nunca deve servir como fonte de tenant;
4. conexão não deve determinar o tenant;
5. cache/reuso, se existir futuramente, deve ser indexado de forma segura por tenant/banco;
6. o mesmo processo não deve reutilizar conexão de tenant A para tenant B sem validação explícita;
7. operação de tenant B não deve usar conexão de tenant A;
8. qualquer inconsistência entre tenant e banco deve falhar antes do acesso a dados.

### Recomendação sem implementação

A preferência arquitetural é a seguinte:

- cada conexão tenant-scoped deve estar associada a um `effectiveTenantId` ou `db_name` validado;
- reutilização de conexão, quando existir, deve ser segura e indexada pelo par tenant/banco;
- sem cache em fase inicial;
- sem mutação do estado global em consonância com a regra de TenantContext imutável.

## Database legado e estratégia de convivência

### Situação atual

`Database.php` e `Repository.php` são o legado. Eles misturam:

- conexão;
- leitura de config;
- schema bootstrap;
- validação de tenant;
- regras de acesso;
- banco atual em static state.

### Recomendação de coexistência

A arquitetura nova deve preferir uma abordagem incremental:

A. encapsular temporariamente o `Database` legado;
B. criar infraestrutura nova para os novos casos de uso;
C. usar adaptação mínima durante a transição.

### Recomendação mais segura

A opção mais incremental e menos arriscada é:

- manter o legado funcionando sem alteração;
- isolar a resolução de tenant e conexão em infraestrutura específica para os novos casos de uso;
- usar adaptação mínima, não refatoração do `Database` legado;
- permitir que o legado continue sendo o sistema de compatibilidade enquanto a nova arquitetura evolui.

Status atual:

OPÇÃO MAIS INCREMENTAL: B + adaptação mínima, sem refatorar Database.php

## Bootstrap/schema em runtime

O diagnóstico confirmou que `Database::getConnection()` chama `initializeSchema()`.

Isso é um forte indicador de que o legado mistura infraestrutura de bootstrap com operação normal do sistema.

A arquitetura nova deve separar claramente:

- abrir conexão;
- executar schema;
- executar migration;
- executar seed.

Essas operações não devem ser reproduzidas pela nova infraestrutura de tenant resolution.

Regra:

`Database.php` permanece legado e intacto nesta task.

A infraestrutura nova deve resolver banco, não fazer bootstrap.

## Falhas previstas

Categorias conceituais para falha da resolução de banco:

- `EffectiveTenant` ausente;
- tenant inexistente;
- tenant bloqueado/inativo quando aplicável;
- `db_name` ausente;
- `db_name` inválido;
- banco inexistente;
- falha de conexão;
- inconsistência `tenant ↔ db_name`;
- tentativa de usar conexão de outro tenant;
- tentativa de fornecer `db_name` externamente;
- falha no lookup do control-plane.

Estas falhas devem impedir a operação antes do acesso a dados.

## Isolamento entre tenants

A regra de isolamento deve garantir que:

Tenant A
→ `effectiveTenantId = A`
→ `db_name = A`
→ conexão A

Tenant B
→ `effectiveTenantId = B`
→ `db_name = B`
→ conexão B

e que:

- A nunca reutilize conexão B;
- B nunca reutilize conexão A;
- a conexão nunca seja usada como fonte de verdade do tenant;
- `selectedTenantId` ou sessão não possam redefinir o banco silenciosamente.

## Relação com Repository

A relação conceitual correta deve ser:

Service
→ Repository Contract
→ Repository Implementation
→ TenantConnectionResolver
→ PDO correto

A decisão sobre o que o `Repository Implementation` recebe pode variar, mas o princípio é este:

- o repository deve receber algo do contexto ou da infraestrutura que o identifique sem depender diretamente de `$_SESSION`;
- o repository não deve receber `db_name` bruto ou `Database` global;
- o repository não deve decidir tenant pela sessão.

### Opções possíveis

1. `Repository` recebe `TenantContext` completo.
2. `Repository` recebe `effectiveTenantId` ou abstração mínima do contexto.
3. `Repository` recebe conexão já resolvida.

### Risco de cada opção

- Opção 1: mais explícito, mas potencialmente mais acoplado.
- Opção 2: mais simples e seguro, mas requer definição do contrato de domínio.
- Opção 3: reduz responsabilidade de domínio, mas aumenta acoplamento de infraestrutura.

### Decisão atual

A arquitetura nova deve preferir a menor abstração que preserve segurança e testabilidade, mas sem cair em implementação prematura.

Como a decisão depende do primeiro caso de uso concreto, segue:

DECISÃO PENDENTE DE IMPLEMENTAÇÃO

## Tabela de entradas e resultados

| Entrada | Fonte | Confiável? | Validação | Resultado |
|---|---|---:|---:|---|
| `effectiveTenantId` | `TenantContext` | Sim | validar existência e autorização | `tenant` válido |
| `tenant_id` do legado | sessão/compatibilidade | Não como fonte canônica | comparar com usuário persistido e com tenant válido | usar apenas como pista |
| `db_name` do request | GET/POST/URL | Não | rejeitar | falha de composição |
| `db_name` da sessão | legado | Não | rejeitar | falha de composição |
| `Database::$tenantDbName` | estado global | Não | rejeitar como autoridade | falha de composição |
| tenant do control-plane | tabela `tenants` | Sim | validar existência e status | `tenant` e `db_name` confiáveis |
| `db_name` resolvido | control-plane | Sim | validar compatibilidade com tenant | conexão tenant-scoped |

## Tabela de cenários

| Cenário | EffectiveTenant | `db_name` resolvido | Conexão esperada | Resultado |
|---|---|---|---|---|
| usuário comum no tenant 5 | 5 | banco 5 | conexão 5 | válido |
| usuário comum com sessão divergente | 5 | banco 3 | conexão 3 | rejeitar |
| admin global selecionando tenant 5 | 5 | banco 5 | conexão 5 | válido se autorizado |
| tenant inexistente | 999 | inexistente | nenhuma | rejeitar |
| banco inconsistente | 5 | banco 8 | conexão 8 | rejeitar |
| conexão de tenant anterior reaproveitada | 5 | banco 5 | conexão 5 | aceitar somente se corresponde ao tenant |
| tentativa de override por request | 5 | DB externo | conexão inválida | rejeitar |

## Fluxo final conceitual

TenantContext
→ `effectiveTenantId`
→ `TenantConnectionResolver`
→ lookup no control-plane
→ validação do tenant
→ `db_name`
→ conexão tenant-scoped
→ `Repository Implementation`

## Abstrações mínimas recomendadas

A solução mais simples, segura e compatível parece ser:

1. `TenantContext` já validado como entrada principal;
2. `TenantConnectionResolver` como componente de infraestrutura, responsável por entender `effectiveTenantId`, consultar control-plane, obter e validar `db_name`, e entregar a conexão correta;
3. `Repository Implementation` consumindo a conexão correta e sem depender de `$_SESSION` ou `Database` global.

### O que não é necessário nesta fase

- criar um cache global de conexão por request;
- mover `initializeSchema` para a infraestrutura nova;
- colocar `db_name` no contexto;
- criar camada extra de banco por cada case;
- gerenciar `Database::$tenantDbName` em código novo.

## Relação com Service e Repository

### Service

O Service não deve conhecer:

- `db_name`;
- DSN;
- PDO;
- credenciais;
- banco específico;
- `Database` legado.

Ele deve receber o contexto já validado e operar normalmente conforme o caso de uso.

### Repository

O Repository implementation deve receber apenas a informação necessária para acessar o tenant correto, sem depender do estado global.

O ponto central é:

- Service não decide banco;
- Repository não decide tenant pela sessão;
- Infrastructure resolve conexão pelo `effectiveTenantId` validado.

## Tratamento do bootstrap/schema

A arquitetura nova não deve reproduzir o padrão legado:

abrir conexão
≠
executar schema
≠
executar migration
≠
executar seed.

O legado continuará funcionando intacto. A task atual apenas documenta que este comportamento precisa ser endereçado separadamente em arquitetura futura.

## Decisões pendentes

- como formalizar o control-plane para os tenants no sistema legado;
- se a resolução de banco usa uma tabela/lookup único ou múltiplos pontos de leitura;
- como tratar `tenant_id`/`company_id` no control-plane durante a transição;
- se a infraestrutura terá um resolver separado ou um adapter mínimo para o `Database` legado;
- como formalizar a relação de `Repository` com a conexão já resolvida;
- como o admin global deve ser tratado quando a operação não é local a um tenant específico.

## Resumo executivo

A resolução segura do banco deve partir de um `TenantContext` já validado, nunca do estado global legado. O banco deve ser um efeito de `EffectiveTenant`, e não uma fonte de verdade do tenant.

A infraestrutura deve fazer o lookup do tenant confiável no control-plane, validar `db_name`, resolver a conexão correta e entregar isso ao Repository implementation. O `Service` e o `TenantContext` não devem conhecer banco nem sessão.

## Arquivos criados

- [mini-erp-web/docs/t3-01-i05-resolucao-banco-tenantcontext.md](t3-01-i05-resolucao-banco-tenantcontext.md)

## Arquivos modificados

- Nenhum arquivo de produção foi alterado.
- Nenhum arquivo em [mini-erp-web/public](../public), [mini-erp-web/app](../app), [mini-erp-web/config.php](../config.php), [mini-erp-web/database](../database) foi modificado.

## Confirmação final

- nenhum código foi alterado;
- nenhum banco foi alterado;
- nenhuma migration foi executada;
- nenhum seed foi executado;
- nenhum arquivo de produção foi modificado;
- nenhuma task posterior foi executada.

A task termina na documentação da resolução de banco a partir do `TenantContext`.
