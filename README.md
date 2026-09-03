<!-- Hero -->
<p align="center">
  <img src="mini-erp-web/public/assets/images/mini-erp-logo.png" alt="MiniERP" width="220">
  <h1 align="center">MiniERP 1.0</h1>
  <p align="center">ERP web multiempresa em PHP + MariaDB — pedidos, fiscais e multi-tenant.</p>

  <p align="center">
    <img alt="PHP 8.2" src="https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white">
    <img alt="MariaDB" src="https://img.shields.io/badge/MariaDB-MySQL-003545?logo=mariadb&logoColor=white">
    <img alt="Status" src="https://img.shields.io/badge/status-em%20desenvolvimento-F59E0B">
  </p>
</p>

---

## Visão geral rápida

MiniERP é uma aplicação web focada em operações comerciais (pedidos) e preparação fiscal
para NF-e/NFC-e. Projetado para ambientes multi-tenant com separação de dados por tenant,
fluxos transacionais e prévias locais de DANFE/DANFC-e.

## Destaques

- Multi-tenant com isolamento por banco;
- Cadastro unificado: pessoas, produtos, transportadoras;
- Fluxo de pedidos com itens, frete, volumes e totais;
- Central de Notas: prévias, XML, timeline e envio (quando configurado);
- Prévia DANFE (55) em A4 e DANFC-e (65) compacta;
- Armazenamento de XML com integridade (SHA-256) quando disponível;
- Módulos de sincronização de catálogo (NCM/contábil) e importadores tabulares.

## Galeria de telas e logo

Abaixo estão as imagens e logos já presentes no repositório. Substitua ou adicione novos prints em
`mini-erp-web/public/assets/images/` e atualize os nomes conforme necessário.

<p align="center">
  <img src="mini-erp-web/public/assets/images/mini-erp-logo.png" alt="MiniERP Logo" width="220" style="margin:8px">
  <img src="mini-erp-web/public/assets/images/logo_login.png" alt="Login Logo" width="220" style="margin:8px">
  <img src="mini-erp-web/public/assets/images/LOGO.png" alt="LOGO" width="220" style="margin:8px">
  <img src="mini-erp-web/public/assets/images/gif_logo.png" alt="GIF Logo" width="220" style="margin:8px">
</p>

Se quiser, posso gerar thumbnails e adicionar imagens de cada tela (Dashboard, Pedidos,
Central de Notas, Cadastro de Produtos). Para isso, envie os prints ou autorize-me a capturar
imagens locais se estiverem disponíveis no ambiente de trabalho.

---

## Screenshots detalhados

A seguir estão as capturas reais (desktop, tablet e mobile) organizadas por área. Cada legenda explica brevemente o que a tela mostra.

- **Dashboard (desktop)** — visão analítica com KPIs, filtros por período, cartões de resumo (clientes, pedidos, notas, pendências) e gráficos de faturamento e produtos mais vendidos.

  ![Dashboard Desktop](mini-erp-web/prints/notebook.jpg)

- **Dashboard (desktop - alternativa)** — variação do dashboard em tela maior com gráficos e alertas de estoque.

  ![Dashboard Desktop 2](mini-erp-web/prints/dashboard%20desktop.jpg)

- **Tela de login (desktop)** — formulário de autenticação com opção de registro e separação visual (card + painel lateral de marca).

  ![Login Desktop](mini-erp-web/prints/DESKTOP.jpg)

- **Dashboard (tablet)** — versão responsiva do dashboard para tablet com cartões empilhados e gráficos adaptados.

  ![Dashboard Tablet](mini-erp-web/prints/tablet%20dashboard.jpg)

- **Painel da Plataforma (desktop)** — administração da plataforma: listagem de empresas, busca, status e ações administrativas (provisionar, acessar ERP do tenant).

  ![Painel da Plataforma](mini-erp-web/prints/PAINEL%20MOBILE.jpg)

- **Dashboard (mobile)** — visão compacta dos KPIs com navegação inferior, cartões empilhados e gráfico resumido para acompanhar faturamento rápido.

  ![Dashboard Mobile](mini-erp-web/prints/mobile%20dashboard.jpg)

- **Login (mobile)** — formulário de entrada adaptado para telas pequenas, com campo de seleção da empresa e botão de ação grande para toque.

  ![Login Mobile](mini-erp-web/prints/mobile.jpg)

- **Painel da Plataforma (mobile)** — visão mobile do painel de empresas, com botão `+ Nova empresa` e navegação inferior para demais seções administrativas.

  ![Painel Plataforma Mobile](mini-erp-web/prints/PAINEL%20MOBILE.jpg)

Se desejar, posso:

- gerar thumbnails otimizados e adicioná-los ao diretório `mini-erp-web/public/assets/images`;
- criar uma subpágina de documentação em `docs/screenshots.md` com imagens maiores e anotações por elemento (setas/legendas).
Se desejar, posso:

- gerar thumbnails otimizados e adicioná-los ao diretório `mini-erp-web/public/assets/images`;
- criar uma subpágina de documentação em `docs/screenshots.md` com imagens maiores e anotações por elemento (setas/legendas).

---

## Compatibilidade e suporte

O MiniERP foi pensado para uso em desktops, tablets e dispositivos móveis. Recomendamos as seguintes plataformas para melhor compatibilidade:

- Navegadores desktop: Chrome (últimas 2 versões), Edge (Chromium), Firefox, Safari (macOS).
- Navegadores mobile: Chrome Android, Safari iOS (versões recentes).
- Recomendado: tela mínima de 360x640 para mobile, 768x1024 para tablet, 1280x720 para desktop.

Notas de compatibilidade:

- Funcionalidades de PDF/preview dependem de bibliotecas do servidor e do navegador para abrir novas guias; em dispositivos móveis o PDF pode abrir no visualizador nativo.
- Integração fiscal (transmissão) requer configuração de certificado A1 e ambiente SEFAZ — não é executada nas prévias locais.

## Usabilidade e acessibilidade (UX)

Principais considerações de UX:

- Navegação: menu lateral em desktop e barra inferior em mobile para troca rápida entre seções (Dashboard, Pedidos, Notas, Cadastros).
- Acessibilidade: foco visível em controles, labels nos campos de formulário e estruturas semânticas para melhoria em leitores de tela.
- Touch targets: botões principais têm tamanhos adequados para toque (>=44px) nas views mobile.
- Performance: as páginas usam carregamento assíncrono de gráficos; em bases grandes ative paginação e filtros para reduzir payload.

Recomendações para testes de usabilidade:

- Testar em Chrome Mobile (emulador) e em um dispositivo físico iOS para validar comportamento de PDF e teclado.
- Testar fluxo completo de pedido → prévia → gerar XML em um tenant de teste antes de habilitar transmissão.

---

## Documentação visual (anotações por tela)

Detalhes mais ricos e anotações por elemento estão em [docs/screenshots.md](docs/screenshots.md). Lá há imagens maiores e explicações ponto a ponto (filtros, cards KPI, ações disponíves, comportamento responsivo).

## Quickstart (local)

1. Clone o repositório:

```powershell
cd C:\xampp\htdocs
git clone https://github.com/WillSPACCE/MiniERP-1.0.git MiniRP
cd MiniRP\mini-erp-web
```

2. Instale dependências:

```powershell
composer install
```

3. Configure `mini-erp-web/config.php` com as credenciais do seu MariaDB/XAMPP.

4. Inicie com Apache (XAMPP) ou servidor PHP embutido:

```powershell
# Apache: iniciar via painel XAMPP
# PHP embutido (teste rápido)
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public
```

Visite `http://127.0.0.1:8000/`.

---

## Boas práticas e aviso fiscal

- Prévias DANFE/DANFC-e são geradas localmente sem certificado; não realizam transmissão real.
- Configure certificado A1, série e ambiente antes de transmitir; siga procedimentos fiscais.

---

## Contribuição

1. Abra uma issue descrevendo sua sugestão ou bug.
2. Crie uma branch com clara intenção: `feature/...` ou `fix/...`.
3. Faça PR com descrição, screenshots e passos para reproduzir.

---

## Licença

Consulte o `composer.json` para detalhes de propriedade. Contate o autor antes de redistribuir.

---

Desenvolvido por <a href="https://github.com/WillSPACCE">WillSPACCE</a>.
