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
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/Favicon-v2/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/Favicon-v2/favicon-16x16.png">
    <link rel="apple-touch-icon" href="/assets/images/Favicon-v2/apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/platform.css">
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
            <span class="brand-mark">MR</span>
            <div>
                <strong>Mini ERP</strong>
                <small>Control-Plane</small>
            </div>
        </div>
        <nav class="platform-nav" aria-label="Navegação do painel">
            <a href="/plataforma/">Dashboard</a>
            <a href="/plataforma/">Empresas</a>
            <a href="/plataforma/operacoes-multitenant.php">Operações Multi-tenant</a>
            <span class="disabled">Auditoria <small>Em breve</small></span>
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
    echo '</main></div></div></body></html>';
}
