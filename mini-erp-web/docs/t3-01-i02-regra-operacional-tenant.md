# T3-01-I02 — Regra operacional entre tenant autenticado, tenant selecionado e tenant efetivo

## Objetivo

Definir a regra operacional que a nova arquitetura deverá aplicar para distinguir, com clareza e segurança,

- usuário autenticado;
- tenant de origem do usuário;
- tenant selecionado pelo administrador global;
- tenant efetivo da operação;
- banco efetivo da operação;
- `company_id` legado;
- `current_company_id` legado.

Esta task é de investigação + definição documental. Não altera comportamento atual, não altera produção, não altera banco, não altera schema, não implementa TenantContext nem qualquer correção.

## Base documental utilizada

- [mini-erp-web/docs/t3-01-i01-fluxo-real-login-empresa-tenant.md](t3-01-i01-fluxo-real-login-empresa-tenant.md)
- [mini-erp-web/docs/f0-t01-estado-atual-mini-erp.md](f0-t01-estado-atual-mini-erp.md)
- [mini-erp-web/docs/roadmap-projeto.md](roadmap-projeto.md)
- [mini-erp-web/public/index.php](../public/index.php)
- [mini-erp-web/public/login.php](../public/login.php)
- [mini-erp-web/app/Repository.php](../app/Repository.php)
- [mini-erp-web/app/Database.php](../app/Database.php)

## Decisão de arquitetura a partir do baseline

O código legado confirmou que existem pelo menos três estados distintos em uso:

1. tenant autenticado;
2. tenant selecionado;
3. tenant efetivo do banco.

A arquitetura nova deve tratar essas noções como conceitos diferentes e não permitir que uma delas substitua as outras implicitamente.

A direção conceitual futura deve ser:

identidade/contexto
→ EffectiveTenant
→ resolução de infraestrutura
→ EffectiveDatabase

e não:

banco atual
→ descobrir tenant.

## Regra operacional definida

### Regra geral

A arquitetura nova deve considerar que o tenant efetivo da operação nunca pode ser inferido apenas pelo banco atual ou pela sessão sem validação.

A operação deve seguir esta sequência:

1. identificar a identidade autenticada (`AuthenticatedUser`);
2. resolver o tenant natural do usuário (`UserTenant`);
3. validar autorização do usuário para operar naquele conjunto de tenants;
4. se houver seleção explícita de tenant, capturar `SelectedTenant` e validar se ele está dentro do conjunto permitido;
5. decidir `EffectiveTenant` após validação;
6. resolver `EffectiveDatabase` a partir do `EffectiveTenant`;
7. rejeitar a operação se qualquer um dos valores estiver inconsistente.

## Definição dos conceitos

### 1. AuthenticatedUser

`AuthenticatedUser` representa quem o sistema reconhece como usuário autenticado naquele request.

Definição operacional:

- identidade confirmada pelo fluxo de autenticação;
- deve conter usuário, identidade e estado de autenticação;
- não deve decidir sozinho qual banco será utilizado;
- não deve ser confundido com tenant efetivo;
- deve permanecer separado de qualquer contexto de operação.

Observação:

No legado, o valor equivalente é `$_SESSION['user_id']`, mas esse dado é apenas uma referência de sessão e não pode ser usado como fonte única para definir tenant efetivo.

### 2. UserTenant

`UserTenant` representa o tenant ao qual o usuário autenticado está efetivamente associado no dado persistido do sistema.

Evidência atual que representa essa associação:

- `usuarios.tenant_id` quando presente;
- `usuarios.company_id` como compatibilidade legada;
- `tenants` como tabela de referência do tenant;
- `$_SESSION['tenant_id']` como sessão legada, apenas como entrada compatível e não como fonte canônica.

Regra operacional futura:

- `UserTenant` deve ser derivado de dados persistidos e autenticados, nunca de `$_POST`, `$_GET`, slug sem validação, nome de banco ou sessão sem checagem.
- `company_id` pode servir como ponte para compatibilidade, mas não deve ser a fonte canônica da arquitetura nova.
- `tenant_id` é o identificador canônico da arquitetura nova.

### 3. SelectedTenant

`SelectedTenant` representa um tenant selecionado explicitamente para uma operação administrativa, por exemplo um administrador global escolheu uma empresa/tenant da lista.

Característica:

- é uma seleção explícita de contexto de execução;
- pode diferir do `UserTenant` do usuário autenticado;
- não equivale ao tenant natural do usuário;
- deve ser validado antes de virar `EffectiveTenant`.

### 4. EffectiveTenant

`EffectiveTenant` é o tenant que efetivamente governa a operação atual de negócio.

Regra:

- é o único valor que deve ser usado como contexto de execução da operação;
- deve ser determinado por validação e autorização, não por `$_SESSION` cru ou por `Database::$tenantDbName`;
- para usuário comum, normalmente equaliza ao `UserTenant`;
- para administrador global, pode ser o `SelectedTenant` somente se a autorização e a seleção estiverem validadas.

### 5. EffectiveDatabase

`EffectiveDatabase` é o banco físico resolvido a partir do `EffectiveTenant`.

Regra:

- o banco deve ser uma consequência de `EffectiveTenant`;
- o nome de banco nunca deve ser fonte de verdade para decidir tenant;
- o banco deve ser obtido por infra, não por inferência a partir do banco atual.

## Relação entre esses conceitos

Representação conceitual:

- `AuthenticatedUser` = identidade do usuário logado;
- `UserTenant` = tenant associado ao usuário;
- `SelectedTenant` = contexto selecionado explicitamente;
- `EffectiveTenant` = tenant validado que governa a operação;
- `EffectiveDatabase` = banco resolvido para o `EffectiveTenant`.

Fluxo recomendado:

AuthenticatedUser
→ UserTenant (dados persistidos do usuário)
→ autorização
→ SelectedTenant (se houver escolha explícita)
→ EffectiveTenant (após validação)
→ EffectiveDatabase (resolução de infraestrutura)

## Regras de precedência da arquitetura nova

A regra não deve copiar a precedência do legado. A precedência deve refletir identidade, autorização, seleção e execução.

### Tabela de precedência

| Fonte | Papel | Pode definir EffectiveTenant? | Precisa validação? | Pode ser alterado durante request? | Observação |
|---|---|---:|---:|---:|---|
| `AuthenticatedUser` | identidade | Não | Sim | Não | representa quem está autenticado |
| `UserTenant` | tenant natural do usuário | Não diretamente | Sim | Não sem reautenticação | origem persistida do usuário |
| `SelectedTenant` | escolha explícita | Só após validação | Sim | Sim, se houver nova seleção explícita | deve ser tratado como contexto operacional |
| `EffectiveTenant` | execução | Sim | Sim | Sim, só dentro da operação após validação explícita | única fonte executiva |
| `EffectiveDatabase` | infraestrutura | Não | Sim | Não, como consequência da regra | nunca é fonte de verdade do tenant |
| `$_SESSION['tenant_id']` | legado/compatibilidade | Não como regra oficial | Sim | Sim | entrada compatível, não fonte canônica |
| `$_POST['tenant_id']` | entrada do cliente | Não | Sim, sempre | Sim | nunca suficiente sozinho |
| `company_id` | compatibilidade legada | Não sem tradução validada | Sim | Sim | não é canônico |
| `slug` não validado | entrada do cliente | Não | Sim | Sim | apenas identificador bruto |
| nome de banco / DSN | infraestrutura | Não | Sim | Não por regra de negócio | não deve determinar tenant |

## Regras por cenário

### Cenário A — Usuário comum de uma empresa

Exemplo: usuário pertence ao Tenant 5.

Regra:

- `AuthenticatedUser` = usuário autenticado;
- `UserTenant` = 5, derivado de `usuarios.tenant_id` ou, em compatibilidade, de `usuarios.company_id` validado;
- `SelectedTenant` = normalmente nulo para operação comum;
- `EffectiveTenant` = 5;
- `EffectiveDatabase` = banco mapeado para tenant 5.

Comportamento esperado:

- o usuário não deve poder selecionar outro tenant arbitrariamente;
- se a sessão disser tenant 3, o sistema deve tratar isso como entrada legada inconsistente, não como verdade;
- se o banco corrente estiver configurado para tenant 3, essa combinação deve ser rejeitada ou tratada como erro de infraestrutura/inconsistência;
- a operação deve falhar em segurança antes de continuar, e não “aceitar o banco atual como autoridade”.

### Cenário B — Administrador global

O administrador global visualiza lista de empresas e pode selecionar uma empresa.

Regra:

- `AuthenticatedUser` continua sendo o administrador autenticado;
- `UserTenant` pode ser nulo, ou um tenant de origem da conta, se a identidade global tiver um tenant natural;
- `SelectedTenant` = empresa escolhida explicitamente na UI;
- `EffectiveTenant` = `SelectedTenant` apenas quando a seleção é explicitamente autorizada e validada;
- `EffectiveDatabase` = banco do tenant selecionado.

Validação obrigatória antes de trocar contexto:

- verificar quem é o usuário autenticado;
- verificar autorização explícita para operar naquele tenant;
- verificar que a seleção foi feita explicitamente por uma ação administrativa;
- verificar que o tenant escolhido existe e está válido;
- verificar que o banco correspondente não está inconsistente com o tenant.

Se a autorização do admin global não estiver claramente definida no código, a regra correta é:

DECISÃO PENDENTE DE AUTORIZAÇÃO

### Cenário C — Usuário comum tentando acessar outro tenant

Regra:

- `UserTenant = A`;
- `SelectedTenant = B`;
- o sistema deve rejeitar qualquer operação em que o contexto de execução seja ambíguo ou conflite com o tenant natural do usuário;
- a política deve priorizar isolamento e rejeição em vez de “usar o último valor da sessão”.

Resultado esperado:

- para usuário comum, `SelectedTenant` só pode ser o mesmo `UserTenant` e não deve ser aceito como override arbitrário;
- operação com `UserTenant != SelectedTenant` deve ser rejeitada ou tratada como tentativa de acesso cruzado.

### Cenário D — Sessão inconsistente

Exemplo:

- usuário pertence ao tenant A;
- `$_SESSION['tenant_id'] = B`.

Regra:

- a sessão deve ser tratada como entrada legado não confiável;
- a arquitetura nova não deve usar a sessão como fonte única de `EffectiveTenant`;
- o sistema deve resolver o que é canônico pela identidade e pelos dados persistidos;
- se a sessão divergir do `UserTenant` e não houver seleção administrativa explícita e validada, a operação deve ser rejeitada.

### Cenário E — Banco inconsistente

Exemplo:

- `EffectiveTenant = A`;
- `Database::$tenantDbName` aponta para banco B.

Regra:

- essa condição deve ser tratada como inválida;
- o banco deve ser considerado detalhe de infraestrutura derivado do tenant efetivo;
- se o banco não corresponde ao `EffectiveTenant`, a operação deve ser recusada antes de qualquer uso de dados.

### Cenário F — Criação de usuário por empresa

O legado atua temporariamente mutando a sessão para associar o usuário ao tenant da empresa. Isso deve ser formalizado de forma separada na arquitetura nova.

Regra conceitual:

- a operação não deve depender de mutação temporária da sessão;
- a ação deve operar sobre um `TargetTenant` explicitamente identificado;
- esse `TargetTenant` pode ser um `SelectedTenant` da operação administrativa; 
- somente depois de validado e autorizado ele pode virar `EffectiveTenant` da operação;
- o administrador autenticado não deve ser confundido com o tenant que está sendo alvo da operação.

Decisão conceitual recomendada:

- usar `TargetTenant` como conceito de operação de criação/associação, se for uma ação administrativa;
- manter `SelectedTenant` para explicitamente dizer que a operação está sendo executada no contexto de uma empresa específica;
- garantir que o tenant do administrador autenticado não seja automaticamente usado como alvo da operação.

Isso impede que o “tenant alvo da administração” seja confundido com “tenant autenticado do administrador”.

## Regras de segurança

Valores vindos diretamente de entrada do cliente ou de sessão não devem ser aceitos como fonte suficiente de `EffectiveTenant`.

Os valores abaixo não podem ser usados como fonte suficiente por si só:

- `$_POST['tenant_id']`;
- `$_GET['tenant_id']`;
- `company_id` arbitrário de formulário;
- slug não validado;
- sessão sem validação;
- nome de banco;
- DSN.

Esses valores podem ser usados apenas como entrada de identificação/seleção e devem ser validados contra:

- identidade do usuário autenticado;
- autorização do usuário;
- dados persistidos conhecidos do sistema;
- resolução do tenant a partir de dados confiáveis.

## `company_id` versus `tenant_id`

Decisão já aprovada e confirmada pela arquitetura alvo:

- `tenant_id` é o identificador canônico da arquitetura nova;
- `company_id` é compatibilidade legada;
- não alterar schema nesta task;
- não remover `company_id`;
- não migrar dados;
- não transformar os dois nomes em conceitos de domínio diferentes sem evidência.

Onde a compatibilidade deve terminar:

- na fronteira de compatibilidade / adapter, a tradução entre `company_id` e `tenant_id` deve acontecer;
- fora dessa borda, o código novo deve operar apenas com `tenant_id` como conceito canônico.

Onde começa o conceito canônico:

- na lógica de domínio e na resolução de contexto, a arquitetura nova deve usar `tenant_id`;
- `company_id` deve ser tratado como legado e adaptado para `tenant_id` somente de forma explícita.

## Fallback tenant 1

O fallback `1` existe no legado em vários pontos, mas não apresenta uma semântica única clara no código.

Evidência atual:

- login atribui `$_SESSION['tenant_id'] = 1` quando não há tenant do usuário em [mini-erp-web/public/index.php](../public/index.php#L503-L505);
- `requireTenantId()` retorna `1` para admin principal quando a validação falha ou o usuário é `admin@localhost`/role admin em [mini-erp-web/app/Repository.php](../app/Repository.php#L112-L124);
- isso mostra que o valor `1` serve como default de desenvolvimento/compatibilidade e também como suporte para admin global.

Classificação possível:

- tenant real: sim, em alguns ambientes pode representar um tenant de fato existente;
- empresa default: possível, mas não confirmado;
- ambiente de desenvolvimento: sim, também parece ser usado como fallback de dev;
- compatibilidade histórica: sim, claramente há compatibilidade no código;
- admin global: sim, em algum uso de admin global;
- fallback de erro: sim, em cenários de ausência de tenant ou validação fracassada.

Conclusão:

O fallback tenant `1` não pode ser transformado em regra oficial da arquitetura nova por suposição.

Classificação final recomendada:

FALLBACK NÃO CLASSIFICADO

Motivo:

- o código usa `1` em múltiplas funções diferentes;
- o significado depende do contexto do usuário e do tipo de operação;
- não existe evidência suficiente para afirmar que `1` seja um tenant semântico oficial de produção.

## Matriz de conceitos

| Conceito | Fonte | Pode definir EffectiveTenant? | Precisa validação? | Pode ser alterado durante request? | Observação |
|---|---|---:|---:|---:|---|
| `AuthenticatedUser` | autenticação | Não | Sim | Não | identidade do usuário logado |
| `UserTenant` | usuário persistido | Não diretamente | Sim | Não sem nova associação | tenant natural do usuário |
| `SelectedTenant` | seleção explícita | Só após validação | Sim | Sim | contexto administrativo |
| `EffectiveTenant` | validação + autorização | Sim | Sim | Sim, apenas por decisão explícita da operação | único tenant executivo |
| `EffectiveDatabase` | infraestrutura | Não | Sim | Não | consequência do tenant efetivo |
| `company_id` | legado | Não sem tradução | Sim | Sim | compatibilidade |
| `current_company_id` | sessão legado | Não | Sim | Sim | compatibilidade e estado de UI |
| `$_SESSION['tenant_id']` | legado | Não como regra oficial | Sim | Sim | entrada compatível |

## Matriz de cenários

| Usuário | UserTenant | SelectedTenant | Sessão | Banco atual | Resultado esperado |
|---|---|---|---|---|---|
| comum de empresa | A | nulo | A | A | operação aceitada |
| comum de empresa | A | nulo | B | A | rejeitar por inconsistência de sessão |
| comum de empresa | A | nulo | A | B | rejeitar por inconsistência de infraestrutura |
| comum de empresa | A | B | A | A | rejeitar: usuário comum não deve trocar tenant |
| administrador global | pode ser nulo ou A | B | A | B | aceitar somente após autorização explícita e validação |
| admin global sem autorização clara | nulo ou A | B | qualquer | qualquer | DECISÃO PENDENTE DE AUTORIZAÇÃO |
| tenant A para tenant B | A | B | A ou B | qualquer | rejeitar: ambiguidade e isolamento violado |
| criação de usuário por empresa | admin autenticado | tenant alvo | mutável temporariamente | qualquer | rejeitar uso de sessão como fonte de contexto; usar TargetTenant/SelectedTenant validado |

## Cenários que devem ser rejeitados

As seguintes condições devem resultar em rejeição ou falha explícita:

- `UserTenant` e `SelectedTenant` divergirem em operação de usuário comum;
- `UserTenant` e `$_SESSION['tenant_id']` divergirem sem seleção administrativa explícita;
- `EffectiveTenant` e `EffectiveDatabase` divergem;
- `SelectedTenant` não pertencer ao conjunto de tenants autorizados para o usuário;
- `company_id`/`slug`/`session` forem usados sem validação como fonte de `EffectiveTenant`;
- operação de criação/associação de usuário depender de mutação temporária da sessão como fonte de verdade;
- qualquer operação em que o banco atual seja tratado como fonte do tenant.

## Decisões ainda não confirmadas

- qual é exatamente a regra de autorização do administrador global para operar em qualquer tenant;
- se um admin global deve ter um `UserTenant` próprio ou se ele é exclusivamente uma identidade global sem tenant natural;
- se `TargetTenant` deve ser introduzido como conceito explícito na arquitetura nova ou se `SelectedTenant` basta para esse caso;
- se tenant `1` representa um tenant real, um ambiente de dev, ou apenas um fallback legacy em produção.

Esses pontos devem permanecer marcados como:

DECISÃO PENDENTE DE AUTORIZAÇÃO

ou

NÃO CONFIRMADO

## Impacto esperado na futura composição do TenantContext

A arquitetura nova deve fazer o seguinte:

- receber como entrada a identidade autenticada e dados legados;
- transformar entrada legada em um `TenantContext` explícito e validado;
- garantir que o `TenantContext` represente o `EffectiveTenant` final, e não o banco, a sessão ou o valor bruto enviado por formulário;
- manter o banco em infraestrutura, de forma isolada da regra de negócio;
- impedir que `$_SESSION` e `Database::$tenantDbName` funcionem como fontes de verdade paralelas.

Em termos de composição esperada:

- `TenantContext` deve carregar `AuthenticatedUser`;
- `TenantContext` deve carregar `EffectiveTenant` validado;
- `TenantContext` deve ser usado para resolver `EffectiveDatabase`;
- `TenantContext` não deve se basear em `Database::$tenantDbName` como entrada do domínio.

## Conclusão operacional

A regra operacional definida para a nova arquitetura é:

1. identidade autenticada primeiro;
2. tenant natural do usuário em segundo lugar;
3. seleção explícita somente quando houver autorização explícita;
4. `EffectiveTenant` como único contexto de execução;
5. `EffectiveDatabase` como consequência, nunca como origem do tenant;
6. `company_id` como compatibilidade legada e `tenant_id` como canônico;
7. rejeição explícita de inconsistências entre usuário, sessão e banco;
8. sem depender de mutação temporária da sessão para criar associação de usuário por empresa.

## Arquivos criados

- [mini-erp-web/docs/t3-01-i02-regra-operacional-tenant.md](t3-01-i02-regra-operacional-tenant.md)

## Arquivos modificados

- Nenhum arquivo de produção foi alterado.
- Nenhum arquivo em [mini-erp-web/public](../public), [mini-erp-web/app](../app), [mini-erp-web/config.php](../config.php), [mini-erp-web/database](../database) foi modificado.

## Validações realizadas

- leitura dos documentos de diagnóstico e baseline;
- revisão de [mini-erp-web/public/index.php](../public/index.php);
- revisão de [mini-erp-web/public/login.php](../public/login.php);
- revisão de [mini-erp-web/app/Repository.php](../app/Repository.php);
- revisão de [mini-erp-web/app/Database.php](../app/Database.php);
- comparação com a arquitetura alvo em [mini-erp-web/docs/roadmap-projeto.md](roadmap-projeto.md).

## Confirmação final

- Nenhum código de produção foi alterado.
- Nenhum banco foi alterado.
- Nenhuma migration foi executada.
- Nenhum seed foi executado.
- Nenhuma correção foi aplicada.
- Nenhuma implementação de TenantContext foi feita.
- T3-01-I03 não foi executada.
