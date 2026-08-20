# Roadmap do Projeto Mini ERP

## Visão geral

Este documento consolida a lista de tarefas, marcos e progresso do projeto Mini ERP, incluindo planejamento inicial, diagnóstico da estrutura atual, criação da base de migração e recuperação do ambiente local.

## Status geral

- Estado do projeto: em recuperação e preparação de migração
- Banco de dados: MariaDB/XAMPP restaurado e funcionando na porta 3306
- Servidor PHP local: rodando em `http://192.168.1.107:8000`
- Base de arquitetura: legado em PHP puro, com plano de evolução para estrutura modular

---

## Fase 0 — Diagnóstico e preparação

### F0-T01 — Inventário do estado atual
Status: concluído

Itens concluídos:
- Leitura da estrutura principal do projeto
- Identificação dos pontos críticos da aplicação legacy
- Mapeamento de arquivos principais:
  - `public/index.php`
  - `public/login.php`
  - `app/Database.php`
  - `app/Repository.php`
  - `config.php`
  - `database/schema.sql`
  - `database/seeds.sql`
- Registro do diagnóstico em documentação interna

Resultado:
- Base documentada para planejamento da migração

### F0-T02 — Estrutura inicial da migração
Status: concluído

Itens concluídos:
- Criação da estrutura de diretórios para nova arquitetura modular
- Pastas criadas:
  - `src/Controllers`
  - `src/Services`
  - `src/Repositories`
  - `src/Contracts`
  - `src/Context`
  - `src/Infrastructure`
  - `src/Validators`
  - `src/Adapters`
  - `src/Config`
- Validação de sintaxe PHP dos arquivos principais

Resultado:
- Estrutura inicial pronta para evolução sem mexer na base de produção

### F0-T03 — Diagnóstico de runtime e infraestrutura
Status: concluído

Itens concluídos:
- Reproduzido erro de conexão com MySQL (`PDOException` / `SQLSTATE[HY000] [2002]`)
- Verificação de host, porta e credenciais em `config.php`
- Teste de conectividade em PHP CLI
- Verificação de serviço MySQL e rastreio da falha de inicialização

Resultado:
- Confirmado que a falha era operacional do banco, não de sintaxe do PHP

---

## Fase 1 — Recuperação do ambiente local

### T1-01 — Recuperar MySQL/MariaDB
Status: concluído

Itens concluídos:
- Leitura do log de erro do MariaDB em `C:\xampp\mysql\data\mysql_error.log`
- Diagnóstico da falha Aria/plugin
- Backup do diretório de dados
- Remoção/renomeação dos logs Aria para recuperação segura
- Execução de `aria_chk` para reparo de tabelas Aria
- Inicialização do `mysqld` em console para verificar a inicialização real
- Execução de `mysql_upgrade` para atualização da estrutura do banco
- Confirmação de porta 3306 ativa e processo MariaDB funcionando

Resultado:
- MariaDB restaurado e pronto para uso em `127.0.0.1:3306`

### T1-02 — Garantir manutenção do banco
Status: concluído

Itens concluídos:
- Verificação da saúde das tabelas do schema principal
- Validação das bases:
  - `mini_erp`
  - `mini_erp_tenant_1`
  - `mini_erp_tenant_2`
  - `mini_erp_tenant_3`
  - `mini_erp_tenant_5`
  - `mini_erp_tenant_12`
- Criação de script de recuperação Aria para reutilização futura

Resultado:
- Ambiente recuperado e com script de suporte para emergências

---

## Fase 2 — Execução local do projeto

### T2-01 — Levantar servidor PHP embutido
Status: concluído

Itens concluídos:
- Execução do batch de inicialização em `mini-erp-web/start-server.bat`
- Verificação de processo PHP ouvindo na porta 8000
- Confirmação de acesso via `localhost` e `192.168.1.107`

Endereços de acesso:
- `http://localhost:8000`
- `http://127.0.0.1:8000`
- `http://192.168.1.107:8000`

Resultado:
- Projeto acessível localmente em modo de desenvolvimento

---

## Fase 3 — Planejamento da migração arquitetural

### T3-01 — Definir arquitetura alvo
Status: concluído

## Especificação da arquitetura alvo para a migração incremental

### Fonte de verdade

Esta especificação considera como fonte de verdade o baseline registrado em [mini-erp-web/docs/f0-t01-estado-atual-mini-erp.md](f0-t01-estado-atual-mini-erp.md).

Os pontos confirmados pelo baseline que devem orientar a arquitetura alvo são:

- o sistema atual é legado em PHP puro;
- `public/index.php` concentra roteamento, sessão, tenant e múltiplos fluxos de negócio;
- `public/login.php` participa diretamente do fluxo de autenticação;
- `app/Database.php` concentra criação/reuso de conexão e mantém estado global mutável relacionado ao tenant;
- `app/Repository.php` concentra persistência, validação de tenant, autenticação, regras de negócio e operações relacionadas a schema;
- existem vários `new PDO(...)` fora de `Database.php`;
- o sistema usa `$_SESSION` diretamente para autenticação e tenant;
- há coexistência entre `company_id` e `tenant_id`;
- existem fallbacks e compatibilidades relacionadas a tenant;
- schema e seeds são executados durante certos fluxos de inicialização da conexão;
- o sistema possui múltiplos bancos de tenant;
- a estrutura inicial da nova arquitetura já existe em `src/Controllers`, `src/Services`, `src/Repositories`, `src/Contracts`, `src/Context`, `src/Infrastructure`, `src/Validators`, `src/Adapters` e `src/Config`.

### 1. Resumo da arquitetura alvo

A arquitetura alvo deve manter duas linhas simultâneas durante a migração incremental:

- linha legado: o sistema atual em `app/`, `public/`, `config.php`, `database/` e scripts de suporte;
- linha nova: a estrutura modular em `src/`, organizada por responsabilidades e usada para migrar casos de uso individualmente.

A arquitetura nova não deve exigir reescrita completa do sistema. Ela deve permitir:

- reduzir a concentração de responsabilidade no entrypoint legado;
- isolar regras de negócio em serviços;
- isolar acesso a dados em repositórios;
- partir da realidade do baseline sem alterar o comportamento atual;
- permitir coesão por caso de uso em vez de migração em bloco.

A regra principal é: a nova arquitetura não deve substituir o sistema legado de uma vez; ela deve coexistir com ele por fronteiras temporárias e adaptadores.

### 2. Responsabilidade de cada camada

#### `src/Controllers`
Responsabilidade:
- receber a requisição HTTP;
- extrair entrada de formulário, query string e payload;
- identificar o caso de uso;
- delegar para o service apropriado;
- retornar estrutura de resposta ou resultado ao fluxo atual.

Não deve:
- conter lógica de negócio complexa;
- acessar PDO diretamente;
- decidir política de tenant;
- manipular schema do banco;
- misturar persistência e rendering.

#### `src/Services`
Responsabilidade:
- executar regras de negócio do caso de uso;
- coordenar fluxo de validação e persistência;
- orquestrar múltiplas operações quando necessário;
- manter a lógica que hoje está espalhada em `public/index.php` e `app/Repository.php`.

Não deve:
- depender diretamente de `$_POST`/`$_SESSION` como fonte principal;
- tratar HTML ou renderização;
- conhecer detalhes de SQL;
- iniciar conexões PDO;
- decidir rotas ou entrada HTTP.

#### `src/Repositories`
Responsabilidade:
- encapsular acesso a dados por entidade ou caso de uso;
- realizar leitura e escrita em banco de forma coesa;
- expor operações específicas do domínio, como listar clientes, salvar produto, localizar tenant, consultar usuário por identidade.

Não deve:
- ser a camada de regra de negócio;
- validar autorização e política de domínio;
- conter lógica de sessão ou HTTP;
- agir como “caixa de ferramentas” para todo o sistema.

#### `src/Contracts`
Responsabilidade:
- formalizar o que as camadas esperam receber e devolver;
- permitir desacoplamento entre implementação e uso;
- expressar contratos de domínio e infraestrutura sem fixar dependência concreta.

Não deve:
- conter lógica executável real;
- misturar contrato com regra de negócio;
- depender de detalhes de banco, sessão ou UI.

#### `src/Context`
Responsabilidade:
- representar o estado atual da execução da requisição;
- guardar identidade do usuário autenticado;
- guardar tenant atual e os metadados necessários para isolamento;
- servir como fonte de contexto sem assumir regra de domínio.

Não deve:
- implementar regras de negócio do ERP;
- realizar acesso a banco;
- substituir autenticação;
- agir como repositório de dados;
- ser usado como armazenamento global compartilhado entre requisições.

#### `src/Infrastructure`
Responsabilidade:
- encapsular conectividade, adaptadores e mecanismos externos;
- centralizar acesso a banco, drivers e integrações técnicas;
- suportar a implementação concreta do acesso ao banco e da conexão do tenant.

Não deve:
- decidir regras de negócio do ERP;
- ter responsabilidade de autenticação;
- decidir permissões por entidade;
- misturar operações de domínio com detalhes técnicos do banco.

#### `src/Validators`
Responsabilidade:
- validar entrada do caso de uso;
- verificar campos obrigatórios, formatos e invariantes;
- impedir que dados inválidos cheguem a serviços e repositórios.

Não deve:
- escrever no banco;
- executar lógica de negócio complexa;
- tomar decisões de tenant ou autenticação;
- esconder regras de continuidade do domínio que pertencem ao serviço.

#### `src/Adapters`
Responsabilidade:
- adaptar estruturas do legado para formatos da nova arquitetura;
- adaptar dados da nova arquitetura para compatibilidade com o legado;
- permitir coexistência entre fluxos antigos e novos sem acoplamento permanente.

Não deve:
- substituir a separação real de responsabilidades;
- tornar o legado uma dependência permanente da arquitetura nova;
- esconder problemas arquiteturais ao invés de isolar a incompatibilidade.

#### `src/Config`
Responsabilidade:
- manter configuração de ambiente, banco, porta, credenciais e parâmetros globais;
- servir como entrada de dados de infraestrutura;
- permitir que camadas de negócio e infraestrutura dependam de parâmetros declarados, mas não executem lógica.

Não deve:
- conter regras do negócio;
- decidir sobre autenticação;
- implementar serviços ou regras de persistência.

### 3. Fluxo conceitual de uma requisição

O fluxo ideal da nova arquitetura deve respeitar a cadeia de responsabilidade, sem depender do estado global mutável observado no legado.

Fluxo conceitual esperado:

1. Request HTTP
2. Controller
3. Validator
4. Service
5. Contract
6. Repository
7. Infrastructure / Database
8. Resultado de volta ao Service
9. Resultado de volta ao Controller
10. Resposta HTTP ou renderização

No contexto específico do projeto, esse fluxo deve coexistir com o fluxo legado atual, porque a aplicação ainda inicia em `public/index.php` e a sessão/tenant são resolvidos em pontos do código antigo.

A arquitetura nova deve ser pensada como: a rota antiga continua recebendo a requisição; a nova camada pode ser ativada por caso de uso específico; o legado atua como camada de compatibilidade temporária.

### 4. Dependências permitidas entre camadas

A arquitetura deve ser explicitamente restritiva:

- Controller → Service: permitido
- Controller → Validator: permitido
- Controller → Context: permitido
- Service → Contract: permitido
- Service → Validator: permitido
- Service → Adapter: permitido
- Repository → Contract: permitido
- Repository → Infrastructure: permitido
- Infrastructure → Config: permitido
- Context → Config: permitido em leitura apenas, quando estritamente necessário
- Adapter ↔ Legacy: permitido apenas como fronteira de compatibilidade

### 5. Dependências proibidas entre camadas

- Controller → PDO: proibido
- Controller → Database: proibido
- Controller → Repository direto: proibido
- Service → PDO: proibido
- Service → $_SESSION: proibido como lógica central
- Service → HTML/render: proibido
- Repository → Controller: proibido
- Repository → HTTP/session: proibido em regra de domínio
- Context → Repository: proibido
- Infrastructure → Service: proibido
- Validators → banco de dados: proibido para regra do caso de uso
- Adapters → regra de negócio: proibido

A regra mais importante: nenhum fluxo de negócio deve abrir uma conexão ou decidir banco de forma direta fora da infraestrutura. Isso é crucial para evitar vazamento de tenant e mistura de contexto.

### 6. Onde devem ficar cada responsabilidade

- regras de negócio: `src/Services`
- validação: `src/Validators`
- acesso a dados: `src/Repositories`
- conexão com banco: `src/Infrastructure`
- configuração: `src/Config` e suporte compatível com `config.php` existente
- autenticação: camada específica de autenticação, separada de regras de negócio e persistência, no momento ainda no legado
- contexto do tenant: `src/Context`
- integração temporária com legado: `src/Adapters`

A arquitetura deve separar conceitualmente:

- autenticação: quem identifica o usuário e valida credenciais;
- sessão HTTP: mecanismo de transporte de estado na requisição;
- identidade do usuário: dados do usuário autenticado;
- tenant atual: contexto de isolamento e seleção de banco;
- conexão com o banco do tenant: responsabilidade de infraestrutura, não de negócio.

### 7. Coexistência com o código legado em `app/`

A coexistência do legado com a nova arquitetura deve seguir uma fronteira clara:

- o legado continua responsável pelas rotas atuais, sessão e fluxo de execução conhecido;
- a nova arquitetura entra em pontos específicos por caso de uso;
- a integração acontece por `Adapters` e fronteiras definidas;
- a nova arquitetura não deve depender do legado como base estrutural permanente;
- o legado permanece compatível até que cada módulo seja validado e migrado.

A estratégia correta é migrar por vez, não por “substituição estrutural completa”.

### 8. Como o código novo pode usar funcionalidades legadas sem dependência permanente

Durante a migração, o código novo pode consumir funcionalidade do legado somente por fronteiras explícitas:

- adapter de entrada para transformar estruturas legadas em entradas da nova arquitetura;
- adapter de saída para transformar dados novos para a representação atual do legado;
- adaptador de compatibilidade para acesso temporário a operações legadas e dados com estrutura antiga;
- contratos que isolam a dependência para que o serviço novo não saiba detalhes do legado.

A regra: o legado pode ser usado como recurso de migração, não como “núcleo” da nova arquitetura.

### 9. Decisões da fase atual de migração

As decisões abaixo foram aprovadas para a etapa atual de migração incremental e devem orientar as próximas tasks sem alterar o comportamento legado, sem mexer no banco e sem transformar este momento em reescrita estrutural.

#### 9.1. TenantContext

Escolha: Opção A — TenantContext como estrutura operacional de request.

Decisão:

`TenantContext` será um objeto/contexto transiente por requisição, responsável por representar explicitamente:

- tenant_id ativo;
- identidade do usuário autenticado quando disponível;
- identificação necessária para resolução do banco do tenant;
- metadados mínimos necessários para isolamento da operação.

`TenantContext` NÃO será entidade de domínio neste momento.

`TenantContext` NÃO deve:

- acessar banco;
- autenticar usuário;
- ler `$_SESSION` diretamente;
- abrir PDO;
- possuir regras de negócio;
- ser estado global/static compartilhado.

A transformação do estado legado (`$_SESSION`, slug, `company_id` etc.) para `TenantContext` deverá ocorrer na fronteira de entrada/compatibilidade.

Motivo:
reduzir acoplamento e substituir progressivamente o estado global de tenant por contexto explícito sem redefinir o domínio durante a migração.

#### 9.2. Fronteira entre `app/Repository.php` e os novos repositories

Escolha: estratégia baseada principalmente na Opção A, com substituição incremental por caso de uso.

Decisão:

`app/Repository.php` continua sendo parte do legado e fonte de comportamento existente para fluxos ainda não migrados.

Não transformar `app/Repository.php` inteiro em adapter.

Cada caso de uso migrado deverá possuir sua nova fronteira de acesso a dados quando necessário.

Após um caso de uso ser migrado e validado:

- o novo fluxo passa a utilizar o novo repository/contract;
- o fluxo legado equivalente pode deixar de ser utilizado naquele ponto específico;
- os demais métodos de `app/Repository.php` permanecem intactos.

Adapters poderão encapsular chamadas ao Repository legado temporariamente quando necessário para preservar compatibilidade.

Não haverá migração do Repository inteiro de uma vez.

Motivo:
evitar big bang, duplicação extensa de comportamento e refatoração de áreas não relacionadas ao caso de uso atual.

#### 9.3. Modelo de isolamento entre tenants

Escolha: Opção A — banco por tenant.

Decisão:

Durante esta migração, preservar o modelo operacional já existente de banco dedicado por tenant.

Não migrar para schema por tenant nem para banco único nesta fase.

A arquitetura nova deverá abstrair a resolução da conexão para que Services, Controllers e regras de negócio não conheçam:

- nome físico do banco;
- DSN;
- credenciais;
- lógica de troca de conexão.

A escolha do banco deverá acontecer na camada de infraestrutura a partir do TenantContext/resolução apropriada.

Importante:

Esta decisão preserva o modelo atual durante a migração e não significa que a topologia de dados nunca poderá ser reavaliada futuramente.

Não alterar provisionamento ou bancos existentes nesta task.

#### 9.4. `company_id` versus `tenant_id`

Escolha: Opção B — coexistência controlada por camada de compatibilidade.

Decisão:

Na arquitetura nova, `tenant_id` será o identificador canônico do contexto de tenant.

`company_id` continuará existindo apenas onde o legado exigir compatibilidade.

A tradução entre `company_id` e `tenant_id` deverá ficar isolada na fronteira de compatibilidade/adapter.

Services, Controllers e novas regras de negócio não devem operar alternando entre `company_id` e `tenant_id`.

Não executar agora:

- alteração de schema;
- remoção de `company_id`;
- migração de dados;
- normalização física das tabelas.

A eliminação definitiva de `company_id` deverá ser uma task futura específica, com análise de impacto e migração própria.

#### 9.5. Autenticação, sessão HTTP e TenantContext

Escolha: Opção A — autenticação e sessão permanecem no legado durante a primeira etapa da migração.

Decisão:

O fluxo atual de login e sessão deverá permanecer funcionando sem alterações enquanto os primeiros casos de uso são migrados.

A nova arquitetura NÃO deverá reimplementar autenticação agora.

Entretanto, código novo não deverá usar `$_SESSION` diretamente como fonte de contexto.

Deverá existir futuramente uma fronteira de entrada que traduza o estado autenticado já resolvido pelo legado para `TenantContext`.

Conceitualmente:

Legado / sessão
→ fronteira de compatibilidade
→ TenantContext
→ novo caso de uso

Assim:

- autenticação continua no legado inicialmente;
- sessão continua como mecanismo HTTP legado;
- TenantContext passa a ser o contexto explícito consumido pelo código novo;
- Services e Repositories novos não devem depender diretamente de `$_SESSION`.

A migração da autenticação deverá ser tratada posteriormente como feature/task própria.

#### 9.6. Contrato de acesso a dados por tenant

Escolha: Opção A — contrato por domínio/caso de uso, com tenant disponível por contexto explícito.

Decisão:

Os contratos de repository devem representar operações de domínio/caso de uso, não detalhes físicos de banco.

Evitar APIs como:

`saveCliente($tenantId, $databaseName, ...)`

quando tenant e banco já pertencem ao contexto da operação.

O Service não deve conhecer nome do banco ou DSN.

O acesso deverá conceitualmente seguir:

Service
→ Repository Contract
→ Repository Implementation
→ infraestrutura de conexão baseada no TenantContext

O tenant deve estar explícito na composição/dependência da operação, mas o nome físico do banco deve permanecer detalhe de infraestrutura.

Evitar contexto global/static.

Operações especialmente sensíveis poderão exigir validação explícita de tenant, mas isso não deve criar contratos inconsistentes entre repositories.

#### 9.7. Bootstrap, schema e seeds em runtime

Decisão:

Preservar o comportamento legado durante a migração inicial para evitar regressão.

Entretanto, a arquitetura nova NÃO deverá reproduzir o padrão de executar:

- schema;
- seeds;
- ALTER TABLE;
- bootstrap estrutural

durante simples obtenção de conexão.

Nenhuma mudança no Database.php legado será feita apenas por causa desta decisão.

Separar bootstrap/migrations do runtime deverá ser uma task futura específica.

Novos componentes de infraestrutura não devem introduzir novas alterações automáticas de schema ao abrir conexão.

#### 9.8. Fallbacks e compatibilidade

Decisão:

Todos os fallbacks existentes devem ser considerados comportamento legado até serem classificados individualmente.

Não remover nem reproduzir automaticamente fallbacks na arquitetura nova.

Regra:

- fallback necessário para fluxo legado permanece no legado;
- adapter pode reproduzir compatibilidade somente quando necessária ao caso de uso migrado;
- novos Services não devem transformar fallback legado em regra de domínio;
- cada fallback que for eliminado deverá possuir task própria ou critério explícito de migração.

Especial atenção aos fallbacks relacionados a:

- `tenant_id`;
- `company_id`;
- admin;
- usuário;
- sessão;
- seleção de tenant.

#### 9.9. Unidade mínima e critério de migração

Decisão:

A unidade preferencial de migração será um caso de uso vertical, e não uma entidade inteira ou camada inteira.

Exemplos:

- listar clientes;
- cadastrar cliente;
- editar cliente;
- excluir cliente.

Cada caso de uso poderá atravessar:

entrada legado
→ Adapter/Controller
→ Validator
→ Service
→ Contract
→ Repository
→ Infrastructure

Um caso de uso somente será considerado migrado quando:

1. comportamento esperado estiver identificado no legado;
2. nova implementação respeitar a arquitetura definida;
3. tenant correto for utilizado;
4. resultado funcional for compatível com o legado;
5. fluxo legado não relacionado continuar funcionando;
6. não houver acesso direto a PDO por Controller/Service;
7. não houver `$_SESSION` dentro de Service/Repository novo;
8. não houver alteração automática de schema;
9. validações relevantes tiverem sido executadas;
10. houver evidência da compatibilidade antes/depois.

Não considerar um módulo inteiro migrado apenas porque foram criadas novas classes.

### 10. Responsabilidade futura de `TenantContext`

`TenantContext` deve ser a representação explícita do contexto da operação atual, ou seja:

- tenant ativo;
- usuário autenticado;
- escopo de requisição;
- dados minimamente necessários para autorização e isolamento.

Ele deve:
- ser transiente por requisição;
- ser preenchido antes de acesso a dados sensíveis;
- impedir vazamento por estado global compartilhado;
- servir como entrada para validar pertencimento e isolamento de tenant.

Ele não deve:
- executar autenticação em si;
- implementar regra de negócio;
- acessar banco diretamente;
- ser utilizado como armazenamento global do sistema.

### 11. Tratamento da coexistência entre `company_id` e `tenant_id`

O baseline mostra que o sistema atual ainda trabalha com coexistência entre `company_id` e `tenant_id`.

A arquitetura alvo deve tratar isso como um estado transitório de compatibilidade, não como design final estável.

Regra arquitetural:

- a nova arquitetura deve tratar `tenant_id` como a referência principal de isolamento e contexto executório;
- `company_id` deve ser tratado como compatibilidade legada até a normalização do domínio;
- a conversão entre os dois nomes deve ocorrer apenas em adaptadores ou camada de compatibilidade;
- services e regras de negócio não devem depender de nomes diferentes em diferentes pontos do sistema.

Essa regra evita que a lógica de negócio seja contaminada por ambiguidade de nomenclatura.

### 12. Compatibilidade e fallbacks existentes

O código legado contém fallbacks de sessão, compatibilidade de tenant e validações permissivas. A arquitetura alvo deve definir como essas compatibilidades serão tratadas sem transformá-las em regra permanente.

Os fallbacks e mecanismos compatíveis devem ser:

- isolados em adaptadores ou camada de compatibilidade;
- explícitos e documentados;
- temporários e rastreáveis;
- usados apenas para manter estabilidade do sistema legado durante a migração.

Não podem ser promovidos para regra definitiva da arquitetura nova.

### 13. Como impedir que Services ou regras de negócio criem conexões PDO diretamente

A regra deve ser arquitetural e de disciplina de camada:

- Services não podem conhecer `PDO` ou `new PDO(...)`;
- Services só devem depender de contratos e de abstrações de infraestrutura;
- a criação de conexão deve ocorrer exclusivamente em `Infrastructure` ou em algum ponto de bootstrap técnico;
- o acesso a banco deve ser encapsulado por `Repositories` e por infraestrutura dedicada.

Se um service precisar acessar dados, ele deve pedir isso por meio de contrato e repositório, e não por instância direta de banco.

### 14. Como impedir que Controllers acessem diretamente banco ou infraestrutura

- Controllers devem depender apenas de interfaces de uso e de serviços da aplicação;
- qualquer operação que precise persistência deve ser invocada por um service ou por um contrato;
- Controllers não devem conhecer detalhes do banco, DSN, conexão, tenant, schema ou SQL.

A regra prática: um Controller só coordena entrada e saída; ele não deve fazer leitura/escrita direta em infraestrutura.

### 15. Como permitir migrar um caso de uso por vez

A estratégia de migração deve ser por caso de uso e por módulo, não por “toda a aplicação de uma vez”.

Fluxo recomendado:

1. identificar um caso de uso isolado;
2. mapear entrada, saída, regra de negócio e dados envolvidos;
3. separar a lógica do fluxo legado em um service novo;
4. criar a interface do repositório ou contrato da infraestrutura;
5. manter o fluxo legada como fallback de compatibilidade;
6. validar o caso de uso isolado sem quebrar os demais;
7. repetir para o próximo caso de uso.

Essa abordagem reduz risco e mantém a aplicação funcional enquanto a arquitetura nova se forma.

### 16. Como validar cada fluxo migrado sem alterar o comportamento dos fluxos ainda legados

A validação deve ser incremental e orientada a comparação:

- cada caso de uso migrado deve ser validado de forma isolada;
- o fluxo legado anterior deve continuar sendo usado como referência até que o novo caminho seja validado;
- o sistema deve manter compatibilidade de saída e de entrada enquanto os módulos ainda coexistem;
- os testes, quando forem criados, devem verificar comportamento real e não apenas a estrutura interna da arquitetura.

Durante esta etapa, a regra é: não quebrar nada do que ainda vive no legado.

### 17. Riscos arquiteturais a considerar nas próximas fases

Os principais riscos são:

- `$_SESSION` como fonte de verdade para tenant e autenticação;
- estado global mutável em `Database::setTenantDbName()`;
- coexistência entre `tenant_id` e `company_id` sem normalização;
- múltiplos `new PDO(...)` em pontos fora da infraestrutura central;
- bootstrap de schema e seeds em runtime;
- baixa separação entre serviço, persistência e sessão;
- vazamento de tenant entre requisições ou operações;
- dependência do legado persistindo no núcleo da nova arquitetura;
- migração por módulo sem padrão claro de contrato e fronteira.

### 18. Decisões que continuam pendentes para etapas futuras

As decisões abaixo não foram convertidas em regra definitiva desta etapa de migração, porque exigem análise posterior de impacto, garantia operacional ou replanejamento de domínio:

- definição da evolução final da autenticação e da sessão fora do legado;
- eliminação definitiva de `company_id` e normalização física de esquema;
- revisão da topologia futura de dados em banco por tenant, schema por tenant ou banco único;
- separação completa entre runtime do sistema e bootstrap/migrations;
- definição de padrões finais de contratos de acesso a dados e autorização por tenant em nível global.

Essas decisões exigem tarefas próprias de desenho e validação fora da fase atual de migração incremental.

### 19. O que NÃO faz parte desta arquitetura neste momento

- não há framework novo;
- não há ORM;
- não há container de injeção complexo;
- não há event bus;
- não há abstração genérica sem necessidade comprovada;
- não há migração de banco ou criação de schema;
- não há alteração de autenticação, sessão, tenant, rotas ou comportamento legado;
- não há criação de serviços, controllers, repositories, interfaces, classes ou implementação de TenantContext;
- não há alteração de `app/`, `public/`, `config.php` ou arquivos de produção.

### 20. Critérios de aceite desta especificação

- arquitetura alvo claramente definida;
- responsabilidade de cada camada claramente documentada;
- dependências permitidas e proibidas claramente listadas;
- coexistência com o legado definida;
- migração incremental definida;
- responsabilidade futura do `TenantContext` definida;
- comparação entre `company_id` e `tenant_id` explicitamente tratada;
- hotspots do baseline considerados;
- riscos conhecidos documentados;
- decisões da fase atual de migração registradas e validadas;
- decisões pendentes futuras explicitamente identificadas;
- ausência de implementação de código.

---

### T3-02 — Migrar entidades e regras de negócio
Status: pendente

Itens planejados:
- `Clientes`
- `Produtos`
- `Vendas`
- `Fornecedores`
- `Motoristas`
- `Transportadoras`
- `CFOPs`
- `Usuários`
- `Empresas / Tenants`

### T3-03 — Validar fluxo de autenticação e tenant
Status: pendente

Itens planejados:
- Revisar login e sessão
- Padronizar tenant atual
- Garantir conexão correta por banco do tenant
- Validar compatibilidade de dados antigos

### T3-04 — Reorganizar views e frontend
Status: pendente

Itens planejados:
- Reduzir lógica visível em `public/index.php`
- Separar renderização e regras de apresentação
- Modularizar componentes e templates

---

## Pendências e próximos passos

### Críticos
- Validar login do sistema em ambiente real
- Testar fluxo de empresa/tenant completo
- Confirmar permissões e usuários admin
- Validar dashboard e telas principais com banco restaurado

### Recomendados
- Exportar backups de segurança do banco
- Registrar snapshot do ambiente funcional atual
- Documentar convenções da arquitetura nova
- Definir backlog de migração em tarefas menores por módulo

---

## Arquivos e artefatos gerados

- `mini-erp-web/docs/f0-t01-estado-atual-mini-erp.md`
- `mini-erp-web/scripts/recover_aria.ps1`
- `mini-erp-web/docs/roadmap-projeto.md`

---

## Observações finais

O projeto avançou de um estado de falha operacional para um ambiente funcional local, com MySQL restaurado e servidor PHP rodando. O próximo grande passo é a migração arquitetural em camadas, mantendo o sistema legado estável enquanto a nova estrutura é organizada e validada.

---

## Backlog técnico de implementação da T3-01

Status: backlog de implementação da fundação multi-tenant, sem execução de código nesta etapa.

Abaixo ficam as primeiras tasks de implementação após as investigações e desenhos concluídos em:

- [mini-erp-web/docs/t3-01-i01-fluxo-real-login-empresa-tenant.md](t3-01-i01-fluxo-real-login-empresa-tenant.md)
- [mini-erp-web/docs/t3-01-i02-regra-operacional-tenant.md](t3-01-i02-regra-operacional-tenant.md)
- [mini-erp-web/docs/t3-01-i03-especificacao-tenantcontext.md](t3-01-i03-especificacao-tenantcontext.md)
- [mini-erp-web/docs/t3-01-i04-fronteira-legacy-tenantcontext.md](t3-01-i04-fronteira-legacy-tenantcontext.md)
- [mini-erp-web/docs/t3-01-i05-resolucao-banco-tenantcontext.md](t3-01-i05-resolucao-banco-tenantcontext.md)
- [mini-erp-web/docs/f0-t01-estado-atual-mini-erp.md](f0-t01-estado-atual-mini-erp.md)

### T3-01-IMP01 — TenantContext mínimo e imutável

- ID: T3-01-IMP01
- Título: Implementar TenantContext mínimo e imutável
- Objetivo: criar apenas o objeto de contexto já especificado documentalmente, com os campos mínimos aprovados e sem integrar ainda ao fluxo legado.
- Arquivos que podem ser criados:
  - `src/Context/TenantContext.php`
  - `src/Context/TenantContextFactory.php` (opcional, somente se o desenho exigir fábrica em vez de construtor direto)
  - testes unitários focados em imutabilidade e validação de campos
- Arquivos que podem ser modificados:
  - diretórios de contexto e testes relacionados
- Arquivos proibidos:
  - `public/index.php`
  - `public/login.php`
  - `app/Database.php`
  - `app/Repository.php`
  - `config.php`
  - qualquer arquivo de banco, migration ou seed
- Dependências:
  - T3-01-I03 concluída
  - T3-01-I02 concluída
- Implementação permitida:
  - criação de classe imutável com campos mínimos usados pela operação;
  - construtor com validação simples para `tenant_id` e `user_id` quando aplicável;
  - getters somente leitura;
  - zero acesso a `$_SESSION`, PDO, `Database`, `Repository` ou DSN.
- Implementação proibida:
  - acessar sessão no núcleo novo;
  - escolher banco nesta task;
  - abrir PDO;
  - depender de HTTP ou request;
  - incluir `db_name` ou DSN no objeto;
  - aceitar mutação após construção.
- Critérios de aceite:
  - `TenantContext` é instanciável com os campos mínimos permitidos;
  - objeto é imutável após criação;
  - não há qualquer vínculo com banco, sessão, HTTP ou `Database`;
  - a task é isolada e não altera comportamento legado.
- Testes/validações obrigatórios:
  - teste de criação válida;
  - teste de campo inválido falha cedo;
  - teste de mutabilidade impossível;
  - teste de ausência de dependência de infraestrutura.
- Evidências esperadas:
  - objeto de contexto pronto e sem acesso a infraestrutura;
  - sem alteração em produção.
- Rollback/confirmação:
  - como não há integração, a confirmação de integridade do legado é a não alteração de arquivos de produção e a execução isolada de testes do novo objeto.

### T3-01-IMP02 — Entrada normalizada do legado

- ID: T3-01-IMP02
- Título: Implementar entrada normalizada do legado
- Objetivo: criar a menor representação necessária para que dados vindos da borda legado sejam tratados sem passar `$_SESSION` diretamente para o núcleo novo.
- Arquivos que podem ser criados:
  - `src/Adapters/LegacyTenantInput.php` ou equivalente mínimo;
  - testes focados na normalização de valores legados
- Arquivos que podem ser modificados:
  - `src/Adapters` e testes correspondentes
- Arquivos proibidos:
  - `public/index.php`
  - `public/login.php`
  - `app/Repository.php`
  - `app/Database.php`
  - qualquer fluxo de persistência real
- Dependências:
  - T3-01-I04 concluída
  - T3-01-IMP01
- Implementação permitida:
  - estrutura mínima de entrada contendo valores legados normalizados para o núcleo novo;
  - conversão de `company_id` para representação compatível na borda;
  - isolamento do conhecimento de sessão/compatibilidade em adapter.
- Implementação proibida:
  - transportar `db_name` como entrada de negócio;
  - executar regra de negócio na borda;
  - autenticar usuário;
  - abrir PDO ou resolver banco;
  - criar DTOs desnecessários além do mínimo necessário.
- Critérios de aceite:
  - a camada de entrada consegue transformar estado legado em representação normalizada;
  - a estrutura é mínima e não duplica o domínio completo;
  - o núcleo novo não depende de `$_SESSION` direto.
- Testes/validações obrigatórios:
  - simulação de sessão legada com `tenant_id` e `company_id`;
  - cenário com compatibilidade de campo;
  - cenário em que a entrada é inválida e deve falhar de forma explícita.
- Evidências esperadas:
  - entrada normalizada sem regra de negócio;
  - nenhum acesso a banco do tenant.
- Rollback/confirmação:
  - legado inalterado; a camada nova fica apenas no lado de entrada.

### T3-01-IMP03 — LegacyContextAdapter mínimo

- ID: T3-01-IMP03
- Título: Implementar LegacyContextAdapter mínimo
- Objetivo: ficar na borda e transformar o estado legado em entrada normalizada, sem executar regra de negócio, sem abrir PDO e sem decidir banco.
- Arquivos que podem ser criados:
  - `src/Adapters/LegacyContextAdapter.php`
  - adaptadores específicos e testes para entrada/saída
- Arquivos que podem ser modificados:
  - `src/Adapters` e testes de borda
- Arquivos proibidos:
  - `app/Repository.php`
  - `app/Database.php`
  - `public/index.php`
  - regras de persistência e acesso a banco
- Dependências:
  - T3-01-IMP02
  - T3-01-I04 concluída
- Implementação permitida:
  - leitura da sessão e compatibilidade legada somente neste adapter;
  - transformação de dados do fluxo antigo em entrada normalizada;
  - empacotamento de `tenant_id`, `company_id`, `user_id` e outros indicadores legados em estrutura definida pela borda.
- Implementação proibida:
  - validar negócio do tenant;
  - decidir quem é o tenant efetivo;
  - alterar sessão;
  - corrigir fallbacks de forma permanente;
  - autenticar usuário;
  - resolver db_name ou abrir conexão.
- Critérios de aceite:
  - a borda conhece a compatibilidade legada de forma isolada;
  - não há regra de domínio nesta camada;
  - a saída é reutilizável para a próxima etapa de resolução.
- Testes/validações obrigatórios:
  - cenário legado com `tenant_id` válido;
  - cenário legado com fallback de `company_id`;
  - cenário com sessão divergente e dados inconsistentes.
- Evidências esperadas:
  - a borda converte de legado para entrada normalizada e não toma decisão sobre tenant efetivo.
- Rollback/confirmação:
  - legado continua intacto; a borda nova não toca comportamento de produção nem persistência.

### T3-01-IMP04 — TenantContextResolver mínimo

- ID: T3-01-IMP04
- Título: Implementar TenantContextResolver mínimo
- Objetivo: validar e resolver os dados necessários para produzir `TenantContext`, respeitando as regras de tenant canônico, compatibilidade e ausência de fonte canônica em sessão/banco.
- Arquivos que podem ser criados:
  - `src/Context/TenantContextResolver.php`
  - `src/Context/TenantResolutionResult.php` (opcional, se necessário para retorno explícito)
  - testes específicos de resolução e validação
- Arquivos que podem ser modificados:
  - `src/Context` e testes de resolução
- Arquivos proibidos:
  - `app/Database.php`
  - `app/Repository.php`
  - `public/index.php`
  - qualquer acesso direto a PDO;
  - qualquer mudança em schema ou banco
- Dependências:
  - T3-01-IMP01
  - T3-01-IMP03
  - T3-01-I02 concluída
  - T3-01-I05 concluída como referência de regra de não decisão por banco
- Implementação permitida:
  - receber entrada normalizada da borda;
  - validar `tenant_id` canônico;
  - identificar `user_tenant_id` somente como dado de contexto operacional;
  - determinar `effectiveTenantId` somente após validação explícita;
  - falhar de forma segura quando a autorização global ainda estiver indefinida.
- Implementação proibida:
  - tratar `$_SESSION` como fonte canônica;
  - usar `Database::$tenantDbName` como autoridade;
  - inventar autorização global sem regra comprovada;
  - decidir banco nesta etapa;
  - misturar resolução com persistência.
- Critérios de aceite:
  - `TenantContext` só é produzido quando a resolução estiver consistente;
  - `effectiveTenantId` está baseado em validação e não em `db_name`;
  - cenário sem autorização necessária falha de maneira explícita e segura;
  - sessão divergente não troca tenant silenciosamente.
- Testes/validações obrigatórios:
  - tenant válido produz contexto válido;
  - tenant inválido falha;
  - usuário comum sem autorização não pode trocar tenant;
  - sessão divergente não sobrescreve o contexto validado.
- Evidências esperadas:
  - fluxo determinístico de resolução sem banco;
  - `TenantContext` resultante pronto para a próxima camada.
- Rollback/confirmação:
  - toda validação fica no resolver; o legado permanece sem alteração em produção.

### T3-01-IMP05 — TenantConnectionResolver mínimo

- ID: T3-01-IMP05
- Título: Implementar TenantConnectionResolver mínimo
- Objetivo: resolver `TenantContext.effectiveTenantId` em `db_name` e conexão correta, sem aceitar `db_name` externo e sem executar bootstrap, schema, seeds ou migração.
- Arquivos que podem ser criados:
  - `src/Infrastructure/TenantConnectionResolver.php`
  - `src/Infrastructure/TenantDatabaseDescriptor.php` (opcional)
  - testes de conexão por tenant e rejeição de entradas externas
- Arquivos que podem ser modificados:
  - `src/Infrastructure` e testes de infraestrutura
- Arquivos proibidos:
  - `app/Database.php` para reescrita completa;
  - `app/Repository.php` para refatoração ampla;
  - `public/index.php`;
  - qualquer script de migration, seed ou `ALTER TABLE`
- Dependências:
  - T3-01-IMP04
  - T3-01-I05 concluída
- Implementação permitida:
  - lookup do tenant válido no control-plane;
  - validação de `db_name` do tenant confiável;
  - criação da conexão correta da infraestrutura;
  - retorno de conexão/descriptor sem expor DSN ao Service.
- Implementação proibida:
  - aceitar `db_name` fornecido externamente como autoridade;
  - usar `Database::$tenantDbName` como fonte de tenant;
  - executar schema, seed, migration ou `ALTER TABLE`;
  - criar conexão sem validar o tenant efetivo;
  - expor PDO/DSN para o Serviço ou Controller.
- Critérios de aceite:
  - `effectiveTenantId` resolve para o banco correto;
  - `db_name` externo não governa a operação;
  - conexão correta resolve sem mexer no schema do legado;
  - o serviço recebe apenas resultado de infraestrutura, não parâmetros internos do banco.
- Testes/validações obrigatórios:
  - `Tenant A` resolve para banco A;
  - `Tenant B` resolve para banco B;
  - `db_name` externo é rejeitado;
  - tenant inválido falha antes da conexão.
- Evidências esperadas:
  - fluxo de resolução validado em teste com isolamento entre tenants;
  - sem alteração de dados existentes.
- Rollback/confirmação:
  - a infraestrutura nova é isolada e não altera o `Database.php` legado; confirmação é a ausência de mudanças em código de produção e o teste de resolução controlada.

### T3-01-IMP06 — Prova de integração controlada

- ID: T3-01-IMP06
- Título: Criar prova mínima de integração controlada
- Objetivo: demonstrar o caminho mínimo e testável do estado legado conhecido para `TenantContext` e ao banco correto do tenant, sem migrar clientes, produtos ou vendas.
- Arquivos que podem ser criados:
  - testes de integração controlada;
  - fixtures mínimas de dois tenants de teste;
  - adaptadores de prova apenas na camada de compatibilidade
- Arquivos que podem ser modificados:
  - diretórios de testes e suporte de fixtures;
  - ajustes mínimos de configuração de ambiente de teste
- Arquivos proibidos:
  - `public/index.php` em fluxo de produção;
  - `app/Repository.php` como alvo de migração ampla;
  - `app/Database.php` reescrita complete;
  - qualquer migração de entidades reais do ERP
- Dependências:
  - T3-01-IMP03
  - T3-01-IMP04
  - T3-01-IMP05
- Implementação permitida:
  - fixture mínima com Tenant A e Tenant B;
  - criação de um teste que valida o caminho de entrada → contexto → resolução de banco;
  - uso de dados de teste isolados, sem tocar dados de produção.
- Implementação proibida:
  - migrar dados reais;
  - migrar clientes, produtos ou vendas;
  - escrever em schema de produção;
  - reutilizar contexto entre tenants.
- Critérios de aceite:
  - a prova cobre o caminho mínimo do sistema;
  - Tenant A e Tenant B são resolvidos em bancos distintos;
  - não há cruzamento de conexão ou vazamento de tenant.
- Testes/validações obrigatórios:
  - cenário A → banco A;
  - cenário B → banco B;
  - cenário de falha para tenant inválido.
- Evidências esperadas:
  - logs ou asserts demonstrando que a conexão corresponde ao tenant correto.
- Rollback/confirmação:
  - uso de banco de teste e fixtures isoladas; nenhuma mudança em produção e nenhum fluxo de negócio real alterado.

### T3-01-IMP07 — Validar isolamento A/B

- ID: T3-01-IMP07
- Título: Validar isolamento A/B
- Objetivo: verificar explicitamente que o contexto e a conexão do tenant não se misturam, nem mesmo em cenários de sessão divergente, tenant inválido ou `db_name` externo.
- Arquivos que podem ser criados:
  - testes de regressão focados em isolamento e segurança;
  - fixtures de tenant A e B
- Arquivos que podem ser modificados:
  - testes de isolamento e diretórios de apoio
- Arquivos proibidos:
  - `app/Repository.php` em refatoração ampla;
  - `app/Database.php` inteiro;
  - `public/index.php`;
  - qualquer script de migração, schema, seed ou produção
- Dependências:
  - T3-01-IMP06
- Implementação permitida:
  - testes automatizados instanciando o contexto de forma explícita;
  - validação da não-reutilização de contexto entre tenants;
  - assertion de rejeição de `db_name` externo e sessão divergente.
- Implementação proibida:
  - manter contexto global compartilhado;
  - permitir troca silenciosa de `effectiveTenantId`;
  - reutilizar uma conexão existente em operação errada.
- Critérios de aceite:
  - `tenant A` nunca usa conexão B;
  - `tenant B` nunca usa conexão A;
  - tenant inválido falha antes do acesso;
  - sessão divergente não sobrescreve o contexto validado;
  - `db_name` externo não governa o resultado.
- Testes/validações obrigatórios:
  - 2 tenants com banco distinto;
  - 1 tenant inválido;
  - 1 caso de sessão divergente;
  - 1 caso de `db_name` externo rejeitado;
  - 1 caso de contexto não reutilizado entre operações.
- Evidências esperadas:
  - testes negativos e positivos explicitando o isolamento do fluxo multi-tenant.
- Rollback/confirmação:
  - esta task fica somente na camada de prova e não modifica o comportamento de produção.

### T3-01-FUT01 — Investigar autorização do admin global [CONCLUÍDA]

- ID: T3-01-FUT01
- Título: Investigar e especificar autorização do admin global
- Objetivo: confirmar se o legado possui uma regra formal de autorização global e documentar a diferença entre comportamento legado e política arquitetural da nova fase.
- Arquivos que podem ser criados:
  - documentação de regra de autorização;
  - cenário de regra global e testes de decisão.
- Arquivos que podem ser modificados:
  - documentação e especificações futuras.
- Arquivos proibidos:
  - alterar regras de produção automáticas;
  - inventar autorização sem evidência;
  - transformar fallback legado em política permanente.
- Dependências:
  - T3-01-IMP04
- Implementação permitida:
  - registro documental da evidência; 
  - definição de regra futura explícita; 
  - manutenção do fluxo fora do escopo até a decisão final.
- Implementação proibida:
  - criar regra global sem análise;
  - usar `admin@localhost`, `tenant_id = 1`, `role = admin` isoladamente como prova de autorização global definitiva;
  - transformar fallback legado em regra nova.
- Critérios de aceite:
  - a ausência de regra formal de autorização global foi confirmada;
  - os fallbacks legados ficaram explicitamente classificados como comportamento de compatibilidade;
  - a arquitetura nova passou a exigir um modelo explícito de autorização.
- Testes/validações obrigatórios:
  - cenário de admin global legado;
  - cenário de usuário comum;
  - cenário de tenant inválido;
  - cenário de sessão/divergência sem autorização global implícita.
- Riscos:
  - confundir fallback de compatibilidade com política de produção;
  - permitir acesso administrativo implícito por regra de conveniência; 
  - misturar `UserTenant` do admin com `SelectedTenant` administrativo.
- Evidências esperadas:
  - documentação de que o legado usa compatibilidade e fallback, não política formal;
  - definição explícita da decisão de produto para a arquitetura nova.
- Estado final desta task:
  - concluída como investigação e registro documental;
  - não implementa autorização nem altera runtime.

### T3-01-FUT01A — Seleção segura de empresa cadastrada

- ID: T3-01-FUT01A
- Título: Seleção segura de empresa cadastrada
- Objetivo: permitir que um administrador da plataforma selecione uma empresa/tenant cadastrado de forma explícita, validada e autorizada, sem alterar o `UserTenant` natural do administrador nem usar `db_name` externo como fonte de verdade.
- Dependências:
  - T3-01-FUT01
- Implementação permitida:
  - receber um `SelectedTenant` validado;
  - verificar existência do tenant cadastrado;
  - exigir autorização administrativa explícita;
  - separar a seleção da empresa do `UserTenant` do admin;
  - produzir um contexto administrativo independente do `TenantContext` do usuário comum.
- Implementação proibida:
  - aceitar tenant arbitrário;
  - aceitar `db_name` externo como parâmetro de decisão;
  - trocar silenciosamente `$_SESSION['tenant_id']` como mutação de contexto;
  - atribuir `tenant_id` da empresa ao usuário admin;
  - transformar a seleção em “admin pertence ao tenant”.
- Critérios de aceite:
  - a empresa/tenant selecionado precisa existir e ser autorizado;
  - a escolha da empresa não altera `AuthenticatedAdmin` nem `UserTenant`;
  - `$_SESSION['tenant_id']` não é usado como mecanismo de troca silenciosa;
  - a operação fica em contexto administrativo explícito.
- Testes:
  - tenant existente e autorizado;
  - tenant inexistente;
  - tenant não autorizado;
  - tenant informado por `db_name` externo;
  - sessão com tenant divergente, sem sobrescrita.
- Riscos:
  - abuso de fallback de sessão;
  - mistura entre contexto empresarial e contexto administrativo;
  - confusão de tenant alvo com tenant natural do usuário.
- Evidências esperadas:
  - `SelectedTenant` validado e autorizado;
  - `AuthenticatedAdmin` preservado;
  - ausência de mutação silenciosa de `$_SESSION['tenant_id']`.

### T3-01-FUT01B — Contexto administrativo da empresa selecionada

- ID: T3-01-FUT01B
- Título: Contexto administrativo da empresa selecionada
- Objetivo: representar de forma explícita a combinação `AuthenticatedAdmin + SelectedTenant autorizado`, sem fundir isso ao `TenantContext` natural do usuário comum.
- Dependências:
  - T3-01-FUT01A
- Implementação permitida:
  - criação de um `AdministrativeContext` separado do `TenantContext` natural;
  - produção de um `EffectiveTenant` apenas quando uma operação tenant-scoped exigir; 
  - manter o admin como usuário administrativo, sem torná-lo membro natural do tenant.
- Implementação proibida:
  - usar `SelectedTenant` como substituto de `UserTenant` do admin;
  - fazer o admin “pertencer” ao tenant administrado;
  - reusar `db_name` da UI como fonte da operação;
  - transformar a sessão em contexto atual da operação.
- Critérios de aceite:
  - o contexto administrativo é explícito e separado do tenant do usuário;
  - o tenant admin natural continua distinto do tenant administrado;
  - operações tenant-scoped podem produzir `EffectiveTenant` validado sem alterar identidade do admin.
- Testes:
  - admin autenticado sem tenant natural;
  - admin autenticado com tenant admin e empresa selecionada;
  - operação em tenant-scoped com `EffectiveTenant` validado;
  - operação sem seleção explícita deve falhar.
- Riscos:
  - erosão de separação de papéis;
  - uso de sessão para esconder o contexto real;
  - criação de `EffectiveTenant` irrestrito.
- Evidências esperadas:
  - `AdministrativeContext` distinto do `TenantContext` natural;
  - `EffectiveTenant` gerado apenas em operação explícita e validada.

### T3-01-FUT02 — Implementar criação de usuário para TargetTenant explícito

- ID: T3-01-FUT02
- Título: Implementar criação de usuário para tenant explícito
- Objetivo: criar usuário para uma empresa/tenant alvo explicitamente selecionado e autorizado pela administração, sem depender de troca temporária da sessão atual.
- Dependências:
  - T3-01-FUT01B
- Implementação permitida:
  - `AuthenticatedAdmin + TargetTenant explícito e autorizado + dados do novo usuário` → criação do usuário da empresa;
  - persistir associação explícita do usuário ao tenant correto.
- Implementação proibida:
  - trocar `$_SESSION['tenant_id']` para criar usuário;
  - restaurar a sessão depois da criação como workaround;
  - criar usuário usando contexto implícito.
- Critérios de aceite:
  - o novo usuário é criado para o tenant explícito informado;
  - o tenant alvo foi validado e autorizado;
  - o admin permanece separado do `UserTenant` do tenant administrado.
- Testes:
  - criação com tenant válido;
  - criação com tenant inválido;
  - criação sem tenant explícito;
  - criação em ambiente com sessão divergente.
- Riscos:
  - mutação de sessão em operação de criação;
  - associação de usuário ao tenant errado;
  - entrada de tenant arbitrário.
- Evidências esperadas:
  - registro do `tenant_id` correto no novo usuário;
  - ausência de troca de sessão em runtime.

### T3-01-FUT03 — Eliminar necessidade de trocar temporariamente `$_SESSION['tenant_id']`

- ID: T3-01-FUT03
- Título: Eliminar troca temporária de tenant pela sessão
- Objetivo: remover o padrão legado de reusar `$_SESSION['tenant_id']` como mecanismo temporário para criação de usuário e operações de empresa.
- Dependências:
  - T3-01-FUT02
- Implementação permitida:
  - contexto explícito de `TargetTenant`;
  - operação de criação sem mutar a sessão compartilhada.
- Implementação proibida:
  - alterar a sessão temporariamente para simular contexto de tenant;
  - depender de `$_SESSION['tenant_id']` como troca de contexto oculto.
- Critérios de aceite:
  - criação e operação do usuário não exigem troca de `$_SESSION['tenant_id']`;
  - o contexto do alvo fica em objeto/estrutura explícita.
- Testes:
  - criação sem mutação de sessão;
  - operação em tenant alvo sem sobrescrita da sessão atual;
  - recuperação em caso de falha sem estado residual.
- Riscos:
  - efeito colateral em sessão global;
  - erro de concorrência por contexto compartilhado.
- Evidências esperadas:
  - ausência de escrita em `$_SESSION['tenant_id']` durante a operação de criação.

### T3-01-FUT04 — Associar corretamente usuário ↔ tenant

- ID: T3-01-FUT04
- Título: Associar usuário ao tenant correto
- Objetivo: garantir associação explícita e consistente entre o novo usuário e o tenant correto, sem depender de ambiguidades em `company_id` e sessão.
- Dependências:
  - T3-01-FUT02
  - T3-01-FUT03
- Implementação permitida:
  - `novo usuário -> tenant_id correto`; 
  - manter `company_id` apenas como compatibilidade enquanto o schema não for migrado.
- Implementação proibida:
  - misturar `company_id` e `tenant_id` como fonte de verdade;
  - ocultar associação em fallback de sessão;
  - normalizar schema físico antes da tarefa específica.
- Critérios de aceite:
  - cada novo usuário possui associação consistente ao tenant alvo;
  - `company_id` não substitui `tenant_id` para decisões de escopo;
  - a associação fica explícita e testável.
- Testes:
  - criação de usuário para tenant X;
  - leitura do `tenant_id` persistido;
  - cenário de compatibilidade com `company_id` legado;
  - caso de tenant divergente.
- Riscos:
  - ambiguidade entre schemas antigos e novos;
  - uso de `company_id` sem compatibilidade clara.
- Evidências esperadas:
  - registro persistido de `tenant_id` correto;
  - explicitação do papel de `company_id` como compatibilidade.

### T3-01-FUT05 — Validar login do usuário da empresa

- ID: T3-01-FUT05
- Título: Validar login do usuário da empresa
- Objetivo: provar que um usuário criado para o tenant X, ao autenticar, produz `UserTenant = X`, `EffectiveTenant = X` e ausência de contexto cruzado.
- Dependências:
  - T3-01-FUT04
  - T3-01-IMP07
- Implementação permitida:
  - validação de login por tenant explícito;
  - construção do `UserTenant` a partir do usuário autenticado;
  - produção de `EffectiveTenant` validado para operações do tenant.
- Implementação proibida:
  - reescrever autenticação global do sistema;
  - transformar login legado em regra nova antes da fundação;
  - deixar login sem associação ao tenant alvo.
- Critérios de aceite:
  - usuário criado para tenant X faz login com `UserTenant = X`;
  - `EffectiveTenant` coincide com X em operação tenant-scoped;
  - não há vazamento para outro tenant.
- Testes:
  - login do usuário do tenant X;
  - login em tenant Y não autorizado;
  - login de usuário sem associação; 
  - recuperação de `EffectiveTenant` após autenticação.
- Riscos:
  - autenticação sem validação explícita do tenant;
  - login usando sessão residual.
- Evidências esperadas:
  - `UserTenant` e `EffectiveTenant` consistentes com o tenant X.

### T3-01-FUT06 — Acessar ERP usando banco dedicado daquela empresa

- ID: T3-01-FUT06
- Título: Acessar ERP usando banco dedicado da empresa
- Objetivo: provar que um usuário do tenant X consegue operar no ERP da empresa X usando `TenantContext X`, `TenantConnectionResolver` e banco dedicado X, sem misturar com outro tenant.
- Dependências:
  - T3-01-FUT05
  - T3-01-IMP07
- Implementação permitida:
  - fluxo real do usuário autenticado para o banco dedicado do tenant X;
  - uso de `TenantContext` e `TenantConnectionResolver` de forma controlada;
  - integração da fundação multi-tenant ao ERP da empresa selecionada.
- Implementação proibida:
  - migrar clientes, produtos, vendas, repository ou `Database` inteiro nesta etapa;
  - alterar schema e dados existentes sem task específica;
  - fazer o ERP operar em banco de outro tenant.
- Critérios de aceite:
  - usuário do tenant X acessa apenas o ERP e o banco do tenant X;
  - `TenantContext X` leva à conexão correta;
  - o fluxo é concluído sem contaminação por tenant Y.
- Testes:
  - usuário tenant X → banco X;
  - usuário tenant Y → banco Y;
  - cenário de falha com tenant inválido;
  - cenário de sessão divergente sem sobrescrita.
- Riscos:
  - conexão errada em runtime;
  - contaminação por estado global em `Database`;
  - uso de sessão para driblar a resolução explícita.
- Evidências esperadas:
  - a operação usa o banco dedicado do tenant correto;
  - o ERP da empresa X fica isolado do tenant Y.

## Ordem obrigatória da sequência FUT

1. T3-01-FUT01 — concluída
2. T3-01-FUT01A — seleção segura de empresa cadastrada
3. T3-01-FUT01B — contexto administrativo da empresa selecionada
4. T3-01-FUT02 — criação de usuário para `TargetTenant` explícito
5. T3-01-FUT03 — remover troca temporária de `$_SESSION['tenant_id']`
6. T3-01-FUT04 — associar usuário ↔ tenant corretamente
7. T3-01-FUT05 — validar login do usuário da empresa
8. T3-01-FUT06 — acessar ERP usando banco dedicado da empresa

## Decisão de produto registrada para a arquitetura nova

- O legado não possui política formal de autorização global.
- `admin@localhost`, senha/fallback administrativo legado, `role = admin` e `tenant_id = 1` continuam sendo comportamento/fallback legado, não regra permanente.
- A nova arquitetura adota o conceito de `PlatformAdmin` para a fase atual.
- `PlatformAdmin` pode selecionar uma empresa cadastrada e administrar a plataforma, mas não se torna membro natural do tenant administrado.
- `AuthenticatedAdmin`, `SelectedTenant`, `AdministrativeContext` e `EffectiveTenant` devem permanecer separadamente modelados.
- A identidade definitiva de `PlatformAdmin` permanece pendente de implementação e deve ser especificada em uma etapa posterior.

## Restrições práticas desta fase

- não implementar código;
- não alterar banco, schema, autenticação, sessão ou dados;
- não executar FUT01A, FUT01B, FUT02 ou tarefas posteriores;
- não apagar ou renumerar tasks já existentes sem necessidade.

## Fechamento do backlog FUT

- FUT01 foi concluída como investigação documental e não como regra de produção.
- FUT01A e FUT01B foram introduzidas para manter a seleção/administração dos tenants em um modelo explícito e separado do `TenantContext` comum.
- A sequência atual foi reordenada para seguir a regra obrigatória acima, sem alterar a estrutura principal do backlog nem renumerar tarefas existentes.

---

## Dependências da sequência principal

A primeira sequência executável de implementação deve ser:

1. T3-01-IMP01 — TenantContext mínimo e imutável
2. T3-01-IMP02 — Entrada normalizada do legado
3. T3-01-IMP03 — LegacyContextAdapter mínimo
4. T3-01-IMP04 — TenantContextResolver mínimo
5. T3-01-IMP05 — TenantConnectionResolver mínimo
6. T3-01-IMP06 — Prova de integração controlada
7. T3-01-IMP07 — Validar isolamento A/B

A primeira task de implementação é explicitamente:

- T3-01-IMP01 — TenantContext mínimo e imutável

Motivo:
- ela é a menor prova executável da arquitetura nova;
- ela não integra ainda com HTTP, session, banco ou Repository;
- ela valida a fundação sem mexer no comportamento legado.

## Preservação das tasks concluídas

As tasks documentais/investigativas já concluídas continuam preservadas e inalteradas:

- T3-01-I01 — fluxo real de login/empresa/tenant
- T3-01-I02 — regra operacional de tenant
- T3-01-I03 — especificação do TenantContext
- T3-01-I04 — fronteira legado → TenantContext
- T3-01-I05 — resolução segura do banco por tenant

Elas não foram renumeradas, reescritas nem apagadas. A nova seção apenas adiciona backlog de implementação da fundação multi-tenant sem mexer na cadeia documental existente.

## Restrição desta execução

- nenhuma task de implementação foi executada;
- nenhum código foi implementado;
- nenhum arquivo de produção foi alterado;
- o legado permaneceu intacto;
- a arquitetura nova foi registrada somente como backlog e desenho de progressão incremental.

---

## Resumo executivo do backlog

A sequência de implementação desce da menor unidade possível de contexto para a resolução mínima do banco e a prova controlada de isolamento. A primeira task executável é a criação do `TenantContext` mínimo e imutável, seguida pela normalização da borda, do adapter, do resolver e da infraestrutura de conexão. Somente após essas provas é que o backlog passa para o fluxo de usuário, autorização e integração real ao ERP.

### T3-02 — Migrar entidades e regras de negócio
Status: pendente

Itens planejados:
- `Clientes`
- `Produtos`
- `Vendas`
- `Fornecedores`
- `Motoristas`
- `Transportadoras`
- `CFOPs`
- `Usuários`
- `Empresas / Tenants`

### T3-03 — Validar fluxo de autenticação e tenant
Status: pendente

Itens planejados:
- Revisar login e sessão
- Padronizar tenant atual
- Garantir conexão correta por banco do tenant
- Validar compatibilidade de dados antigos

### T3-04 — Reorganizar views e frontend
Status: pendente

Itens planejados:
- Reduzir lógica visível em `public/index.php`
- Separar renderização e regras de apresentação
- Modularizar componentes e templates

---

## Pendências e próximos passos

### Críticos
- Validar login do sistema em ambiente real
- Testar fluxo de empresa/tenant completo
- Confirmar permissões e usuários admin
- Validar dashboard e telas principais com banco restaurado

### Recomendados
- Exportar backups de segurança do banco
- Registrar snapshot do ambiente funcional atual
- Documentar convenções da arquitetura nova
- Definir backlog de migração em tarefas menores por módulo

---

## Arquivos e artefatos gerados

- `mini-erp-web/docs/f0-t01-estado-atual-mini-erp.md`
- `mini-erp-web/scripts/recover_aria.ps1`
- `mini-erp-web/docs/roadmap-projeto.md`

---

## Observações finais

O projeto avançou de um estado de falha operacional para um ambiente funcional local, com MySQL restaurado e servidor PHP rodando. O próximo grande passo é a migração arquitetural em camadas, mantendo o sistema legado estável enquanto a nova estrutura é organizada e validada.
