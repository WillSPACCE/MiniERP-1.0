<?php
declare(strict_types=1);

$root = __DIR__ . '/..';
$validationFiles = [
    'public/plataforma/_layout.php',
    'public/plataforma/login.php',
    'public/plataforma/_context.php',
    'public/plataforma/operacoes-multitenant.php',
    'public/plataforma/minha-conta.php',
    'public/plataforma/empresa-database.php',
];

$badPatterns = [
    'OperaÃ§',
    'AdministraÃ§',
    'SessÃ£o do formulÃ¡rio expirada.',
    'E-mail ou senha invÃ¡lidos.',
    'AÃ§Ã£o invÃ¡lida.',
    'CSRF invÃ¡lido.',
    'Painel indisponÃ­vel.',
    'ConfirmaÃ§Ã£o',
    'EXECUTAR OPERAÃ‡ÃƒO',
    'HistÃ³rico de OperaÃ§Ãµes',
    'Ãndices',
    'â†',
    'Â·',
    'â€”',
];

foreach ($validationFiles as $relativePath) {
    $path = $root . '/' . $relativePath;
    if (!is_file($path)) {
        throw new RuntimeException("Missing platform file: {$relativePath}");
    }

    $content = file_get_contents($path);
    if ($content === false) {
        throw new RuntimeException("Unable to read: {$relativePath}");
    }

    foreach ($badPatterns as $pattern) {
        if (str_contains($content, $pattern)) {
            throw new RuntimeException("Mojibake detected in {$relativePath}: {$pattern}");
        }
    }

    if (str_contains($relativePath, '_layout.php') || str_contains($relativePath, 'login.php')) {
        $hasUtf8Header = stripos($content, 'charset=UTF-8') !== false || stripos($content, 'charset="utf-8"') !== false;
        if (!$hasUtf8Header) {
            throw new RuntimeException("Missing UTF-8 declaration in {$relativePath}");
        }
    }
}

echo "PlatformUiUtf8Regression OK\n";
