<?php
declare(strict_types=1);

use MiniErp\Infrastructure\ControlPlaneConnectionFactory;
use MiniErp\Repositories\PlatformAdminRepository;
use MiniErp\Services\PlatformAuthenticationService;

require_once __DIR__ . '/_session.php';
startPlatformSession();

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

if (!empty($_SESSION['platform_admin']['admin_id'])) {
    header('Location: /plataforma/');
    exit;
}

require_once __DIR__ . '/../../vendor/autoload.php';

$connection = (new ControlPlaneConnectionFactory(__DIR__ . '/../../config.php'))->create();
$repository = new PlatformAdminRepository($connection);
$hasAdmins = $repository->count() > 0;

if (empty($_SESSION['platform_login_csrf'])) {
    $_SESSION['platform_login_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals((string) $_SESSION['platform_login_csrf'], (string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Sessão do formulário expirada.';
    } else {
        try {
            $identity = (new PlatformAuthenticationService($repository))->authenticate(
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['password'] ?? ''),
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            session_regenerate_id(true);
            $_SESSION['platform_admin'] = [
                'admin_id' => $identity->getUserId(),
                'email' => $identity->getEmail(),
                'role' => $identity->getRole(),
                'authenticated_at' => time(),
            ];
            unset($_SESSION['platform_login_csrf']);
            header('Location: /plataforma/');
            exit;
        } catch (DomainException) {
            $error = 'E-mail ou senha inválidos.';
        }
    }
}

$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login do Control-Plane</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/Favicon-v2/favicon-32x32.png">
    <link rel="apple-touch-icon" href="/assets/images/Favicon-v2/apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/platform.css">
</head>
<body class="login-body">
    <main class="login-shell">
        <div class="login-brand">
            <span class="brand-mark">MR</span>
            <div>
                <strong>Mini ERP</strong>
                <small>Control-Plane</small>
            </div>
        </div>
        <h1>Painel da Plataforma</h1>
        <p>Autenticação administrativa exclusiva do Control-Plane.</p>
        <?php if (!$hasAdmins): ?>
            <p class="message error">Nenhum administrador do Control-Plane configurado.</p>
            <p class="login-note">Use o bootstrap CLI para criar o primeiro administrador.</p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="message error" role="alert"><?= $e($error) ?></p>
        <?php endif; ?>
        <form class="login-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= $e($_SESSION['platform_login_csrf']) ?>">
            <div>
                <label for="platform-email">E-mail</label>
                <input id="platform-email" type="email" name="email" autocomplete="username" required>
            </div>
            <div>
                <label for="platform-password">Senha</label>
                <input id="platform-password" type="password" name="password" autocomplete="current-password" required>
            </div>
            <button class="btn btn-primary" type="submit" <?= $hasAdmins ? '' : 'disabled' ?>>Entrar no Control-Plane</button>
        </form>
    </main>
</body>
</html>
