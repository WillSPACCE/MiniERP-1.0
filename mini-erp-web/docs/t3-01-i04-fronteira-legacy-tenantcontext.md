# T3-01-I04 — Fronteira entre estado legado e futuro TenantContext

## Objetivo

Definir a fronteira responsável por transformar o estado legado atual em dados explícitos, consistentes e validados suficientes para construir o futuro `TenantContext`, sem implementar código nem alterar produção.

Esta task responde, em desenho conceitual, a seguinte questão:

quem transforma o estado legado em contexto de execução válido?

O objetivo não é criar abstrações em excesso. O objetivo é identificar a menor separação de responsabilidades necessária para:

- consumir o legado sem espalhar `$_SESSION` e `$_POST` por Services e Repositories;
- normalizar entradas legadas;
- validar identidade, autorização e tenant;
- compor `TenantContext` de forma segura;
- manter a infraestrutura separada do estado de execução.

## Base documental obrigatória

- [mini-erp-web/docs/t3-01-i01-fluxo-real-login-empresa-tenant.md](t3-01-i01-fluxo-real-login-empresa-tenant.md)
- [mini-erp-web/docs/t3-01-i02-regra-operacional-tenant.md](t3-01-i02-regra-operacional-tenant.md)
- [mini-erp-web/docs/t3-01-i03-especificacao-tenantcontext.md](t3-01-i03-especificacao-tenantcontext.md)
- [mini-erp-web/docs/f0-t01-estado-atual-mini-erp.md](f0-t01-estado-atual-mini-erp.md)
- [mini-erp-web/docs/roadmap-projeto.md](roadmap-projeto.md)
- [mini-erp-web/public/index.php](../public/index.php)
- [mini-erp-web/public/login.php](../public/login.php)
- [mini-erp-web/app/Repository.php](../app/Repository.php)
- [mini-erp-web/app/Database.php](../app/Database.php)
- [mini-erp-web/config.php](../config.php)

## Restrições desta task

Esta task é de investigação + desenho documental.

Não criar:

- código novo;
- classe `TenantContext`;
- Adapter concreto;
- Resolver concreto;
- Factory concreto;
- interface nova;
- alteração em `public/index.php`;
- alteração em `public/login.php`;
- alteração em `app/Repository.php`;
- alteração em `app/Database.php`;
- alteração em `config.php`;
- alteração em banco;
- alteração em schema;
- execução de migrations;
- execução de seeds;
- correção de login;
- correção de criação de usuário;
- alteração da sessão;
- remoção de fallback;
- execução de tasks posteriores.

## Direção arquitetural confirmada

A arquitetura nova deve seguir esta direção:

LEGADO / HTTP / SESSÃO
→ fronteira de compatibilidade
→ validação/resolução
→ TenantContext
→ caso de uso novo

e nunca:

Service
→ `$_SESSION`

ou:

TenantContext
→ `$_SESSION`

O objetivo é a separação entre:

- estado legado do sistema;
- regras de autenticação já resolvidas;
- decisão de tenant;
- execução do caso de uso.

## Conclusão-base da T3-01

A T3-01 já decidiu que:

- `TenantContext` é transitório por request/operação;
- é imutável depois de validado;
- representa identidade autenticada e `EffectiveTenant` autorizado;
- não lê `$_SESSION` diretamente;
- não acessa PDO;
- não conhece `db_name`;
- não conhece DSN;
- não contém `Database`;
- não autentica usuário;
- não deve ter estado global/static;
- não deve ser reutilizado entre requests.

## Definição da fronteira

A fronteira entre legado e futuro contexto não deve ser uma camada de negócio. Ela deve ser uma camada de compatibilidade e normalização.

Sua responsabilidade principal é:

1. ler o estado legad o de forma controlada;
2. normalizar entradas do legado;
3. resolver identidade autenticada;
4. resolver tenant natural do usuário;
5. avaliar seleção explícita administrativa;
6. validar inconsistências;
7. compor um `TenantContext` validado para o caso de uso.

Essa fronteira não deve ser o “núcleo da regra de negócio”. Ela é uma borda de entrada.

## Separação mínima recomendada

A menor separação de responsabilidade útil parece ser esta:

- `LegacyContextAdapter` (ou equivalente): lê o legado e produz inputs normalizados;
- `TenantContextResolver` (ou equivalente): combina identidade, tenant do usuário, seleção, autorização e produz `TenantContext` válido.

Não é necessário criar cinco camadas diferentes para isso.

### O que não é necessário nesta fase

- múltiplas factories;
- múltiplos adapters por tipo de entrada;
- camada específica para cada campo de sessão;
- abstrações extras para `selectedTenant` se a resolução puder ser simples e explícita;
- camada que grava `db_name` no contexto.

Essas ideias podem ser úteis depois, mas ainda não são necessárias para a fronteira mínima.

Status:

NÃO NECESSÁRIA NESTA FASE

## Responsabilidade por entrada

### 1. Quem lê `$_SESSION`

A leitura da sessão deve ser feita exclusivamente pela fronteira de compatibilidade/adapter legado.

Responsável conceitual:

- `LegacyContextAdapter`
- ou componente equivalente de adaptação de estado legado

Não deve ser lido por:

- Service;
- Repository;
- `TenantContext`;
- Infrastructure;
- caso de uso novo sem adaptação explícita.

### 2. Quem lê parâmetros de seleção vindos de GET/POST/slug

Parâmetros vindos de query string, POST, URL slug e outros inputs de UI devem ser lidos e normalizados pela fronteira do legado.

Responsável conceitual:

- `LegacyContextAdapter`
- `TenantContextResolver` em conjunto, quando a decisão depende de validação

Regra:

- `$_POST` e `$_GET` são entradas de origem do cliente;
- slug bruto não é fonte de verdade;
- qualquer valor vindo de input do cliente deve ser considerado não confiável até validação.

### 3. Quem resolve `AuthenticatedUser`

A resolução de `AuthenticatedUser` deve ocorrer na fronteira de autenticação/compatibilidade, a partir do estado já autenticado do legado.

Responsável conceitual:

- `AuthenticatedUserResolver`
- ou a parte do `LegacyContextAdapter` que lê a sessão autenticada e a identifica como usuário válido

O componente pode consumir:

- `$_SESSION['user_id']` já autenticado;
- dados de login já reconhecidos;
- dados persistidos do usuário.

Mas ele não deve decidir o tenant da operação sozinho.

### 4. Quem resolve `UserTenant`

`UserTenant` deve ser resolvido pela mesma fronteira, com dados persistidos e validados.

Responsável conceitual:

- `TenantContextResolver`
- ou adapter de persistência/compatibilidade, de acordo com o primeiro caso de uso real

Regra:

- o tenant natural do usuário deve vir de dado persistido, não da sessão,
- `company_id` deve ser traduzido para `tenant_id` em uma etapa explícita,
- `$_SESSION['tenant_id']` deve ser tratado como entrada legada e comparada, não como fonte canônica.

### 5. Quem recebe `SelectedTenant`

A entrada de `SelectedTenant` deve ser recebida e validada pela fronteira de autorização e resolução.

Responsável conceitual:

- `TenantContextResolver`

Regra:

- `SelectedTenant` não vira `EffectiveTenant` automaticamente;
- o valor só é válido após autorização e existência do tenant;
- o admin global deve ter `SelectedTenant` explicitamente validado.

### 6. Onde `company_id` é traduzido para `tenant_id`

A tradução deve ocorrer na borda de compatibilidade, antes do caso de uso novo.

Ponto conceitual:

`company_id` legado
→ validação/lookup
→ `tenant_id` canônico
→ `TenantContext`

Não deve acontecer em:

- Service;
- Repository;
- Infrastructure;
- código novo de domain/business.

Essa tradução é uma fronteira de compatibilidade e não deve ser espalhada pelo sistema.

### 7. Onde os valores legados deixam de existir como conceitos do código novo

Os valores abaixo devem deixar de ser conhecidos pelo código novo logo após a normalização/validação da fronteira:

- `$_SESSION`;
- `$_POST`;
- `$_GET`;
- `company_id`;
- `current_company_id`;
- slug bruto;
- `Database::$tenantDbName`;
- `db_name` de infraestrutura.

Depois da fronteira, o código novo deve operar apenas com:

- `authenticatedUserId`;
- `userTenantId` (quando aplicável);
- `selectedTenantId` (quando aplicável);
- `effectiveTenantId`;
- flags de autorização mínima;
- metadados sem poder de decisão.

### 8. Quem valida inconsistências antes da construção do TenantContext

A validação deve ficar na camada de resolução, que combina:

- identidade autenticada;
- tenant natural do usuário;
- seleção administrativa;
- autorização do usuário;
- existência do tenant;
- consistência com sessão legada e banco atual.

Responsável conceitual:

- `TenantContextResolver`

Essa camada deve rejeitar qualquer inconsistência antes de compor o contexto.

### 9. Quem constrói o `TenantContext`

O `TenantContext` deve ser construído por um componente de resolução/normalização, e não pelos Services nem pelos Repositories.

Responsável conceitual:

- `TenantContextResolver` (menor item útil)

Ele recebe entradas normalizadas da borda e produz um `TenantContext` validado.

### 10. O que acontece quando a composição falha

Quando a fronteira encontrar inconsistência, ela deve interromper a composição e produzir uma falha de contexto antes do caso de uso começar.

Isso significa:

- sem entrar em Repository;
- sem abrir conexão;
- sem executar regra de negócio;
- sem usar `Database::$tenantDbName` como fonte de verificação;
- sem assumir o último `tenant_id` da sessão como válido.

## Entradas permitidas na fronteira

### Tabela de entradas legadas

| Entrada legado | Significado | Confiável? | Validação necessária | Saída normalizada |
|---|---|---:|---:|---|
| `$_SESSION['user_id']` | identidade autenticada já resolvida | parcial | Sim | `authenticatedUserId` |
| `$_SESSION['tenant_id']` | tenant da sessão legada | não confiável como fonte canônica | Sim | comparação/validação; não vira `effectiveTenantId` diretamente |
| `$_SESSION['current_company_id']` | compatibilidade legada | não confiável | Sim | `companyIdLegacy` somente em adapter; traduzido para `tenant_id` |
| `$_POST['tenant_id']` | seleção de cliente | não confiável | Sim | `selectedTenantId` somente se autorizado |
| `$_GET['tenant_id']` | seleção de cliente | não confiável | Sim | `selectedTenantId` somente se autorizado |
| slug bruto | identificador de tenant por URL | não confiável | Sim | `tenant_id` validado após lookup |
| `company_id` persistido/legado | empresa associada em dados legados | parcialmente confiável apenas se validado | Sim | `tenant_id` canônico |
| seleção administrativa explícita | tenant alvo da operação | dependente da autorização | Sim | `selectedTenantId` |
| tenant persistido do usuário | source de verdade do usuário | confiável quando persistido e validado | Sim | `userTenantId` |

## Entradas que devem ser tratadas como não confiáveis até validação

- `$_SESSION['tenant_id']`
- `$_SESSION['current_company_id']`
- `$_POST['tenant_id']`
- `$_GET['tenant_id']`
- slug bruto
- `company_id` do legado
- qualquer valor de tenant vindo do cliente ou do estado global

Essas entradas podem servir como clues de contexto, mas não são fonte de verdade.

## Entradas proibidas como fonte de verdade

- `Database::$tenantDbName`
- DSN
- `db_name`
- `PDO`/conexão
- `$_SESSION` como regra executiva
- `company_id` sem tradução
- qualquer valor bruto da URL/POST/GET sem validação

## Saída da fronteira

A saída da fronteira deve ser uma estrutura explicitamente normalizada, sem objetos de runtime legado e sem globais.

Conceitualmente:

Legacy state
→ normalized context input
→ `TenantContext`

### Estrutura mínima recomendada da saída

A saída da fronteira deve permitir construir o `TenantContext` sem carregar HTTP ou infraestrutura, e idealmente conter apenas:

- `authenticatedUserId`;
- `userTenantId` (quando houver);
- `selectedTenantId` (quando houver);
- `effectiveTenantId` (quando houver);
- flags ou metadados de autorização mínima;
- status de validação.

### DTO/intermediário?

A existência de um DTO/intermediário de entrada pode ser útil, mas não é obrigatória. A solução de menor risco e menor complexidade é:

- a fronteira pode produzir diretamente um objeto de contexto validado;
- ou, se a necessidade surgir, um DTO normalizado pode ser usado como ponte.

No desenho atual, a recomendação é:

- evitar DTO extra se o resolver já puder receber os valores normalizados;
- marcar `DTO intermediário` como `NÃO NECESSÁRIO NESTA FASE`.

## Fluxo de usuário comum

### Fluxo conceitual

1. a sessão informa `user_id` autenticado;
2. a fronteira resolve o usuário persistido;
3. a fronteira resolve `UserTenant` a partir de dados persistidos/compatibilidade;
4. a sessão legada e os campos de compatibilidade são comparados com o usuário persistido;
5. se houver divergência sem autorização explícita, falha;
6. `EffectiveTenant` é definido como `UserTenant`;
7. `TenantContext` é criado com `authenticatedUserId`, `userTenantId`, `effectiveTenantId` e sem sessão;
8. caso de uso novo usa o contexto validado.

### Regras de usuário comum

- `selectedTenantId` deve ser nulo ou igual ao `userTenantId`;
- `effectiveTenantId` deve ser igual ao `userTenantId`;
- qualquer divergência entre sessão e usuário persistido deve falhar antes do caso de uso;
- a sessão não pode sobrescrever silenciosamente o tenant persistido do usuário.

## Fluxo de admin global

### Fluxo conceitual

1. a sessão identifica o admin autenticado;
2. a fronteira verifica qual é a identidade do usuário;
3. a operação lê uma seleção administrativa do tenant alvo;
4. a fronteira verifica se o tenant alvo existe;
5. a fronteira verifica se a autorização do admin para esse tenant está comprovada;
6. se autorizado, `SelectedTenant` vira `EffectiveTenant`;
7. `TenantContext` é criado;
8. o caso de uso usa o contexto tenant-scoped.

### Regra importante

A autorização global continua sendo:

DECISÃO PENDENTE DE AUTORIZAÇÃO

Não inventar regra “admin pode tudo”.

## Fluxo conceitual de criação de usuário por empresa

### Problema real legado

Hoje o legado aproximadamente:

admin
→ troca temporariamente `$_SESSION['tenant_id']`
→ associa usuário
→ restaura sessão

Esse padrão é incompatível com a arquitetura nova.

### Fluxo desejado

- `AuthenticatedAdmin` identifica quem está operando;
- `TargetTenant` é informado explicitamente pela ação/caso de uso;
- a fronteira valida a existência do tenant e a autorização do admin;
- `TargetTenant` pode ser usado como parâmetro do caso de uso;
- o caso de uso cria o usuário no tenant alvo, sem mutar sessão global.

### Distinção importante

`tenant do administrador`
≠
`tenant alvo do usuário sendo criado`

### Recomendação de design

A solução com menor risco de confusão é:

- `TenantContext` para contexto autorizado da operação atual;
- `targetTenantId` como parâmetro específico do caso de uso quando necessário;
- não misturar `TargetTenant` ao núcleo do contexto permanente.

### Decisão de classificação

- `targetTenantId`: parâmetro do caso de uso, específico e transitório;
- `effectiveTenantId`: tenant que governa a operação atual;
- `tenant do admin`: identidade/autorização do usuário autenticado;

## `company_id` e a fronteira de compatibilidade

### Regra

`company_id` legado
→ validação e tradução explícita
→ `tenant_id` canônico

Depois dessa etapa, o código novo não deve conhecer `company_id`.

### Onde isso deve acontecer

Na fronteira de compatibilidade, antes da produção do contexto de execução.

Não deve acontecer:

- em Service;
- em Repository;
- em caso de uso novo sem boundary adapter;
- em banco.

### Como persistir sem alterar schema

A fronteira pode consumir `company_id` para realizar a tradução, mas depois disso torna o valor legado irrelevante para o código novo.

## Slug

### Regra

`slug bruto`
→ lookup confiável do tenant
→ `tenant_id` validado

A partir desse ponto:

- slug não entra no `TenantContext`;
- slug não é usado para definir banco;
- slug não deve sobreviver ao processo de normalização.

## Banco e infraestrutura

A borda não deve escolher `db_name` para serviços.

A estrutura correta é:

`TenantContext.effectiveTenantId`
→ `Infrastructure`/`TenantConnectionResolver`
→ `db_name`
→ conexão

### Proibido

- `Database::$tenantDbName` como fonte de `EffectiveTenant`;
- nome do banco na estrutura de runtime da operação;
- `db_name` sendo trocado dentro do contexto operacional.

## Falhas previstas na fronteira

Categorias conceituais de falha:

- sessão sem usuário;
- usuário inexistente;
- usuário inativo quando aplicável;
- tenant persistido inexistente;
- `company_id` incompatível;
- seleção administrativa inválida;
- tenant selecionado inexistente;
- autorização não comprovada;
- sessão divergente do `UserTenant`;
- múltiplas fontes conflitantes;
- ausência de tenant em operação tenant-scoped.

Essas falhas devem abortar a composição do contexto antes do caso de uso.

## Compatibilidade e núcleo novo

A fronteira pode consumir o legado, mas o núcleo novo não deve depender do legado.

Direção permitida:

Legacy Adapter
→ novo contrato/contexto

Evitar:

Service novo
→ `Repository.php` legado
→ `$_SESSION`

quando a dependência puder ser isolada na borda.

## Responsabilidade da fronteira conceitual

| Responsabilidade | Camada/componente conceitual | Pode conhecer legado? | Pode conhecer TenantContext? |
|---|---|---:|---:|
| ler sessão e inputs HTTP | LegacyContextAdapter | Sim | Não |
| resolver identidade autenticada | AuthenticatedUserResolver | Sim | Não |
| resolver tenant natural do usuário | TenantContextResolver / compat layer | Sim | Sim, como entrada de composição |
| validar autorização | TenantContextResolver | Sim | Sim |
| validar `SelectedTenant` | TenantContextResolver | Sim | Sim |
| traduzir `company_id` para `tenant_id` | Legacy compatibility boundary | Sim | Não como valor legado |
| compor `TenantContext` | TenantContextResolver | Sim, apenas como entrada | Sim, é o resultado |
| resolver banco a partir do tenant | Infrastructure / TenantConnectionResolver | Não como regra de negócio | Sim, apenas `effectiveTenantId` |
| executar caso de uso | Service | Não no núcleo | Sim |

## Fluxo final conceitual

HTTP/LEGADO
→ `LegacyContextAdapter`
→ leitura e normalização
→ validação de identidade e seleção
→ `TenantContextResolver`
→ `TenantContext`
→ `Service`
→ `Repository Contract`
→ `Infrastructure`

## Abstrações mínimas recomendadas

A solução mais simples e segura parece ser:

1. `LegacyContextAdapter` para consumir estado legado e produzir dados normalizados;
2. `TenantContextResolver` para validar e construir o contexto;
3. `Infrastructure`/`TenantConnectionResolver` para resolver o banco a partir de `effectiveTenantId`.

### Sem necessidade comprovada

- `TenantContextFactory` separada;
- `LegacySessionAdapter` dedicado apenas à sessão;
- `AuthenticatedUserResolver` separada se isso for apenas uma função interna do adapter/resolver;
- múltiplos adapters para cada entrada de `$_SESSION`, `$_GET`, `$_POST` e slug.

Se duas abstrações forem suficientes, não criar mais.

## A fronteira de compatibilidade não deve conhecer detalhe de banco

A fronteira deve apenas resolver o tenant válido na operação. O `db_name` e a conexão pertencem à infraestrutura.

Isso reduz o risco de:

- `Service` conhecer `Database`;
- `TenantContext` conhecer `Database`;
- `db_name` entrar em regras de negócio;
- `Database::$tenantDbName` tornar-se fonte de verdade de tenant.

## Conclusão

A fronteira ideal é uma borda explícita de compatibilidade e validação, e não uma camada de negócio ou um espelho do legado.

Ela deve:

- consumir o estado legado;
- transformar entradas de sessão/input em valores normalizados;
- decidir identidade e tenant validado;
- construir o `TenantContext` somente quando a operação estiver consistente;
- devolver ao novo código um contexto mínimo, imutável e seguro.

Qualquer comportamento fora desse fluxo caracteriza acoplamento legado e deve ser rejeitado.

## Arquivos criados

- [mini-erp-web/docs/t3-01-i04-fronteira-legacy-tenantcontext.md](t3-01-i04-fronteira-legacy-tenantcontext.md)

## Arquivos modificados

- Nenhum arquivo de produção foi alterado.
- Nenhum arquivo em [mini-erp-web/public](../public), [mini-erp-web/app](../app), [mini-erp-web/config.php](../config.php), [mini-erp-web/database](../database) foi modificado.

## Confirmação final

- nenhum código foi alterado;
- nenhum banco foi alterado;
- nenhuma migration foi executada;
- nenhuma seed foi executada;
- nenhum arquivo de produção foi modificado;
- nenhuma task posterior foi executada.

A task termina na documentação da fronteira legado → `TenantContext`.
