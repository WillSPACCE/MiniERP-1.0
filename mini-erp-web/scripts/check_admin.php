<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Repository.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

$repo = new Repository();
$user = $repo->findUsuarioByEmail('admin@localhost');
if (!$user) {
    echo "Usuário admin não encontrado\n";
    exit(1);
}

echo "Encontrado: " . $user['email'] . " nome=" . $user['nome'] . "\n";
if (password_verify('2020', $user['senha'])) {
    echo "Senha correta (2020)\n";
} else {
    echo "Senha diferente\n";
}
