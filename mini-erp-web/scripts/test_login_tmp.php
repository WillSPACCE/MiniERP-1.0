<?php
require __DIR__ . '/../app/Database.php';
require __DIR__ . '/../app/Repository.php';
$repo = new Repository();
$user = $repo->findUsuarioByEmail('admin@localhost');
var_export($user);
if ($user) {
    echo PHP_EOL . 'verify: ' . (password_verify('admin', $user['senha']) ? 'ok' : 'fail') . PHP_EOL;
}
