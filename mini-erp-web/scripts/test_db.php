<?php
require __DIR__ . '/../app/Database.php';
require __DIR__ . '/../app/Repository.php';

$repo = new Repository();

// cria produto de teste se não existir
$produtos = $repo->listProdutos();
if (count($produtos) === 0) {
    $repo->saveProduto(['nome' => 'Produto Teste', 'codigo' => 'PT-001', 'preco' => '10.00', 'estoque_atual' => 100]);
    echo "Produto de teste criado\n";
    $produtos = $repo->listProdutos();
}
$produto = $produtos[0];
$pid = (int)$produto['id'];

echo "Usando produto ID: $pid nome: {$produto['nome']}\n";

// salvar impostos
$repo->saveProductTaxes($pid, ['ipi'=>'1.5','icms'=>'18','pis'=>'1.65','cofins'=>'7.6']);
echo "Impostos salvos no DB para produto $pid\n";

// criar cliente se não existir
$clientes = $repo->listClientes();
if (count($clientes) === 0) {
    $repo->saveCliente(['nome'=>'Cliente Teste','email'=>'teste@example.com']);
    echo "Cliente teste criado\n";
    $clientes = $repo->listClientes();
}
$cliente = $clientes[0];
$cid = (int)$cliente['id'];
echo "Usando cliente ID: $cid nome: {$cliente['nome']}\n";

// criar venda
try {
    $post = ['cliente_id' => $cid];
    $itens = [['produto_id' => $pid, 'quantidade' => 2]];
    $vendaId = $repo->createSale($post, $itens);
    echo "Venda criada com ID: $vendaId\n";
} catch (Throwable $e) {
    echo "Erro ao criar venda: " . $e->getMessage() . "\n";
}

// listar impostos via repo
$taxes = $repo->getProductTaxes($pid);
echo "Impostos lidos: " . json_encode($taxes) . "\n";

?>