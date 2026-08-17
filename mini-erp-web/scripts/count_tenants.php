<?php
require __DIR__ . '/../app/Database.php';
$pdo = Database::getConnection();
$row = $pdo->query('SELECT COUNT(*) AS c FROM tenants')->fetch();
echo $row['c'] . PHP_EOL;
