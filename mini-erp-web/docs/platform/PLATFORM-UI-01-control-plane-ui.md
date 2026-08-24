# PLATFORM-UI-01 — Correção UTF-8 + padronização visual do Control-Plane

## Objetivo

Corrigir os textos corrompidos do painel administrativo do Control-Plane e consolidar uma padronização visual estável para todas as telas do módulo `public/plataforma` sem tocar no tenant-data-plane nem no fluxo ERP.

## Causa raiz

A instância em execução apresentava strings em estado de mojibake, especialmente em trechos como:

- `OperaÃ§Ãµes Multi-tenant`
- `AdministraÃ§Ã£o exclusiva do Control-Plane`
- `CSRF invÃ¡lido.`
- `Painel indisponÃ­vel.`

Isso indica que o código-fonte foi salvo/transformado em uma codificação incompatível com UTF-8, e a página também não estava emitindo a resposta HTTP/HTML com `charset=UTF-8` de forma consistente.

## Arquivos impactados

- `public/plataforma/_layout.php`
- `public/plataforma/login.php`
- `public/plataforma/_context.php`
- `public/plataforma/operacoes-multitenant.php`
- `public/plataforma/minha-conta.php`
- `public/plataforma/empresa-database.php`
- `public/assets/platform.css`

## Correções aplicadas

1. Padronização do charset no layout e nas páginas login/operações;
2. Substituição dos textos corrompidos por versões em português correto e consistentes;
3. Garantia de `Content-Type: text/html; charset=UTF-8` para o HTML do painel;
4. Manutenção da separação visual entre o Control-Plane e o ERP;
5. Reuso dos tokens visuais do `platform.css` para manter uma linguagem visual única.

## Critérios de sucesso

- Sem mojibake em textos de interface do painel;
- HTML do painel em UTF-8;
- Sem alterações de regras de negócio do ERP ou do tenant-data-plane;
- Visual consistente entre dashboard, operações, detalhes sociais e formulários;
- Regressão coberta por teste automatizado simples.

## Observações

Esse trabalho não cria ou modifica o Platform Admin existente; ele apenas repara e padroniza a camada de interface do control-plane.
