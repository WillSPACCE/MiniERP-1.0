<?php
require __DIR__ . '/../app/Database.php';
require __DIR__ . '/../app/Repository.php';

$repo = new Repository();
$data = [
    'nome_fantasia' => 'Empresa Portavel Teste',
    'razao_social' => 'Empresa Portavel Teste LTDA',
    'cnpj' => '12345678000199',
    'cep' => '01001000',
    'uf' => 'SP',
    'municipio' => 'Sao Paulo',
];

try {
    $repo->saveCompany($data);
    echo "Tenant criado/atualizado com sucesso.\n";
} catch (Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . PHP_EOL;
}
