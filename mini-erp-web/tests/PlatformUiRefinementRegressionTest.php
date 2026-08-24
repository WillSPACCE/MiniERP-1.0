<?php
declare(strict_types=1);

$root = __DIR__ . '/..';
$css = file_get_contents($root . '/public/assets/platform.css');
$login = file_get_contents($root . '/public/plataforma/login.php');
$cert = file_get_contents($root . '/public/plataforma/empresa-fiscal-config.php');

if ($css === false || $login === false || $cert === false) {
    throw new RuntimeException('Unable to read required UI files.');
}

foreach (['--control-height', '--font-sm', '--space-4', '.btn-primary', '.btn-secondary', '.btn-outline', '.btn-danger', '.btn-icon', '.cert-status-card', '.form-grid', '.badge'] as $token) {
    if (!str_contains($css, $token)) {
        throw new RuntimeException("Missing UI token/class: {$token}");
    }
}

if (!str_contains($login, 'login-shell') || !str_contains($login, 'class="btn btn-primary"')) {
    throw new RuntimeException('Login shell is not using the compact admin treatment.');
}

if (!str_contains($cert, 'cert-status-card') || !str_contains($cert, 'Validar e salvar')) {
    throw new RuntimeException('Certificate page is missing the compact status panel and action redesign.');
}

echo "PlatformUiRefinementRegression OK\n";
