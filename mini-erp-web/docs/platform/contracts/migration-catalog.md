# Contrato — Migration Catalog

## Objetivo

Definir o catálogo explícito e verificável das migrations oficiais aplicáveis ao sistema multi-tenant.

## Estrutura da entrada

Cada entrada do catálogo possui:

- `migration_id`
- `filename`
- `checksum`
- `target`
- `risk`
- `description`
- `dependencies`
- `tables_affected`
- `requires_backup`
- `transaction_mode`
- `schema_version_from`
- `schema_version_to`

## Target

- `MAIN`: aplica ao banco do Control-Plane;
- `TENANT`: aplica ao banco do tenant;

A UI multi-tenant só aceita `TENANT`.

## Risco

- `SAFE_ADDITIVE`
- `STRUCTURAL`
- `DESTRUCTIVE`
- `MANUAL_REVIEW`

Política:

- `SAFE_ADDITIVE`: permitido após dry-run e backup;
- `STRUCTURAL`: permitido somente quando explicitamente catalogado/aprovado;
- `DESTRUCTIVE`: bloqueado;
- `MANUAL_REVIEW`: bloqueado.

## Dependências

A dependência é materializada no catálogo e não inferida por ordem alfabética.

Exemplo:

- Migração `B` depende de `A`;
- se `A` não estiver aplicada ou incorporada ao baseline, o precheck retorna `DEPENDENCY_MISSING`.

## Checksum

A verificação usa SHA-256 do arquivo oficial e é validada em três pontos:

1. no catálogo;
2. no dry-run;
3. no momento da execução real.

Se o hash mudar entre a simulação e a execução, a operação é bloqueada com `CHECKSUM_MISMATCH`.

## Baseline e legado

A lógica reconhece quando a estrutura foi incorporada ao baseline do tenant sem linha de histórico correspondente. Nesse caso, o status pode ser `BASELINE_INCLUDED`, evitando reaplicação cega.

## States de dry-run

- `READY`
- `ALREADY_APPLIED`
- `BASELINE_INCLUDED`
- `DEPENDENCY_MISSING`
- `CHECKSUM_MISMATCH`
- `SCHEMA_INCOMPATIBLE`
- `TARGET_MISMATCH`
- `BLOCKED`

## Proibidos

O catálogo não permite:

- SQL digitado no navegador;
- upload de arquivo SQL;
- path arbitrário;
- URL de migration;
- edição do SQL no painel;
- qualquer migração fora do diretório oficial.
