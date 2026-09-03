# Documentação visual — Screenshots anotadas

Esta página reúne as capturas de tela do projeto com anotações e descrições técnicas e de usabilidade.

## Como usar esta página

- As imagens estão organizadas por área (Dashboard, Login, Painel da Plataforma).
- Cada seção descreve: elementos principais, ações usuais, comportamentos responsivos e notas de teste.

---

## Dashboard (Desktop)

![Dashboard Desktop](../mini-erp-web/prints/notebook.jpg)

Pontos principais:

- Filtro por período e cliente no topo: usado para refinar os KPIs e os gráficos.
- Cartões KPI (clientes, pedidos, notas, pendências, saldo em estoque): atalhos rápidos para as áreas relacionadas.
- Gráficos (faturamento, mais vendidos): carregados assincronamente; configurar cache para performance.
- Ações: exportar, filtrar e abrir prévias de DANFE.

Testes recomendados:

- Alterar período e verificar atualização de KPIs.
- Abrir prévia DANFE e validar geração do PDF em nova guia.

---

## Dashboard (Tablet)

![Dashboard Tablet](../mini-erp-web/prints/tablet%20dashboard.jpg)

Notas:

- Layout adaptado com cartões empilhados horizontalmente quando há largura suficiente.
- Tocar em um cartão abre a lista detalhada; use gestos de scroll para navegar.

---

## Dashboard (Mobile)

![Dashboard Mobile](../mini-erp-web/prints/mobile%20dashboard.jpg)

Usabilidade:

- Navegação inferior com ícones para acesso rápido às seções.
- Cartões empilhados com contraste forte para leitura rápida.
- Gráficos simplificados para reduzir consumo de dados.

---

## Login (Desktop)

![Login Desktop](../mini-erp-web/prints/DESKTOP.jpg)

Elementos:

- Card de login dividido: formulário à esquerda e branding/CTA à direita.
- Campos com placeholder e labels; botão `Entrar` com contraste forte.

Testes:

- Testar fluxo de login com vários tenants e usuário com permissão.

---

## Login (Mobile)

![Login Mobile](../mini-erp-web/prints/mobile.jpg)

Notas de usabilidade:

- Campo de seleção de empresa na parte superior do card.
- Botão grande para ação primária, otimizado para toque.

---

## Painel da Plataforma (Desktop)

![Painel Plataforma Desktop](../mini-erp-web/prints/PAINEL%20MOBILE.jpg)

Descrição:

- Página de administração de empresas: busca, filtros, status, ações (provisionar, acessar).
- Indicadores e lista paginada.

---

## Painel da Plataforma (Mobile)

![Painel Plataforma Mobile](../mini-erp-web/prints/PAINEL%20MOBILE.jpg)

Notas:

- Elementos reordenados verticalmente e navegação inferior ativa.
- Botão `+ Nova empresa` fixo no topo para fácil acesso.

---

## Conclusão

Se quiser, posso adicionar anotações visuais (setas, caixas e chamadas) diretamente nas imagens e gerar versões anotadas em `docs/assets/` para referência em apresentações e PRs.
