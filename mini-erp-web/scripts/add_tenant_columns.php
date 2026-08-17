<?php
require __DIR__ . '/../app/Database.php';
$pdo = Database::getConnection();
$tables = ['usuarios','clientes','produtos','vendas','itens_venda','product_taxes','fornecedores','motoristas','transportadoras'];
foreach ($tables as $t) {
    try {
        $pdo->exec("ALTER TABLE $t ADD COLUMN tenant_id INT NULL");
        echo "Added tenant_id to $t\n";
    } catch (Throwable $e) {
        echo "Skipping $t: " . $e->getMessage() . "\n";
    }
}
// Atualiza registros existentes para tenant_id = 1
try {
    foreach ($tables as $t) {
        $pdo->exec("UPDATE $t SET tenant_id = 1 WHERE tenant_id IS NULL");
    }
    echo "Populated tenant_id = 1 for existing rows\n";
} catch (Throwable $e) {
    echo "Update failed: " . $e->getMessage() . "\n";
}
