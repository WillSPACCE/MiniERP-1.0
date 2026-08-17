<?php
require __DIR__ . '/../app/Database.php';
require __DIR__ . '/../app/Repository.php';

$repo = new Repository();

// connect to main DB and find tenants without db_name
$config = require __DIR__ . '/../config.php';
$db = $config['db'];
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['database']);
$pdo = new PDO($dsn, $db['username'], $db['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$rows = $pdo->query("SELECT id, nome_fantasia, slug FROM tenants WHERE db_name IS NULL OR db_name = '' ORDER BY id ASC")->fetchAll();
if (empty($rows)) {
    echo "Nenhum tenant pendente de provisionamento.\n";
    exit(0);
}

foreach ($rows as $r) {
    $id = (int)$r['id'];
    $name = $r['nome_fantasia'] ?? $r['slug'] ?? ('tenant' . $id);
    echo "Provisionando tenant {$id} ({$name})... ";
    try {
        $dbn = $repo->provisionTenant($id);
        echo "OK -> {$dbn}\n";
    } catch (Throwable $e) {
        echo "ERRO: " . $e->getMessage() . "\n";
    }
}
