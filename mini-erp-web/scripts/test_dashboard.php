<?php
require __DIR__ . '/../app/Database.php';
require __DIR__ . '/../app/Repository.php';
try {
    $repo = new Repository();
    $data = $repo->getDashboardData();
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}
