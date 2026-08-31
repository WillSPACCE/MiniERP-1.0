-- Data canonica para filtros e ordenacao do cadastro de produtos.
ALTER TABLE produtos
 ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
