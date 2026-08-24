# PLATFORM-01-T03 — Lifecycle/status das empresas

## Estado do schema observado

Em 2026-08-20, a inspeção somente leitura de `mini_erp.tenants` confirmou `status varchar(20)`, `blocked tinyint(1)`, `db_name` anulável e `created_at`/`updated_at`. Não existem `provisioning_status` ou `schema_version`. Os registros legados usam `ativo`; o tenant 14 usa `cadastrada`, `blocked = 0` e `db_name = NULL`.

Nenhum valor persistido foi convertido. A separação definitiva entre `company_status` e `provisioning_status`, além de `schema_version`, requer task própria de schema.

## Interpretação compatível

- `cadastrada`: registro lógico sem ambiente pronto;
- `provisionando`: reservado para T04;
- `ativa` e o legado `ativo`: ambiente tratado como ativo; ERP visualmente disponível apenas com `db_name` não vazio;
- `parcialmente_bloqueada`: política operacional reservada para T07;
- `bloqueada`: sem acesso visual ao ERP;
- `arquivada`: preservada e sem operação;
- valor desconhecido: fail-closed.

O campo legado `blocked = 1` torna uma empresa interpretada como ativa visualmente bloqueada. Isso não altera o registro e não implementa o bloqueio do runtime.

## Transições declaradas

São reconhecidas: `cadastrada → provisionando`; `provisionando → ativa|cadastrada`; `ativa → parcialmente_bloqueada|bloqueada|arquivada`; `parcialmente_bloqueada → ativa|bloqueada`; `bloqueada → ativa|arquivada`. `arquivada` não possui saída. T03 apenas valida essas regras; não persiste transições.

## Limites

Os links de Provisionar, Usuários, ERP e Bloquear/Desbloquear levam a uma rota autenticada e informativa. Ela revalida a política e não executa escrita. T04 fará o provisionamento físico; T06 tratará usuários; T07 aplicará bloqueios; T10 integrará o acesso ao ERP.
