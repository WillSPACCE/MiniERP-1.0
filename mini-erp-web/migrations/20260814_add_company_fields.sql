-- Migration: Add address/phone/ibge fields to tenants table
-- Execute this SQL in the main database used by the application (where table `tenants` exists).

ALTER TABLE tenants
    ADD COLUMN IF NOT EXISTS cep VARCHAR(16) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS uf VARCHAR(4) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS logradouro VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS numero VARCHAR(64) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS complemento VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS bairro VARCHAR(128) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS telefone VARCHAR(48) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS codigo_ibge VARCHAR(32) DEFAULT NULL;

-- Note: If your MySQL version doesn't support `IF NOT EXISTS` on ADD COLUMN, run the ALTER TABLE statements
-- for each column individually or check information_schema before altering.
