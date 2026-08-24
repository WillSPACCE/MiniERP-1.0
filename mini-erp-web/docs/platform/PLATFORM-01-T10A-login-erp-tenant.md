# PLATFORM-01-T10A — Login da empresa e abertura segura do ERP

Status: implementado e integrado ao novo entrypoint `/erp/`.

O login consulta a identidade canônica em `mini_erp.usuarios`, valida exclusivamente com `password_verify`, exige usuário ativo e `tenant_id` igual ao tenant identificado pelo slug. A empresa precisa estar ativa, não bloqueada e provisionada com o nome canônico `mini_erp_tenant_{tenant_id}`.

Após autenticação, a sessão grava somente `erp_user_id` e `erp_tenant_id`. O runtime relê usuário e empresa no MAIN, compõe `TenantContext` e entrega esse contexto ao `TenantConnectionResolver`. `db_name` nunca é aceito da URL ou do formulário. Não há fallback para tenant 1, impersonação ou reaproveitamento de `platform_user_id`.

O recorte original desta task criou dashboard e listagens paralelas somente leitura. A correção PLATFORM-01-T10R removeu essa UI duplicada e conservou `/erp/` somente como autenticação/redirecionamento para o ERP histórico. O logout remove as chaves ERP e as compatibilidades legadas derivadas, preservando eventual sessão do Control-Plane.

Fora do escopo: estabilização dos entrypoints (T10B), integração dos CRUDs ao banco tenant (T10C), rota definitiva `/empresa/{slug}` (T10D) e qualquer implementação de T07–T09.
