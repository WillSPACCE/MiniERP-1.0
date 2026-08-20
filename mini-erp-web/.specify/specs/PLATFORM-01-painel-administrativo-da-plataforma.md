# Feature Specification: PLATFORM-01 — Painel Administrativo da Plataforma

**Feature Branch**: `PLATFORM-01-painel-administrativo-da-plataforma`

**Created**: 2026-08-20

**Status**: Clarified and approved for v1

**Input**: Product and architecture decisions clarified for the platform administrative control-plane.

## User Scenarios & Testing

### User Story 1 - Plataforma administra empresas e tenants (Priority: P1)

O PlatformAdmin, como identidade administrativa própria da plataforma, deve conseguir acessar o painel administrativo e administrar o lifecycle de empresas/tenants sem se tornar membro natural do tenant administrado.

**Why this priority**: A governança do control-plane precisa ser clara, isolada e auditável antes das próximas etapas de provisionamento e suporte.

**Independent Test**: A ação pode ser validada pela existência de perfil/identidade PlatformAdmin, seleção de tenant autorizado e operação sob contexto administrativo explícito.

**Acceptance Scenarios**:

1. **Given** uma identidade PlatformAdmin válida no control-plane, **When** ela entra no painel, **Then** ela acessa a administração da plataforma e não é tratada como usuário natural do tenant.
2. **Given** um tenant cadastrado e ativo, **When** o PlatformAdmin seleciona a empresa, **Then** o painel opera em um AdministrativeContext separado, com tenant selecionado validado e auditado.

---

### User Story 2 - Suporte técnico gerencia tenant autorizado sem impersonação (Priority: P1)

O Técnico/Master deve poder apoiar uma empresa autorizada sem assumir a identidade da empresa nem executar operações comerciais em nome do usuário natural do tenant.

**Why this priority**: Esse fluxo define o modelo de suporte operacional e a linha entre administração da plataforma e operação do negócio.

**Independent Test**: Pode ser validado pela autenticação do técnico, escolha do tenant autorizado, uso de AdministrativeContext e ausência de impersonação.

**Acceptance Scenarios**:

1. **Given** um Técnico/Master autenticado, **When** ele seleciona um tenant autorizado, **Then** o sistema cria um contexto administrativo válido e audita a ação.
2. **Given** o Técnico/Master está em contexto administrativo, **When** ele executa ações administrativas permitidas, **Then** apenas operações explícitas e auditáveis são permitidas.

---

### User Story 3 - Empresa em modo leitura ou bloqueada permanece administrável (Priority: P1)

A empresa pode entrar em bloqueio parcial ou total sem que dados sejam apagados, sem que o tenant seja removido e sem que o acesso da plataforma seja perdido para o painel administrativo.

**Why this priority**: O controle de segurança e continuidade operacional depende dessa regra para evitar perda de dados e perda de governança.

**Independent Test**: Pode ser validado pela mudança de status da empresa, pelo bloqueio de operações de negócio e pela persistência das estruturas e registros.

**Acceptance Scenarios**:

1. **Given** uma empresa em status partially_blocked, **When** usuários tentam registrar ou alterar dados, **Then** o ERP bloqueia INSERT/UPDATE/DELETE, mas mantém leitura e gerência administrativa.
2. **Given** uma empresa em status blocked, **When** usuários naturais tentam autenticar, **Then** o acesso é negado e o painel continua administrando o tenant.

---

### User Story 4 - Provisionamento e auditoria do tenant são rastreáveis (Priority: P1)

A plataforma deve registrar provisionamento, eventos administrativos e status do tenant de forma rastreável e segura.

**Why this priority**: O ciclo de vida da empresa e a governança das mudanças precisam ser audíveis para suporte, segurança e operação.

**Independent Test**: Pode ser validado pela presença de `provisioning_status`, `company_status`, eventos de auditoria e registro de falhas com retry permitido.

**Acceptance Scenarios**:

1. **Given** uma empresa ainda em provisionamento, **When** qualquer etapa falha, **Then** o sistema marca o tenant como failed, registra a falha e impede operação ativa.
2. **Given** uma operação administrativa relevante, **When** ela ocorre, **Then** o sistema registra actor, tenant, ação, timestamp, resultado, origem e metadata segura mínima.

---

## Requirements

### Functional Requirements

- **FR-001**: The system MUST define a dedicated PlatformAdmin identity in the control-plane, separate from natural tenant users.
- **FR-002**: PlatformAdmin MUST belong to the platform and MUST NOT be treated as a standard tenant member or as a legacy `admin@localhost` fallback.
- **FR-003**: The system MUST distinguish PlatformAdmin from Técnico/Master as separate roles with distinct responsibilities.
- **FR-004**: Técnico/Master MUST operate in an authenticated, authorized AdministrativeContext for a selected tenant and MUST NOT impersonate a tenant user.
- **FR-005**: Técnico/Master MUST be allowed to consult operational data needed for diagnosis, logs, status, allowed configuration and specific administrative actions, but MUST NOT perform unrestricted business operations in the company’s name.
- **FR-006**: The system MUST support `company_status` values `active`, `partially_blocked`, `blocked`, and `archived`.
- **FR-007**: The system MUST support `provisioning_status` values `pending`, `running`, `ready`, and `failed`.
- **FR-008**: A tenant MUST remain in a non-operational state while `provisioning_status` is not `ready`.
- **FR-009**: A partially blocked company MUST allow authentication and read access while blocking business INSERT/UPDATE/DELETE operations.
- **FR-010**: A fully blocked company MUST deny access for natural tenant users, preserve data and database structures, and keep the panel administrable.
- **FR-011**: The system MUST store `last_login` and a minimal login history for audit and support purposes.
- **FR-012**: The system MUST log relevant administrative events, including login, failed login, company registration, company edit, provisioning, activation, block/unblock, archive, user creation/edit/activation/deactivation, reset of access, admin entry into tenant, and relevant administrative support actions.
- **FR-013**: Audit records MUST store actor identity, role/type, tenant, action, timestamp, result, origin, IP when available, and minimal secure metadata.
- **FR-014**: Audit records MUST NOT store passwords, password hashes, tokens, session IDs, credentials, or secrets.
- **FR-015**: The official tenant access format for the first version MUST be `/empresa/{slug}`.
- **FR-016**: Legacy tenant routes/slugs MAY remain temporarily through redirect/alias compatibility without duplicating tenant logic.
- **FR-017**: A company MUST be marked `ready` only after tenant registration, slug generation, dedicated database creation, schema/template application, `schema_version` registration, minimum configuration, initial admin user creation, connection validation, and link release are all confirmed.
- **FR-018**: Provisioning failures MUST set `provisioning_status = failed`, capture the failing step and safe error, allow retry, retain already-created structures where possible, and avoid destructive automatic cleanup.
- **FR-019**: `tenant_id` MUST be the canonical identity for all new features; `company_id` MAY be supported only as legacy compatibility at boundaries/adapters.
- **FR-020**: Tenant deletion MUST be handled by archive/desactivation in v1; physical deletion is out of scope for this version.
- **FR-021**: The platform control-plane MUST own the canonical authentication model, including identity, credential, tenant link, status, role/permissions, last login, and authorization.
- **FR-022**: Local tenant user tables MUST not become a concurrent source of authority for authentication; they may only be treated as compatibility/local representation later.
- **FR-023**: The platform panel MUST absorb lifecycle ownership for company registration, company editing, tenant selection, status management, provisioning, user administration, block/unblock, support access and administrative audit.
- **FR-024**: The tenant ERP MUST remain responsible only for business operations of the tenant, not the control-plane lifecycle or platform admin functions.

### Key Entities

- **PlatformAdmin**: Platform-owned administrative identity for platform governance and control-plane operations.
- **Técnico/Master**: Authorized support role with operational support scope, distinct from PlatformAdmin.
- **AdministrativeContext**: Explicit user + tenant + authorization scope used during platform or support operations.
- **Company**: Business entity or tenant represented by a registered company and tenant lifecycle.
- **Tenant**: Business domain identity with `tenant_id`, `slug`, `db_name`, status and provisioning lifecycle.
- **User**: Natural user of the tenant or platform, with canonical authentication handled by the control-plane.
- **AuditEvent**: Administrative action record with actor, tenant, action, result, timing and secure metadata.

## Success Criteria

### Measurable Outcomes

- **SC-001**: All platform administrative actions are attributable to a distinct PlatformAdmin or an authorized Técnico/Master identity.
- **SC-002**: No support flow relies on tenant user impersonation in v1.
- **SC-003**: A partially blocked company remains readable while all business write operations are prevented.
- **SC-004**: A fully blocked company keeps data and database structures intact while denying access to natural tenant users.
- **SC-005**: Provisioning status and company status are explicit and do not allow active operation before `ready`.
- **SC-006**: Audit entries record the minimum required event data without storing sensitive credentials or secrets.
- **SC-007**: `/empresa/{slug}` becomes the official access pattern for the first version, with legacy links supported as compatibility redirects only.

## Assumptions

- The control-plane is authoritative for identity and authentication in the first version.
- The tenant database remains the technical boundary for business data, while the platform maintains lifecycle and admin operations.
- Support actions are auditable and permission-gated, not open-ended administrative operations.
- `tenant_id` is the canonical key for new functionality and architecture decisions.
- The initial version prioritizes safety and explicit boundaries over complex module-level blocking or advanced impersonation support.

## Resolved Decisions for v1

### PlatformAdmin
- Choice: A
- PlatformAdmin is a dedicated administrative identity in the control-plane.
- It is not `admin@localhost`, not a tenant member, and not a legacy role fallback.
- It belongs to the platform, not to a company.

### Técnico/Master
- Choice: B
- PlatformAdmin = global platform governance.
- Técnico/Master = authorized operational support.
- These are distinct roles; the técnico/master does not need to be a full PlatformAdmin.

### Technical access
- Choice: A
- In v1: Técnico autenticado → selects authorized tenant → AdministrativeContext → support operations → full audit.
- No impersonation of tenant users.

### Allowed technical operations
- Choice: B
- Supported actions include diagnosis data access, logs, status, permitted configuration view, user administration when allowed, access reset and explicit administrative actions.
- Business operations like sales, purchases, movements and postings are out of scope for support execution in v1.

### Partial block policy
- Choice: A for the first version
- Users still authenticate;
- ERP is read-only;
- INSERT/UPDATE/DELETE are blocked;
- Data remains available;
- Panel stays administrable.

### Total block policy
- Choice: A
- Natural tenant users cannot log in;
- Data remains untouched;
- Database remains intact;
- Platform continues managing the tenant;
- PlatformAdmin/Técnico only use explicit administrative policy and audit.

### Last login policy
- Choice: B
- Keep `last_login` and also maintain minimal login history.
- The panel must show recent access activity, not only the latest isolated login.

### Audit scope
- Choice: D
- Registry includes login, failed login, company creation/edit, provisioning, activation, block/unblock, archive, user events, access reset, admin entry, context start/end and relevant administrative support actions.

### Audit minimum payload
- Choice: D
- Fields include actor, role, tenant, action, timestamp, result, origin, IP when available, and minimal secure metadata.
- Sensitive values excluded: password, hash, token, session id, credentials and secrets.

### Company link format
- Choice: B
- Official format: `/empresa/{slug}`.
- Subdomains remain future work.

### Legacy compatibility
- Choice: C
- New format is official, but old routes/slugs may be temporarily retained via redirect/alias compatibility.
- Do not duplicate tenant logic.

### Provisioning READY criteria
- Choice: A
- Tenant registered, slug generated, dedicated database created, schema/template applied, `schema_version` recorded, minimum config applied, initial admin created, connection validated, access link enabled.

### Provisioning failure handling
- Choice: A
- Mark failed, capture failing step and safe error, allow retry, avoid destructive cleanup, ensure idempotence where possible, and keep tenant non-active until `ready`.

### Status model
- Choice: B
- `company_status`: `active`, `partially_blocked`, `blocked`, `archived`
- `provisioning_status`: `pending`, `running`, `ready`, `failed`
- No extra states unless truly necessary.

### Schema versioning
- Choice: B
- Each tenant must expose an explicit `schema_version`.
- Platform must be able to tell current schema version, applied migrations, compatibility and required update.
- Migration runner not in this stage.

### Canonical user source
- Choice: A
- Canonical authentication stays in the control-plane.
- Local tenant user tables are not a second source of authority; they are legacy/local compatibility only.

### `company_id` vs `tenant_id`
- Choice: B
- `tenant_id` is canonical for new features.
- `company_id` may be retained only as compatibility support at legacy boundaries.

### Company deletion
- Choice: A
- First version must not physically delete tenant/bank from the panel.
- Use `archived` or equivalent deactivation.

### Migration of current functions
- Choice: A
- Platform panel owns company registration, editing, selection, lifecycle, provisioning, user management, block/unblock, technical support and admin audit.
- Tenant ERP remains only for actual business operations.

## Decisions Deferred to Future Work

- Domain and subdomain customization.
- Advanced or complete login history.
- Module-level partial block granularity.
- Very specific technical permissions by operation.
- Real impersonation support.
- Physical tenant deletion with retention policy.
- Full migration runner and schema upgrade engine.

## Final Constraints

- This specification reflects decisions made for the first version only.
- No implementation is to be performed in this step.
- No database or schema changes are to be executed.
- No migration scripts or production code are to be created or modified.
- The platform panel and control-plane design is clarified and ready to be used as a requirements baseline for the next planning phase.
