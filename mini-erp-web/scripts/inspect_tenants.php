<?php
$config = require __DIR__ . '/../config.php';
$db = $config['db'];
try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['database']);
    $pdo = new PDO($dsn, $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $rows = $pdo->query('SELECT id, nome_fantasia, razao_social, cnpj, db_name, COALESCE(blocked,0) AS blocked FROM tenants ORDER BY id DESC')->fetchAll();
    echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}
