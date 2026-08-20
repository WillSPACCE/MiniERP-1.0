<?php

declare(strict_types=1);

// Teste de caracterização: verifica que o fluxo legado em public/index.php
// realiza uma mutação temporária de $_SESSION['tenant_id'] ao criar usuário.

$index = file_get_contents(__DIR__ . '/../public/index.php');
if ($index === false) {
    fwrite(STDERR, "Cannot read public/index.php\n");
    exit(1);
}

$pattern1 = "// Temporariamente define tenant/session para permitir assignUserToCompany funcionar";
$pattern2 = "\$_SESSION['tenant_id'] = \$id;";
$pattern3 = "\$repo->assignUserToCompany";

$found1 = strpos($index, $pattern1) !== false;
$found2 = strpos($index, $pattern2) !== false;
$found3 = strpos($index, $pattern3) !== false;

if (!$found1 || !$found2 || !$found3) {
    fwrite(STDERR, "Characterization FAILED: expected legacy session mutation block not found.\n");
    fwrite(STDERR, "Found comment: " . var_export($found1, true) . "\n");
    fwrite(STDERR, "Found session assign: " . var_export($found2, true) . "\n");
    fwrite(STDERR, "Found assignUserToCompany call: " . var_export($found3, true) . "\n");
    exit(1);
}

echo "Characterization OK\n";
