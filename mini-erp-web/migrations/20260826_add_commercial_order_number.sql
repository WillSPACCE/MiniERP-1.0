-- Migration: add commercial order sequence and order_number column
ALTER TABLE fiscal_orders
    ADD COLUMN IF NOT EXISTS order_number BIGINT UNSIGNED NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS commercial_order_sequences (
    tenant_id INT UNSIGNED NOT NULL,
    establishment_id INT UNSIGNED NOT NULL DEFAULT 0,
    last_number BIGINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (tenant_id, establishment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- add unique index to ensure per-tenant/establishment uniqueness
CREATE UNIQUE INDEX IF NOT EXISTS uq_fiscal_orders_tenant_establishment_order_number ON fiscal_orders (order_number, tenant_id, establishment_id);
