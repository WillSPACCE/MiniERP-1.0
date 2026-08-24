# PLATFORM-UI-02 — refinamento visual do Control-Plane

## Objetivo

Ajustar a aparência do painel administrativo para um padrão moderno, compacto e profissional sem redesenhar a identidade atual ou adicionar frameworks pesados.

## Escopo

- reduzir tamanho de tipografia e espaçamento em telas administrativas;
- padronizar botões e inputs para altura e proporção compactas;
- consolidar estados de sucesso, warning, danger e neutral;
- reduzir cards e tabelas para leitura mais ágil;
- manter o mesmo layout atual com refinamento visual e consistência de ícones.

## Sistema visual aplicado

- tokens de espaçamento (`--space-*`);
- tokens tipográficos (`--font-*`);
- alturas padrão de controles (`--control-height`, `--control-height-sm`);
- maior padronização de botões: `btn`, `btn-primary`, `btn-secondary`, `btn-outline`, `btn-danger`, `btn-success`, `btn-warning`, `btn-ghost`, `btn-icon`;
- cards, badges e filas mais compactas;
- login compacto e consistente com o restante do painel;
- painel de status do certificado em formato enxuto e legível.

## Resultado esperado

- arquitetura administrativa com leitura rápida;
- experiência visual mais profissional e compacta;
- telas operacionais com hierarquia limpa e menos ruído visual;
- sem quebra de funcionalidade ou de regras de negócio.
