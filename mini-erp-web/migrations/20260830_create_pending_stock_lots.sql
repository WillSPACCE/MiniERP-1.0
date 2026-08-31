-- Lotes recebidos que aguardam vinculo com um produto cadastrado.
CREATE TABLE IF NOT EXISTS stock_pending_lots (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id INT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NOT NULL,
 lot_code VARCHAR(100) NOT NULL,
 quantity_received DECIMAL(18,4) NOT NULL DEFAULT 0,
 manufactured_at DATE NULL,
 expires_at DATE NULL,
 source_document VARCHAR(120) NULL,
 notes VARCHAR(500) NULL,
 status VARCHAR(24) NOT NULL DEFAULT 'PENDING',
 linked_product_id INT NULL,
 linked_lot_id BIGINT UNSIGNED NULL,
 linked_at DATETIME NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY ix_pending_lot_tenant_status (tenant_id,status,created_at),
 KEY ix_pending_lot_code (tenant_id,lot_code),
 CONSTRAINT fk_pending_lot_location FOREIGN KEY(location_id) REFERENCES stock_locations(id),
 CONSTRAINT fk_pending_lot_product FOREIGN KEY(linked_product_id) REFERENCES produtos(id),
 CONSTRAINT fk_pending_lot_stock_lot FOREIGN KEY(linked_lot_id) REFERENCES stock_lots(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
