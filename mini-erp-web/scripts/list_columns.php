<?php
require __DIR__ . '/../app/Database.php';
$pdo = Database::getConnection();
$stmt = $pdo->query('SHOW COLUMNS FROM usuarios');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo $c['Field'] . "\n";
}
