<?php
// Script para popular cada banco de tenant com produtos e funcionários de demonstração.
// Uso: C:\xampp\php\php.exe C:\xampp\htdocs\MiniRP\mini-erp-web\scripts\seed_tenants_demo.php

$baseDir = __DIR__ . '/..';
$config = require $baseDir . '/config.php';
$dbConf = $config['db'];
$serverDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbConf['host'], $dbConf['port']);

try {
    $admin = new PDO($serverDsn, $dbConf['username'], $dbConf['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (Throwable $e) {
    echo "Falha ao conectar ao servidor MySQL: " . $e->getMessage() . "\n";
    exit(1);
}

// Seleciona o banco principal e busca tenants ativos com db_name
$mainDb = $dbConf['database'];
try {
    $admin->exec("USE `{$mainDb}`");
} catch (Throwable $e) {
    echo "Aviso: não foi possível executar USE {$mainDb}: " . $e->getMessage() . "\n";
}

$tenants = $admin->query("SELECT id, nome_fantasia, db_name FROM tenants WHERE db_name IS NOT NULL AND status = 'ativo'")->fetchAll();
if (!$tenants) {
    echo "Nenhum tenant com db_name encontrado.\n";
    exit(0);
}

$seedByTenant = [
    // db_name => [products..., users...]
];

// Definição por ordem (assume que tenants estão na ordem criada)
$productsLists = [
    [
        ['nome' => 'Notebook Pro', 'codigo' => 'ELECT-NB-001', 'preco' => 6599.90, 'estoque_atual' => 5],
        ['nome' => 'Mouse Wireless', 'codigo' => 'ELECT-MO-001', 'preco' => 79.90, 'estoque_atual' => 50],
        ['nome' => 'Headphones Studio', 'codigo' => 'ELECT-HP-001', 'preco' => 499.90, 'estoque_atual' => 20],
    ],
    [
        ['nome' => 'Café Premium 1kg', 'codigo' => 'GROC-COF-001', 'preco' => 39.90, 'estoque_atual' => 100],
        ['nome' => 'Açúcar Cristal 1kg', 'codigo' => 'GROC-SUG-001', 'preco' => 4.50, 'estoque_atual' => 200],
        ['nome' => 'Azeite Extra Virgem 500ml', 'codigo' => 'GROC-OIL-001', 'preco' => 24.90, 'estoque_atual' => 60],
    ],
    [
        ['nome' => 'Papel A4 500pg', 'codigo' => 'OFFC-PPR-001', 'preco' => 29.90, 'estoque_atual' => 80],
        ['nome' => 'Caneta Esferográfica Pack 10', 'codigo' => 'OFFC-PEN-001', 'preco' => 9.90, 'estoque_atual' => 150],
        ['nome' => 'Grampeador Metal', 'codigo' => 'OFFC-STR-001', 'preco' => 34.90, 'estoque_atual' => 25],
    ],
];

$usersLists = [
    [
        ['nome' => 'Gerente Eletrônicos', 'email' => 'gerente1@localhost', 'senha' => 'password', 'role' => 'manager'],
        ['nome' => 'Vendedor A', 'email' => 'vendedor1@localhost', 'senha' => 'password', 'role' => 'user'],
    ],
    [
        ['nome' => 'Gerente Supermercado', 'email' => 'gerente2@localhost', 'senha' => 'password', 'role' => 'manager'],
        ['nome' => 'Vendedor B', 'email' => 'vendedor2@localhost', 'senha' => 'password', 'role' => 'user'],
    ],
    [
        ['nome' => 'Gerente Escritório', 'email' => 'gerente3@localhost', 'senha' => 'password', 'role' => 'manager'],
        ['nome' => 'Vendedor C', 'email' => 'vendedor3@localhost', 'senha' => 'password', 'role' => 'user'],
    ],
];

$index = 0;
foreach ($tenants as $t) {
    $index++;
    $db = $t['db_name'];
    echo "Semente: tenant id={$t['id']} nome={$t['nome_fantasia']} db={$db}\n";
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConf['host'], $dbConf['port'], $db);
        $pdo = new PDO($dsn, $dbConf['username'], $dbConf['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    } catch (Throwable $e) {
        echo "Falha conectar em {$db}: " . $e->getMessage() . "\n";
        continue;
    }

    $pList = $productsLists[($index - 1) % count($productsLists)];
    $uList = $usersLists[($index - 1) % count($usersLists)];

    // Inserir produtos
    $insertProd = $pdo->prepare('INSERT INTO produtos (nome, codigo, ncm, unidade, preco, estoque_atual, status, company_id) VALUES (:nome, :codigo, :ncm, :unidade, :preco, :estoque, :status, NULL)');
    foreach ($pList as $p) {
        try {
            $insertProd->execute([
                'nome' => $p['nome'],
                'codigo' => $p['codigo'],
                'ncm' => '',
                'unidade' => 'UN',
                'preco' => $p['preco'],
                'estoque' => $p['estoque_atual'],
                'status' => 'ativo',
            ]);
            echo "  - Produto inserido: {$p['nome']}\n";
        } catch (Throwable $e) {
            echo "  - Aviso produto: " . $e->getMessage() . "\n";
        }
    }

    // Inserir usuários no DB do tenant
    $insertUser = $pdo->prepare('INSERT INTO usuarios (nome, email, senha, role, status, company_id) VALUES (:nome, :email, :senha, :role, :status, NULL)');
    foreach ($uList as $u) {
        try {
            $hash = password_hash($u['senha'], PASSWORD_DEFAULT);
            $insertUser->execute([
                'nome' => $u['nome'],
                'email' => $u['email'],
                'senha' => $hash,
                'role' => $u['role'],
                'status' => 'ativo',
            ]);
            echo "  - Usuário inserido: {$u['email']} / senha: {$u['senha']}\n";
        } catch (Throwable $e) {
            echo "  - Aviso usuário: " . $e->getMessage() . "\n";
        }
    }

    // opcional: criar alguns clientes iniciais
    $insertCliente = $pdo->prepare('INSERT INTO clientes (nome, email, telefone, cpf_cnpj, status) VALUES (:nome, :email, :telefone, :cpf, :status)');
    try {
        $insertCliente->execute([
            'nome' => 'Cliente Exemplo ' . $index,
            'email' => "cliente{$index}@example.com",
            'telefone' => '',
            'cpf' => sprintf('%011d', 10000000000 + $index),
            'status' => 'ativo',
        ]);
        echo "  - Cliente exemplo inserido.\n";
    } catch (Throwable $e) {
        echo "  - Aviso cliente: " . $e->getMessage() . "\n";
    }
}

echo "Seed finalizada.\n";
