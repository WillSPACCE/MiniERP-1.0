-- Controle de estoque por local, lote e movimentacao auditavel.
ALTER TABLE produtos
 ADD COLUMN IF NOT EXISTS stock_control_by_lot TINYINT(1) NOT NULL DEFAULT 0 AFTER minimum_stock;

ALTER TABLE fiscal_order_items
 ADD COLUMN IF NOT EXISTS stock_lot_id BIGINT UNSIGNED NULL AFTER product_id;

CREATE TABLE IF NOT EXISTS stock_locations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id INT UNSIGNED NOT NULL,
 name VARCHAR(120) NOT NULL,
 code VARCHAR(40) NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_stock_location (tenant_id,code),
 KEY ix_stock_location_tenant (tenant_id,active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_lots (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id INT UNSIGNED NOT NULL,
 product_id INT NOT NULL,
 location_id BIGINT UNSIGNED NOT NULL,
 lot_code VARCHAR(100) NOT NULL,
 manufactured_at DATE NULL,
 expires_at DATE NULL,
 quantity_available DECIMAL(18,4) NOT NULL DEFAULT 0,
 blocked_sale TINYINT(1) NOT NULL DEFAULT 0,
 block_reason VARCHAR(500) NULL,
 status VARCHAR(24) NOT NULL DEFAULT 'ACTIVE',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_stock_lot_location (tenant_id,product_id,location_id,lot_code),
 KEY ix_stock_lot_product (tenant_id,product_id,status),
 KEY ix_stock_lot_expiry (tenant_id,expires_at),
 CONSTRAINT fk_stock_lot_product FOREIGN KEY(product_id) REFERENCES produtos(id),
 CONSTRAINT fk_stock_lot_location FOREIGN KEY(location_id) REFERENCES stock_locations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_movements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id INT UNSIGNED NOT NULL,
 product_id INT NOT NULL,
 source_lot_id BIGINT UNSIGNED NULL,
 destination_lot_id BIGINT UNSIGNED NULL,
 movement_type VARCHAR(24) NOT NULL,
 quantity DECIMAL(18,4) NOT NULL,
 balance_before DECIMAL(18,4) NULL,
 balance_after DECIMAL(18,4) NULL,
 reference_type VARCHAR(40) NULL,
 reference_id BIGINT UNSIGNED NULL,
 reason VARCHAR(500) NOT NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY ix_stock_movement_product (tenant_id,product_id,created_at),
 KEY ix_stock_movement_reference (tenant_id,reference_type,reference_id),
 CONSTRAINT fk_stock_movement_product FOREIGN KEY(product_id) REFERENCES produtos(id),
 CONSTRAINT fk_stock_movement_source FOREIGN KEY(source_lot_id) REFERENCES stock_lots(id),
 CONSTRAINT fk_stock_movement_destination FOREIGN KEY(destination_lot_id) REFERENCES stock_lots(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO stock_locations(tenant_id,name,code,active)
SELECT id,'Estoque principal','PRINCIPAL',1 FROM (SELECT DISTINCT tenant_id id FROM fiscal_orders UNION SELECT DISTINCT tenant_id id FROM fiscal_certificates) tenants
ON DUPLICATE KEY UPDATE name=VALUES(name),active=1;
