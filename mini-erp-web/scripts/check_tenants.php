<?php
$c = require __DIR__ . '/../config.php';
$db = $c['db'];
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['database']);
try {
    $pdo = new PDO($dsn, $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $id = $argv[1] ?? 5;
    $stmt = $pdo->prepare('SELECT * FROM tenants WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int)$id]);
    $row = $stmt->fetch();
    if ($row) {
        echo "FOUND:\n" . json_encode($row, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "NOT FOUND tenant id={$id}\n";
    }
    echo "\nLAST 10 tenants:\n";
    $q = $pdo->query('SELECT id,nome_fantasia,razao_social,cnpj,slug,db_name FROM tenants ORDER BY id DESC LIMIT 10');
    foreach ($q as $r) echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
} catch (Throwable $e) {
    echo 'ERR: ' . $e->getMessage() . "\n";
    exit(1);
}
