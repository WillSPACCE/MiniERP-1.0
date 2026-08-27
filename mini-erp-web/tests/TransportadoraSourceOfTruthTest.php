<?php
declare(strict_types=1);

session_start();
$_SESSION['erp_user_id'] = 9;
$_SESSION['erp_tenant_id'] = 14;
$_SESSION['user_id'] = 9;
$_SESSION['tenant_id'] = 14;

$config = require __DIR__ . '/../config.php';
$db = $config['db'];
$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname=mini_erp_tenant_14;charset=utf8mb4",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$doc = '77889900123455';
$pdo->prepare('DELETE FROM clientes WHERE cpf_cnpj = :doc')->execute(['doc' => $doc]);
$pdo->prepare(
    'INSERT INTO clientes (nome, nome_fantasia, cpf_cnpj, tipo_pessoa, pessoa_fisica, status, email, telefone, fone_principal, cidade, uf, role_customer, role_supplier, role_seller, role_carrier) VALUES (:nome, :nome_fantasia, :cpf_cnpj, :tipo_pessoa, :pessoa_fisica, :status, :email, :telefone, :fone_principal, :cidade, :uf, :role_customer, :role_supplier, :role_seller, :role_carrier)'
)->execute([
    'nome' => 'Transportadora de Teste',
    'nome_fantasia' => 'Transportadora de Teste',
    'cpf_cnpj' => $doc,
    'tipo_pessoa' => 'transportadora',
    'pessoa_fisica' => 'nao',
    'status' => 'ativo',
    'email' => 'carrier.test@local',
    'telefone' => '41999998888',
    'fone_principal' => '41999998888',
    'cidade' => 'Curitiba',
    'uf' => 'PR',
    'role_customer' => 0,
    'role_supplier' => 0,
    'role_seller' => 0,
    'role_carrier' => 1,
]);

require_once __DIR__ . '/../app/Repository.php';
$repo = new Repository($pdo, false);
$rows = $repo->listTransportadoras();

foreach ($rows as $row) {
    if (($row['cpf_cnpj'] ?? '') === $doc || ($row['nome'] ?? '') === 'Transportadora de Teste') {
        echo "TransportadoraSourceOfTruthTest OK\n";
        exit(0);
    }
}

throw new RuntimeException('Transportadora cadastrada via pessoa não apareceu em listTransportadoras().');
