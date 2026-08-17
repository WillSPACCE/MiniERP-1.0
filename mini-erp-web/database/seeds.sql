INSERT IGNORE INTO clientes (id, nome, email, telefone, cidade, status) VALUES
(1, 'Maria Silva', 'maria@empresa.com', '(11) 99999-1111', 'São Paulo', 'ativo'),
(2, 'José Almeida', 'jose@empresa.com', '(11) 98888-2222', 'Rio de Janeiro', 'ativo'),
(3, 'Ana Costa', 'ana@empresa.com', '(11) 97777-3333', 'Belo Horizonte', 'ativo');

INSERT IGNORE INTO produtos (id, nome, codigo, categoria, preco, estoque_atual, status) VALUES
(1, 'Teclado Mecânico', 'TEC-001', 'Periféricos', 299.90, 18, 'ativo'),
(2, 'Mouse Gamer', 'MOU-010', 'Periféricos', 189.00, 24, 'ativo'),
(3, 'Monitor 24', 'MON-024', 'Eletrônicos', 899.90, 8, 'ativo'),
(4, 'Notebook Pro', 'NBP-500', 'Computadores', 3499.00, 5, 'ativo');

INSERT IGNORE INTO vendas (id, cliente_id, data_venda, total, status) VALUES
(1, 1, '2026-08-01', 299.90, 'finalizada'),
(2, 2, '2026-08-03', 189.00, 'finalizada');

INSERT IGNORE INTO itens_venda (id, venda_id, produto_id, quantidade, preco_unitario, subtotal) VALUES
(1, 1, 1, 1, 299.90, 299.90),
(2, 2, 2, 1, 189.00, 189.00);
