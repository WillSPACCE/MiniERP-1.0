<?php
declare(strict_types=1);

function platformEscape(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function isPlatformPartialView(): bool
{
    return isset($_GET['view']) && $_GET['view'] === 'partial';
}

function renderPlatformStart(object $identity, string $title, string $breadcrumb = 'Empresas'): void
{
    if (isPlatformPartialView()) {
        echo '<div class="platform-partial" data-partial-view="true">';
        return;
    }
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }

    if (empty($_SESSION['platform_logout_csrf'])) {
        $_SESSION['platform_logout_csrf'] = bin2hex(random_bytes(32));
    }

    $name = platformEscape($identity->getName());
    $role = platformEscape($identity->getRole());
    $pageTitle = platformEscape($title);
    $crumb = platformEscape($breadcrumb);
    $csrf = platformEscape($_SESSION['platform_logout_csrf']);
    $embedded = isset($_GET['embed']) && $_GET['embed'] === '1';
    $currentPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $mobileActive = static fn(string $path): string => $currentPath === $path || ($path !== '/plataforma/' && str_starts_with($currentPath, $path)) ? ' active' : '';
    $mobileCompanies = $mobileActive('/plataforma/');
    $mobileTechnicians = $mobileActive('/plataforma/tecnicos.php');
    $mobileOperations = $mobileActive('/plataforma/operacoes-multitenant.php');
    $mobileAudit = $mobileActive('/plataforma/auditoria.php');
    $mobileSettings = $mobileActive('/plataforma/configuracoes.php');
    $mobileAccount = $mobileActive('/plataforma/minha-conta.php');
    $uiScripts = $embedded
        ? '<link rel="stylesheet" href="/assets/embed-ui.css"><script src="/assets/embed-ui.js" defer></script>'
        : '<script src="/assets/app-ui.js" defer></script><script src="/assets/fiscal-config-ui.js" defer></script>';

    echo <<<HTML
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{$pageTitle} — Painel da Plataforma</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/Favicon-v2/favicon-32x32.png?v=round1">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/Favicon-v2/favicon-16x16.png?v=round1">
    <link rel="apple-touch-icon" href="/assets/images/Favicon-v2/apple-touch-icon.png?v=round1">
    <link rel="stylesheet" href="/assets/platform.css?v=mobile7">
    <link rel="stylesheet" href="/assets/platform-audit.css?v=2">
    <link rel="stylesheet" href="/assets/platform-audit-simple.css?v=3">
    <link rel="stylesheet" href="/assets/platform-audit-table.css?v=1">
    <link rel="stylesheet" href="/assets/platform-audit-rules.css?v=1">
    <link rel="stylesheet" href="/assets/app-ui.css">
    <link rel="stylesheet" href="/assets/app-feedback.css">
    <link rel="stylesheet" href="/assets/ui-forms.css">
    <script src="/assets/cnpj-lookup.js" defer></script>
    {$uiScripts}
    <script src="/assets/app-feedback.js" defer></script>
</head>
<body>
<div class="platform-shell">
    <aside class="platform-sidebar">
        <div class="platform-brand">
            <a class="platform-brand-home" href="/plataforma/" aria-label="Voltar ao Dashboard da plataforma" title="Voltar ao Dashboard"><img class="platform-brand-logo" src="/assets/images/mini-erp-logo.png" alt="Mini ERP Web"></a>
            <div>
                <strong>Mini ERP</strong>
                <small>Control-Plane</small>
            </div>
        </div>
        <nav class="platform-nav" aria-label="Navegação do painel">
            <a href="/plataforma/">Dashboard</a>
            <a href="/plataforma/">Empresas</a>
            <a href="/plataforma/tecnicos.php">Técnicos globais</a>
            <a href="/plataforma/operacoes-multitenant.php">Operações Multi-tenant</a>
            <a href="/plataforma/auditoria.php">Auditoria</a>
            <a href="/plataforma/configuracoes.php">Configurações</a>
        </nav>
    </aside>
    <div class="platform-main">
        <header class="platform-header">
            <div class="header-copy">
                <strong>Painel da Plataforma</strong>
                <small>Administração exclusiva do Control-Plane</small>
            </div>
            <div class="admin-info">
                <div>
                    <strong>Administrador: {$name}</strong>
                    <small>{$role}</small>
                </div>
                <a class="btn ghost" href="/plataforma/minha-conta.php">Minha conta</a>
                <form method="post" action="/plataforma/logout.php">
                    <input type="hidden" name="csrf_token" value="{$csrf}">
                    <button class="btn ghost" type="submit">Sair</button>
                </form>
            </div>
        </header>
        <nav class="platform-mobile-nav" aria-label="Menu principal para celular">
            <a class="{$mobileSettings}" href="/plataforma/configuracoes.php"><span aria-hidden="true">⚙</span><strong>Config.</strong></a>
            <a class="{$mobileCompanies}" href="/plataforma/"><span aria-hidden="true">▦</span><strong>Empresas</strong></a>
            <a class="{$mobileTechnicians}" href="/plataforma/tecnicos.php"><span aria-hidden="true">♟</span><strong>Técnicos</strong></a>
            <a class="{$mobileOperations}" href="/plataforma/operacoes-multitenant.php"><span aria-hidden="true">↻</span><strong>Operações</strong></a>
            <a class="{$mobileAudit}" href="/plataforma/auditoria.php"><span aria-hidden="true">◫</span><strong>Auditoria</strong></a>
            <a class="{$mobileAccount}" href="/plataforma/minha-conta.php"><span aria-hidden="true">●</span><strong>Conta</strong></a>
        </nav>
        <main class="content">
            <p class="muted">{$crumb}</p>
HTML;
}

function renderPlatformEnd(): void
{
    if (isPlatformPartialView()) {
        echo '</div>';
        return;
    }
    echo '<footer class="site-credit platform-credit">Desenvolvido por <a href="https://willspacce.netlify.app/" target="_blank" rel="noopener noreferrer" aria-label="Portfólio de Willyan Martins">Willyan Martins <span aria-hidden="true">›</span></a></footer></main></div></div></body></html>';
}
