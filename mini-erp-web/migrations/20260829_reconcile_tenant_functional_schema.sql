-- Reconciliação aditiva do esquema funcional dos tenants.
-- Preserva colunas legadas e completa o cadastro usado pela interface atual.
ALTER TABLE clientes
 ADD COLUMN IF NOT EXISTS estado VARCHAR(100) DEFAULT '',
 ADD COLUMN IF NOT EXISTS vendedor VARCHAR(150) DEFAULT '',
 ADD COLUMN IF NOT EXISTS status_pagamento VARCHAR(50) DEFAULT '',
 ADD COLUMN IF NOT EXISTS pagamento VARCHAR(50) DEFAULT '',
 ADD COLUMN IF NOT EXISTS anvisa_data_venc DATE NULL,
 ADD COLUMN IF NOT EXISTS anvisa_codigo VARCHAR(50) DEFAULT '',
 ADD COLUMN IF NOT EXISTS comissao_percentual VARCHAR(20) DEFAULT '',
 ADD COLUMN IF NOT EXISTS comissao_volume VARCHAR(20) DEFAULT '',
 ADD COLUMN IF NOT EXISTS forma_pagamento VARCHAR(50) DEFAULT '',
 ADD COLUMN IF NOT EXISTS limite_credito DECIMAL(10,2) DEFAULT 0.00,
 ADD COLUMN IF NOT EXISTS desconto DECIMAL(10,2) DEFAULT 0.00,
 ADD COLUMN IF NOT EXISTS funeral VARCHAR(20) DEFAULT '',
 ADD COLUMN IF NOT EXISTS transportadora VARCHAR(150) DEFAULT '',
 ADD COLUMN IF NOT EXISTS placa VARCHAR(20) DEFAULT '',
 ADD COLUMN IF NOT EXISTS placa_uf VARCHAR(10) DEFAULT '',
 ADD COLUMN IF NOT EXISTS antt VARCHAR(50) DEFAULT '',
 ADD COLUMN IF NOT EXISTS frete DECIMAL(10,2) DEFAULT 0.00,
 ADD COLUMN IF NOT EXISTS valor_frete DECIMAL(10,2) DEFAULT 0.00,
 ADD COLUMN IF NOT EXISTS data LONGTEXT NULL;

ALTER TABLE fornecedores
 ADD COLUMN IF NOT EXISTS data LONGTEXT NULL;

ALTER TABLE fiscal_number_reservations
 ADD COLUMN IF NOT EXISTS number BIGINT UNSIGNED NULL,
 ADD COLUMN IF NOT EXISTS fiscal_number BIGINT UNSIGNED NULL,
 ADD COLUMN IF NOT EXISTS cnf VARCHAR(32) NULL,
 ADD COLUMN IF NOT EXISTS numeric_code CHAR(8) NULL;

UPDATE fiscal_number_reservations
 SET number = COALESCE(number, fiscal_number)
 WHERE number IS NULL AND fiscal_number IS NOT NULL;

UPDATE fiscal_number_reservations
 SET cnf = COALESCE(cnf, numeric_code)
 WHERE cnf IS NULL AND numeric_code IS NOT NULL;

UPDATE fiscal_number_reservations
 SET fiscal_number = COALESCE(fiscal_number, number),
     numeric_code = COALESCE(numeric_code, LEFT(cnf, 8));

ALTER TABLE fiscal_artifacts
 ADD COLUMN IF NOT EXISTS size BIGINT UNSIGNED NULL,
 ADD COLUMN IF NOT EXISTS size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0;

UPDATE fiscal_artifacts
 SET size_bytes = IF(size_bytes = 0 AND size IS NOT NULL, size, size_bytes),
     size = COALESCE(size, size_bytes);
