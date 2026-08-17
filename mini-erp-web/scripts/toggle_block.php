<?php
require __DIR__ . '/../app/Database.php';
require __DIR__ . '/../app/Repository.php';
$repo = new Repository();
$id = $argv[1] ?? null;
$flag = $argv[2] ?? '1';
if (!$id) {
    echo "Uso: php toggle_block.php <companyId> [0|1]\n";
    exit(1);
}
try {
    $repo->setCompanyBlocked((int)$id, $flag === '1');
    echo "OK: set blocked={$flag} for company {$id}\n";
} catch (Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . PHP_EOL;
}
