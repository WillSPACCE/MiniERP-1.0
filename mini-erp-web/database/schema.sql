CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefone VARCHAR(50) DEFAULT '',
    cpf_cnpj VARCHAR(20) DEFAULT '',
    inscricao_estadual VARCHAR(50) DEFAULT '',
    logradouro VARCHAR(150) DEFAULT '',
    numero VARCHAR(20) DEFAULT '',
    complemento VARCHAR(100) DEFAULT '',
    bairro VARCHAR(100) DEFAULT '',
    municipio VARCHAR(100) DEFAULT '',
    codigo_municipal VARCHAR(20) DEFAULT '',
    uf VARCHAR(2) DEFAULT '',
    cep VARCHAR(20) DEFAULT '',
    cidade VARCHAR(100) DEFAULT '',
    status VARCHAR(20) DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    ncm VARCHAR(20) DEFAULT '',
    cest VARCHAR(20) DEFAULT '',
    unidade VARCHAR(10) DEFAULT 'UN',
    gtin VARCHAR(50) DEFAULT '',
    cfop_padrao VARCHAR(20) DEFAULT '',
    categoria VARCHAR(80) DEFAULT '',
    preco DECIMAL(10,2) NOT NULL DEFAULT 0,
    estoque_atual INT NOT NULL DEFAULT 0,
    status VARCHAR(20) DEFAULT 'ativo',
    company_id INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    data_venda DATE NOT NULL,
    empresa_cnpj VARCHAR(20) DEFAULT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    status VARCHAR(20) DEFAULT 'finalizada',
    CONSTRAINT fk_vendas_clientes FOREIGN KEY (cliente_id) REFERENCES clientes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS itens_venda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venda_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_itens_venda FOREIGN KEY (venda_id) REFERENCES vendas(id),
    CONSTRAINT fk_itens_produtos FOREIGN KEY (produto_id) REFERENCES produtos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_taxes (
    product_id INT PRIMARY KEY,
    ipi VARCHAR(50) DEFAULT '',
    icms VARCHAR(50) DEFAULT '',
    pis VARCHAR(50) DEFAULT '',
    cofins VARCHAR(50) DEFAULT '',
    CONSTRAINT fk_taxes_produto FOREIGN KEY (product_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cfops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    descricao VARCHAR(255) NOT NULL,
    natureza VARCHAR(80) DEFAULT '',
    aplicacao VARCHAR(80) DEFAULT '',
    status VARCHAR(20) DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    nome_fantasia VARCHAR(150) DEFAULT '',
    cpf_cnpj VARCHAR(20) DEFAULT '',
    inscricao_estadual VARCHAR(50) DEFAULT '',
    email VARCHAR(150) DEFAULT '',
    telefone VARCHAR(50) DEFAULT '',
    cep VARCHAR(20) DEFAULT '',
    logradouro VARCHAR(150) DEFAULT '',
    numero VARCHAR(20) DEFAULT '',
    complemento VARCHAR(100) DEFAULT '',
    bairro VARCHAR(100) DEFAULT '',
    municipio VARCHAR(100) DEFAULT '',
    uf VARCHAR(2) DEFAULT '',
    cidade VARCHAR(100) DEFAULT '',
    status VARCHAR(20) DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS motoristas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(20) DEFAULT '',
    cnh VARCHAR(20) DEFAULT '',
    categoria_cnh VARCHAR(10) DEFAULT '',
    vencimento_cnh DATE DEFAULT NULL,
    telefone VARCHAR(50) DEFAULT '',
    status VARCHAR(20) DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transportadoras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    nome_fantasia VARCHAR(150) DEFAULT '',
    cpf_cnpj VARCHAR(20) DEFAULT '',
    inscricao_estadual VARCHAR(50) DEFAULT '',
    email VARCHAR(150) DEFAULT '',
    telefone VARCHAR(50) DEFAULT '',
    cep VARCHAR(20) DEFAULT '',
    logradouro VARCHAR(150) DEFAULT '',
    numero VARCHAR(20) DEFAULT '',
    complemento VARCHAR(100) DEFAULT '',
    bairro VARCHAR(100) DEFAULT '',
    municipio VARCHAR(100) DEFAULT '',
    uf VARCHAR(2) DEFAULT '',
    cidade VARCHAR(100) DEFAULT '',
    status VARCHAR(20) DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- tabela de usuários/funcionários (autenticação básica)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT '',
    status VARCHAR(20) DEFAULT 'ativo',
    company_id INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de empresas (centralizada)
-- Tenants table (multi-empresa)
CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(40) NOT NULL,
    nome_fantasia VARCHAR(255) DEFAULT '',
    razao_social VARCHAR(255) DEFAULT '',
    cnpj VARCHAR(32) DEFAULT '',
    slug VARCHAR(255) NOT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'ativo',
    data JSON DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tenants_slug (slug),
    UNIQUE KEY uq_tenants_uuid (uuid),
    UNIQUE KEY uq_tenants_cnpj (cnpj)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- tabela para tokens de redefinição de senha (dev-friendly)
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
