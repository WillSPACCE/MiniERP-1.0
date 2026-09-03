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
