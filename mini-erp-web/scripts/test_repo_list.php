<?php
require __DIR__ . '/../app/Database.php';
require __DIR__ . '/../app/Repository.php';
try {
    $repo = new Repository();
    $rows = $repo->listCompanies();
    echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}
