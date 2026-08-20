# T3-01-I03 — Especificação da composição, invariantes e ciclo de vida do TenantContext

## Objetivo

Especificar o contrato conceitual do futuro `TenantContext` como elemento transitório de uma operação, sem implementar classe, interface, adapter ou qualquer alteração de código.

O objetivo desta task é descrever, com base no baseline e nas regras já decididas, exatamente:

1. quais dados o `TenantContext` contém;
2. quais dados ele não contém;
3. quais campos são obrigatórios;
4. quais campos são opcionais;
5. quem pode construí-lo;
6. quando ele é considerado válido;
7. quando sua construção deve falhar;
8. se ele pode ser alterado depois de criado;
9. como ele representa usuário comum;
10. como ele representa operação administrativa sobre outra empresa;
11. como ele será consumido por Services, Repositories e Infrastructure;
12. quais invariantes precisam ser verdadeiras antes de qualquer acesso a dados.

## Base documental obrigatória

- [mini-erp-web/docs/t3-01-i01-fluxo-real-login-empresa-tenant.md](t3-01-i01-fluxo-real-login-empresa-tenant.md)
- [mini-erp-web/docs/t3-01-i02-regra-operacional-tenant.md](t3-01-i02-regra-operacional-tenant.md)
- [mini-erp-web/docs/f0-t01-estado-atual-mini-erp.md](f0-t01-estado-atual-mini-erp.md)
- [mini-erp-web/docs/roadmap-projeto.md](roadmap-projeto.md)
- [mini-erp-web/public/index.php](../public/index.php)
- [mini-erp-web/public/login.php](../public/login.php)
- [mini-erp-web/app/Repository.php](../app/Repository.php)
- [mini-erp-web/app/Database.php](../app/Database.php)

## Restrições desta task

Esta task é exclusivamente de especificação e desenho.

Não criar:

- classe `TenantContext`;
- interface de contexto;
- adapter de infraestrutura;
- repository novo;
- alteração em código produtivo;
- alteração em `public/`;
- alteração em `app/`;
- alteração em `config.php`;
- alteração em banco;
- alteração em schema;
- execução de migration;
- execução de seeds;
- correção de login;
- correção de criação de usuário;
- alteração da sessã̃o;
- alteração do fallback tenant `1`;
- execução de qualquer task posterior.

## Premissas já decididas pela T3-01-I02

A regra operacional já definida estabelece que a arquitetura nova deve obedecer a esta sequência:

AuthenticatedUser
→ UserTenant
→ autorização
→ SelectedTenant quando aplicável
→ EffectiveTenant
→ resolução de infraestrutura
→ EffectiveDatabase

Também já foi decidido que:

- `tenant_id` é o identificador canônico da arquitetura nova;
- `company_id` é apenas compatibilidade legado;
- sessão não é fonte canônica de tenant;
- nome do banco não é fonte canônica de tenant;
- `EffectiveDatabase` é consequência do `EffectiveTenant`;
- usuário comum não pode trocar arbitrariamente de tenant;
- admin global pode possuir `SelectedTenant`, mas sua autorização final precisa ser formalizada;
- criação de usuário por empresa não deve depender de troca temporária de sessão;
- `TenantContext` deve representar o `EffectiveTenant` validado;
- `TenantContext` deve ser transitório por request;
- `TenantContext` não deve acessar banco;
- `TenantContext` não deve ler `$_SESSION` diretamente;
- `TenantContext` não deve autenticar usuário;
- `TenantContext` não deve abrir PDO;
- `TenantContext` não deve possuir estado global/static.

## Definição de propósito

`TenantContext` deve ser um objeto de decisão operacional, válido apenas para a operação em execução.

Ele não é:

- fonte de banco;
- história do usuário;
- estado global;
- cache de tenant;
- repositório de sessão;
- porta de entrada para HTTP;
- identidade de negócio completa.

Ele é:

- uma representação explícita, validada e imutável do tenant efetivo para a operação atual;
- um ponto de coerção entre o estado legado e a operação de negócio;
- um contrato que permite Services e Repositories trabalharem com um tenant resolvido e autorizado;
- um boundary object entre legacy boundary e business/infrastructure.

## Responsabilidades do TenantContext

### O que ele deve responder

- quem está autenticado no request atual;
- qual é o tenant efetivo da operação;
- se a operação está no escopo de um tenant específico;
- se a operação é de usuário comum ou de administração global;
- se o tenant foi validado explicitamente;
- se o contexto pode ser usado para acessar negócio/infrastructure daquela empresa.

### O que ele não deve fazer

- abrir conexão;
- decidir a string do DSN;
- consultar banco para resolver tenant;
- ler `$_SESSION` diretamente;
- depender de `$_POST`/`$_GET` como fonte de verdade;
- conhecer credenciais;
- chamar Repository;
- conhecer Service;
- carregar Request HTTP completo;
- manter estado compartilhado entre requests.

## Campos propostos e classificação

### 1. `authenticatedUserId`

- Classificação: obrigatório para operações protegidas.
- Origem: identidade autenticada já resolvida pelo fluxo legado.
- Motivo: o contexto só tem sentido se existir um usuário autenticado válido; sem isso não há base para determinar autorização.
- Observação: não substitui `UserTenant` e não deve ser confundido com tenant efetivo.

### 2. `effectiveTenantId`

- Classificação: obrigatório para qualquer operação tenant-scoped.
- Origem: `EffectiveTenant` validado após autorização/seleção.
- Motivo: esse é o único valor que deve governar a operação atual.
- Observação: nunca deve ser inferido apenas pela sessão ou pelo banco atual.

### 3. `userTenantId`

- Classificação: obrigatório em cenários de usuário comum; opcional em operação global/admin.
- Origem: tenant associado ao usuário no dado persistido, com compatibilidade legada quando aplicável.
- Motivo: precisa existir para comparar com `SelectedTenant` e para validar acesso de usuário comum.
- Observação: não deve ser usada como override de `effectiveTenantId`.

### 4. `selectedTenantId`

- Classificação: opcional.
- Origem: seleção explícita da operação administrativa, quando houver.
- Motivo: representa a empresa/tenant selecionado na UI ou em fluxo de administração.
- Observação: somente deve virar `effectiveTenantId` depois de validação estrita; em operações comuns, deve ser nulo ou equivalentes.

### 5. `targetTenantId`

- Classificação: opcional; parâmetro de caso de uso específico; não permanente do contexto.
- Origem: fluxo de criação de usuário para uma empresa ou outra operação administrada sobre tenant alvo.
- Motivo: utilizado para representar o tenant do alvo da operação sem misturar com o contexto do usuário autenticado.
- Observação: a regra recomendada é que `targetTenantId` não seja parte permanente do `TenantContext`, mas um valor transitório do caso de uso quando necessário.

### 6. `isGlobalAdmin`

- Classificação: opcional; ou equivalent representation.
- Origem: informação de autorização/role derivada do usuário autenticado.
- Motivo: explica que o usuário pode operar em múltiplos tenants em contexto administrativo.
- Observação: a informação pode ser representada por uma flag, um conjunto de permissões ou um enum, mas não deve ser carregada como “tenant” em si.

### 7. `contextOrigin`

- Classificação: opcional.
- Origem: informação sobre como o contexto foi resolvido (session adapter, authenticated user, explicit tenant selection, admin tenant selection).
- Motivo: útil para depuração, auditoria e diagnóstico; não é fonte de decisão executiva.
- Observação: não deve permitir reabrir qualquer caminho de decisão inseguro; é apenas metadado.

### 8. `resolutionSource`

- Classificação: opcional.
- Origem: metadado de como `EffectiveTenant` foi determinado.
- Motivo: útil para rastrear se a decisão foi por `UserTenant`, por `SelectedTenant` validado ou por outra regra de autorização.
- Observação: deve ser apenas auditável; nunca deve substituir a validação real.

### 9. `tenantScope`

- Classificação: decisão pendente.
- Origem: variação possível para distinguir `tenant-scoped` e `global`.
- Motivo: pode ser útil para distinguir operação dentro de empresa vs operação global.
- Observação: em ausência de evidência arquitetural clara no código legado, a forma exata deste campo fica como decisão pendente de escopo global.

### 10. `companyIdLegacy`

- Classificação: proibido no núcleo novo; somente compatibilidade.
- Origem: legado, compatibilidade do sistema antigo.
- Motivo: `tenant_id` deve ser o identificador canônico do novo contexto; `company_id` deve ser isolado na fronteira de compatibilidade.
- Observação: deve ser traduzido antes de entrar no `TenantContext`.

### 11. `dbName`

- Classificação: proibido.
- Motivo: o banco é consequência do tenant, não origem do tenant.
- Observação: nunca deve ficar no `TenantContext`.

### 12. `pdo`, `Database`, `dsn`, `connection`, `credentials`

- Classificação: proibido.
- Motivo: `TenantContext` não deve conhecer infraestrutura de banco.

### 13. `$_SESSION`, `$_POST`, `$_GET`, `Request`

- Classificação: proibido no núcleo do contexto.
- Motivo: são entradas do legado; devem ser adaptadas e validadas antes de compor o contexto.

### 14. `Repository`, `Service`

- Classificação: proibido.
- Motivo: `TenantContext` não deve depender de camada de serviço ou persistência.

## Tabela final de campos propostos

| Campo | Obrigatório | Origem | Pode mudar? | Responsabilidade | Observação |
|---|---|---|---|---|---|
| `authenticatedUserId` | Sim, para operações protegidas | autenticação resolvida | Não | identidade do request | base do contexto |
| `effectiveTenantId` | Sim, quando a operação for tenant-scoped | `EffectiveTenant` validado | Não | execução da operação | único tenant executivo |
| `userTenantId` | Sim para usuário comum; opcional para global | dados persistidos do usuário | Não | autorização/validação | não é override |
| `selectedTenantId` | Opcional | seleção explícita de admin | Não após validação | administração | pode existir sem virar efetivo |
| `targetTenantId` | Opcional e específico de caso de uso | operação de criação/associação | Não na mesma operação | caso de uso | não permanente do contexto |
| `isGlobalAdmin` | Opcional | role/autorização do usuário | Não | autorização | representa capacidade administrativa |
| `contextOrigin` | Opcional | metadado de resolução | Não | diagnóstico/auditoria | não decide tenant |
| `resolutionSource` | Opcional | metadado de resolução | Não | diagnóstico/auditoria | não substitui validação |
| `tenantScope` | Pendência | distinção tenant-scoped/global | Não | escopo da operação | decisão pendente |
| `companyIdLegacy` | Somente compatibilidade | legado | Não | adapter de compatibilidade | nunca no núcleo novo |
| `dbName`, `dsn`, `pdo`, `Database` | Proibido | infraestrutura | N/A | infra | não pertencem ao contexto |
| `$_SESSION`, `$_POST`, `$_GET`, `Request` | Proibido | HTTP/legado | N/A | Boundary/adapter | entrada, não contexto |

## Dados que NÃO devem fazer parte do TenantContext

Os seguintes elementos devem ser explicitamente excluídos do contrato conceitual:

- PDO;
- conexão de banco;
- DSN;
- senha de banco;
- nome físico do banco;
- objeto `Database`;
- `$_SESSION`;
- `$_POST`;
- `$_GET`;
- Request HTTP completo;
- Repository;
- Service;
- estado global/static.

Qualquer um desses itens só seria aceito se houvesse uma necessidade explícita e muito específica de debug ou auditoria. No desenho atual, não há evidência de necessidade real para qualquer um desses itens dentro do `TenantContext`.

Conclusão:

`TenantContext` não deve acumular infraestrutura, sessão, request ou camada de persistência. Ele deve ser um envelope de decisão validada.

## Imutabilidade

### Decisão arquitetural

`TenantContext` deve ser imutável após construção e validação.

Regra recomendada:

- depois de validado, `effectiveTenantId` não pode mudar;
- mudança de tenant deve resultar em nova resolução e novo `TenantContext`;
- não deve haver setters para trocar tenant em operação em curso;
- o objeto não pode ser reutilizado entre requests ou operações diferentes;
- a mesma operação não deve reusar um `TenantContext` anterior para outra empresa.

### Justificativa

Este padrão reduz o risco de:

- o tenant da sessão sobrescrever a operação;
- operação mutante em um request longo;
- estado global compartilhado;
- reuso acidental de contexto entre tenants;
- ação em tenant errado com banco já definido.

### Regras de ciclo de vida

- entrada HTTP legado;
- leitura/adaptação do estado legado;
- autenticação já resolvida pelo legado;
- resolução de identidade;
- resolução/autorização de tenant;
- criação do TenantContext;
- execução do caso de uso;
- descarte ao final da operação/request.

## Ciclo de vida do TenantContext

### Fluxo conceitual

`$_SESSION` / `$_POST` / `$_GET` / dados legados
→ Legacy Context Adapter / Resolver
→ autenticação já resolvida
→ identidade do usuário
→ tenant natural do usuário
→ autorização
→ SelectedTenant (quando existir)
→ EffectiveTenant
→ TenantContext
→ Service / Repository / Infrastructure
→ acesso seguro ao banco do tenant efetivo
→ descarte ao fim da request

### Quem deve compor o TenantContext

A composição deve ser responsabilidade de uma fronteira de compatibilidade / resolver explícito, e não do Service ou do Repository.

A composição conceitual ideal é:

- Legacy boundary resolve dados legados;
- autenticação já foi validada externamente;
- um componente de contexto/adapter valida e compõe `TenantContext`;
- Services recebem o contexto já constituído e validado;
- Infrastructure recebe `effectiveTenantId` e produz o banco/camada de acesso correspondente.

### Critério de validade

O `TenantContext` só é válido quando todas as seguintes condições são verdadeiras:

- `authenticatedUserId` representa usuário autenticado válido;
- `effectiveTenantId` existe quando operação precisa de tenant;
- `userTenantId` está consistente com a identidade do usuário quando necessário;
- `selectedTenantId` foi validado antes de virar `effectiveTenantId`;
- a seleção do tenant não viola a autorização do usuário;
- o tenant está presente e resolvível;
- não há ambiguidade entre sessão e tenant persistido sem autorização clara;
- `effectiveTenantId` não é inferido de `Database::$tenantDbName`, de `$_SESSION` ou de `db_name` sem validação;
- a operação é consistente com o escopo de tenant solicitado.

## Cenário 1 — Usuário comum

Exemplo:

- `authenticatedUserId = 20`
- `userTenantId = 5`

Conceitualmente:

- `effectiveTenantId = 5`
- `selectedTenantId = null` em operação comum
- `isGlobalAdmin = false`

### Campos esperados

- `authenticatedUserId`: obrigatório
- `effectiveTenantId`: obrigatório
- `userTenantId`: obrigatório
- `selectedTenantId`: nulo ou ausente
- `targetTenantId`: não necessário
- `isGlobalAdmin`: opcional/false

### Se houver inconsistência

Quando:

- `$_SESSION['tenant_id'] = 3`;
- `selectedTenantId = 3`;
- banco atual = tenant 3;

a construção deve falhar antes do caso de uso porque a operação não é consistente com o `UserTenant` e não há autorização para trocar o tenant.

### Regra

Usuário comum:

- `effectiveTenantId` deve ser igual a `userTenantId`;
- `selectedTenantId` deve ser nulo ou igual ao mesmo tenant;
- qualquer divergência deve falhar sem entrar em regra de negócio.

## Cenário 2 — Admin global visualizando lista de empresas

Admin pode estar em operação global sem tenant específico.

### Avaliação possível

Há duas alternativas conceituais:

A. `TenantContext` pode existir sem `effectiveTenantId` para operações globais.
B. `TenantContext` só deve existir quando houver operação tenant-scoped.

### Decisão recomendada

A arquitetura nova deve favorecer a opção B como padrão de segurança e clareza: 

- quando a operação é global e não scopo-tenant, o sistema não deve forçar um `TenantContext` tenant-scoped;
- em operações globais, deve existir outra forma de contexto de escopo global ou nenhum contexto específico de tenant.

Entretanto, como no código legado a regra precisa ser formalizada na autorização do admin global, este ponto permanece como:

DECISÃO PENDENTE DE ESCOPO GLOBAL

A documentação não deve inventar uma regra sem suporte suficiente no código legado.

## Cenário 3 — Admin seleciona uma empresa

Exemplo:

- admin autenticado;
- seleciona tenant 5;
- autorização validada;
- operação tenant-scoped.

### Representação conceitual

- `authenticatedUserId` = admin autenticado
- `userTenantId` = pode ser nulo ou valor do tenant natural do administrador quando existir
- `selectedTenantId` = 5
- `effectiveTenantId` = 5 somente após autorização e validação
- `isGlobalAdmin` = true

### Regra

O administrador não deve ser transformado em usuário pertencente ao tenant 5 apenas porque o contexto operacional foi trocado.

O admin continua representando a identidade autenticada e o tenant efetivo da operação é o selecionado, quando autorizado.

## Cenário 4 — Criar usuário para uma empresa

Exemplo:

- admin autenticado;
- escolhe empresa/tenant 5;
- cria usuário destinado ao tenant 5.

### Regra

O contexto operacional da ação deve representar:

- `authenticatedUserId` do admin autenticado;
- `effectiveTenantId = 5` quando a operação é tenant-scoped.

### `targetTenantId`

A recomendação é:

- `targetTenantId` pode existir como parâmetro específico do caso de uso de criação de usuário;
- esse campo não deve ser parte permanente do `TenantContext` se ele for apenas um argumento de operação.

Em outras palavras:

- `TenantContext` = contexto de execução autorizado;
- `targetTenantId` = parâmetro do caso de uso `CreateUserForTenant`, quando necessário.

Isso evita que o `TenantContext` vire um saco de dados de operação e perca a clareza de responsabilidade.

## Cenário 5 — Acesso aos dados da empresa

Conceitualmente:

TenantContext
→ Infrastructure/ConnectionResolver
→ banco do EffectiveTenant
→ Repository
→ dados daquela empresa

### Regra

Service não pode receber ou conhecer:

- `db_name`;
- DSN;
- PDO;
- credenciais.

O que o `Service` deve receber é o `TenantContext` já validado, e a infraestrutura deve resolver a conexão a partir do `effectiveTenantId`.

## Invariantes obrigatórias

As invariantes abaixo devem ser verdadeiras antes de qualquer acesso a dados:

1. `authenticatedUserId` deve representar identidade autenticada válida.
2. O `TenantContext` deve ser transitório e não compartilhado entre requests.
3. `effectiveTenantId`, quando requerido, deve existir e estar autorizado.
4. Usuário comum não pode ter `effectiveTenantId` diferente de `userTenantId`.
5. `selectedTenantId` nunca vira `effectiveTenantId` sem validação explícita.
6. `company_id` nunca entra no núcleo novo sem tradução para `tenant_id`.
7. `EffectiveDatabase` nunca determina `EffectiveTenant`.
8. O contexto não pode ser mutado para trocar tenant durante a operação.
9. A resolução do tenant não pode depender de `$_SESSION` sem validação.
10. O contexto não pode ser reutilizado por outro tenant após a operação.
11. A operação deve falhar antes de qualquer acesso a dados se houver ambiguidade de tenant.
12. Um usuário autenticado não deve operar em tenant que não esteja dentro do conjunto permitido.
13. `targetTenantId` não deve ser confundido com `effectiveTenantId` para operações comuns.
14. `dbName` e `Database` não devem aparecer em `TenantContext`.

## Falhas de composição

A construção do `TenantContext` deve falhar conceptualmente quando:

- usuário não autenticado em operação protegida;
- tenant inexistente;
- tenant bloqueado/inválido quando aplicável;
- usuário comum tentando operar em outro tenant;
- seleção administrativa sem autorização;
- sessão incompatível com identidade persistida;
- tenant não resolúvel;
- ambiguidade entre fontes sem regra segura;
- banco tratando tenant como fonte da verdade;
- `company_id`/`slug`/`session` sendo usados sem validação;
- `selectedTenantId` contradizendo `userTenantId` em operação comum.

Essas falhas devem impedir a continuidade da operação e devem acontecer antes de qualquer acesso a dados ou criação de conexão.

## Relação com a sessão legada

A relação correta deve ser:

`$_SESSION`
→ Legacy Context Adapter / Resolver
→ validação
→ TenantContext

Nunca:

`TenantContext`
→ lê `$_SESSION` por conta própria.

### Justificativa

A sessão é um mecanismo legado e mutável; ela pode refletir estado parcial ou inconsistente. A arquitetura nova deve encapular essa leitura em camada de adaptação, antes de construir o contexto válido.

## Relação com Infrastructure

Conceitualmente:

`TenantContext.effectiveTenantId`
→ `TenantConnectionResolver`
→ lookup seguro do tenant
→ `db_name`
→ conexão

Regra:

- `TenantContext` não deve armazenar `db_name`;
- `Infrastructure` deve resolver o banco com base no tenant efetivo validado;
- `EffectiveDatabase` deve ser derivado da regra de infraestrutura, nunca do contrário.

## Relação com Repository

A decisão correta neste momento é manter a interface do `Repository` como decisão pendente de implementação, porque a arquitetura ainda não definiu a forma final do contrato.

Opções conceituais:

1. `Repository` recebe `TenantContext` completo.
2. `Repository` recebe apenas um contrato mínimo derivado do contexto.

### Vantagens e riscos

Opção 1:
- vantagens: menos ambiguidade, contexto explícito;
- riscos: acoplamento maior e possibilidade de passar informações demais.

Opção 2:
- vantagens: melhor separação e menor acoplamento;
- riscos: exige definição precisa de abstrações e uma fronteira clara.

### Decisão atual

A arquitetura nova deve preferir o mínimo necessário.

Mas, como ainda não houve a implementação real e não há primeiro caso de uso migrado, a decisão concreta sobre a assinatura de Repository deve permanecer:

DECISÃO PENDENTE DE IMPLEMENTAÇÃO

## Segurança

O `TenantContext` ajuda a impedir que o sistema viole a separação entre tenants, ao:

- impedir que tenant A acesse tenant B via `selectedTenantId` sem validação;
- impedir alteração de tenant via `POST`/`GET` e outros campos brutos do cliente;
- impedir troca silenciosa de banco por `Database::$tenantDbName` ou por `db_name` de sessão;
- impedir uso do último tenant da sessão como regra executiva;
- impedir associação de usuário ao tenant errado;
- impedir reuso de conexão/contexto entre tenants;
- manter a alteração de contexto explícita e nova, nunca mutável.

## Matriz de cenários

| Cenário | AuthenticatedUser | UserTenant | SelectedTenant | EffectiveTenant | Context válido? |
|---|---|---|---|---|---|
| usuário comum no seu tenant | 20 | 5 | nulo | 5 | Sim |
| usuário comum com sessão divergente | 20 | 5 | nulo | 3 | Não |
| usuário comum tentou trocar tenant | 20 | 5 | 3 | 3 | Não |
| admin global sem seleção | 40 | nulo ou 7 | nulo | nulo ou não aplicável | pendente |
| admin global seleciona empresa 5 | 40 | nulo ou 7 | 5 | 5 | Sim, se autorizado |
| admin sem autorização | 40 | nulo ou 7 | 5 | 5 | Não |
| tenant A acessando tenant B | 20 | A | B | B | Não |
| criação de usuário para empresa 5 | 40 | nulo ou 7 | 5 | 5 | Sim, se autorizado |

## Exemplos conceituais de contextos válidos e inválidos

### Válido: usuário comum

- `authenticatedUserId = 20`
- `userTenantId = 5`
- `selectedTenantId = null`
- `effectiveTenantId = 5`
- `isGlobalAdmin = false`

### Válido: admin global selecionando tenant 5

- `authenticatedUserId = 40`
- `userTenantId = 7` (ou nulo, conforme regra)
- `selectedTenantId = 5`
- `effectiveTenantId = 5`
- `isGlobalAdmin = true`

### Inválido: sessão divergente sem autorização

- `authenticatedUserId = 20`
- `userTenantId = 5`
- `selectedTenantId = null`
- `effectiveTenantId = 3`
- tentativa de usar `$_SESSION['tenant_id'] = 3`

### Inválido: usuário comum tentando trocar tenant

- `authenticatedUserId = 20`
- `userTenantId = 5`
- `selectedTenantId = 8`
- `effectiveTenantId = 8`

### Inválido: banco determina tenant

- `authenticatedUserId = 20`
- `effectiveTenantId = 5`
- `Database::$tenantDbName = 3`

### Inválido: contexto reutilizado em outro request

- contexto de request anterior com tenant 5 sendo reaproveitado em operação de tenant 8

## Decisões pendentes

### 1. Escopo global do admin

Ainda faltam evidências para decidir com segurança se admin global:

- deve possuir `TenantContext` com `effectiveTenantId = null`;
- deve usar um contexto global separado;
- deve ser considerado tenant-scoped em algumas operações.

Status:

DECISÃO PENDENTE DE ESCOPO GLOBAL

### 2. Representação de autorização do admin global

Ainda não está formalizada no código legado se o admin global tem:

- tenant natural;
- acesso a todos os tenants;
- acesso apenas a subset;
- regra dinâmica por empresa.

Status:

DECISÃO PENDENTE DE AUTORIZAÇÃO

### 3. `TargetTenant` e `SelectedTenant`

A distinção entre `SelectedTenant` e `targetTenantId` precisa ser formalizada em caso de uso real de criação de usuário por empresa.

Status:

NÃO CONFIRMADO

### 4. Implementação do Repository e abstração do contexto

Ainda não é possível decidir sem primeiro migrar um caso de uso real:

- se Repository recebe `TenantContext` completo;
- se recebe uma abstração mínima;
- se o Service faz a adaptação.

Status:

DECISÃO PENDENTE DE IMPLEMENTAÇÃO

## Resumo executivo

O futuro `TenantContext` deve representar uma decisão já validada para a operação atual, e não uma coleção de valores legados ou de infraestrutura. Ele deve conter apenas identidade autenticada e tenant efetivo, com metadados mínimos, sem PDO, sem banco, sem sessão e sem request.

A regra central é:

- `AuthenticatedUser` identifica quem está executando;
- `UserTenant` representa o tenant natural do usuário;
- `SelectedTenant` representa a escolha explícita quando houver;
- `EffectiveTenant` é o único valor que governa a operação;
- `EffectiveDatabase` é consequência da infraestrutura e não a origem do tenant;
- o contexto é imutável e transitório por request;
- qualquer inconsistência deve falhar antes de qualquer acesso a dados.

## Arquivos criados

- [mini-erp-web/docs/t3-01-i03-especificacao-tenantcontext.md](t3-01-i03-especificacao-tenantcontext.md)

## Arquivos modificados

- Nenhum arquivo de produção foi alterado.
- Nenhum arquivo em [mini-erp-web/public](../public), [mini-erp-web/app](../app), [mini-erp-web/config.php](../config.php), [mini-erp-web/database](../database) foi modificado.

## Confirmação final

- nenhum código foi alterado;
- nenhum banco foi alterado;
- nenhum arquivo de produção foi modificado;
- nenhuma migration foi executada;
- nenhum seed foi executado;
- nenhuma classe, interface, adapter ou repository foi criada;
- a task terminou na especificação documental.

## Estado final da task

T3-01-I03 concluída como especificação documental, sem implementação do `TenantContext`, sem alteração do comportamento atual e sem execução de tasks posteriores.
