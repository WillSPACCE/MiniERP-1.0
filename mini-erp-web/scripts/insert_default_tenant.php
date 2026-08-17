<?php
require __DIR__ . '/../app/Database.php';
$pdo = Database::getConnection();
$uuid = bin2hex(random_bytes(8));
$stmt = $pdo->prepare('INSERT INTO tenants (uuid, nome_fantasia, razao_social, cnpj, slug, data) VALUES (:uuid, :nome, :razao, :cnpj, :slug, :data)');
$stmt->execute([
    'uuid' => $uuid,
    'nome' => 'Default Tenant',
    'razao' => 'Default Tenant',
    'cnpj' => '',
    'slug' => 'default',
    'data' => json_encode([]),
]);
echo "Tenant inserido\n";
