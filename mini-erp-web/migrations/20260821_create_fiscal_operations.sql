-- FISCAL-06: Pedido x Espelho x Documento Fiscal Interno. Sem emissão e sem numeração fiscal.
CREATE TABLE IF NOT EXISTS fiscal_orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, operation_type VARCHAR(5) NOT NULL,
 establishment_id INT UNSIGNED NULL, person_id INT NULL, internal_code VARCHAR(50) NOT NULL DEFAULT '', operation_date DATE NOT NULL,
 commercial_status VARCHAR(12) NOT NULL DEFAULT 'SAVED', fiscal_status VARCHAR(20) NOT NULL DEFAULT 'NOT_CREATED',
 operation_nature VARCHAR(120) NOT NULL DEFAULT '', fiscal_model CHAR(2) NOT NULL DEFAULT '55', purpose VARCHAR(20) NOT NULL DEFAULT 'NORMAL',
 final_consumer TINYINT(1) NOT NULL DEFAULT 0, presence_indicator VARCHAR(2) NOT NULL DEFAULT '1',
 payment_condition VARCHAR(30) NOT NULL DEFAULT '', payment_method VARCHAR(30) NOT NULL DEFAULT '', first_due_date DATE NULL,
 notes TEXT NULL, seller_id INT NULL, carrier_id INT NULL, driver_id INT NULL, freight_mode VARCHAR(2) NOT NULL DEFAULT '9',
 discount_amount DECIMAL(18,2) NOT NULL DEFAULT 0, freight_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
 insurance_amount DECIMAL(18,2) NOT NULL DEFAULT 0, other_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
 products_total DECIMAL(18,2) NOT NULL DEFAULT 0, grand_total DECIMAL(18,2) NOT NULL DEFAULT 0,
 created_by INT NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY idx_fiscal_orders_tenant (tenant_id,operation_type,commercial_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS fiscal_order_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL, product_id INT NOT NULL,
 quantity DECIMAL(18,4) NOT NULL, unit_price DECIMAL(18,4) NOT NULL, discount_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
 freight_amount DECIMAL(18,2) NOT NULL DEFAULT 0, insurance_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
 other_amount DECIMAL(18,2) NOT NULL DEFAULT 0, gross_total DECIMAL(18,2) NOT NULL, net_total DECIMAL(18,2) NOT NULL,
 CONSTRAINT fk_fiscal_order_item_order FOREIGN KEY(order_id) REFERENCES fiscal_orders(id),
 CONSTRAINT fk_fiscal_order_item_product FOREIGN KEY(product_id) REFERENCES produtos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS fiscal_mirrors (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, source_order_id BIGINT UNSIGNED NOT NULL,
 snapshot_version INT UNSIGNED NOT NULL, operation_snapshot_json JSON NOT NULL, pending_json JSON NOT NULL,
 created_by INT NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_fiscal_mirror_version(tenant_id,source_order_id,snapshot_version),
 CONSTRAINT fk_fiscal_mirror_order FOREIGN KEY(source_order_id) REFERENCES fiscal_orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS fiscal_documents (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, source_order_id BIGINT UNSIGNED NOT NULL,
 document_version INT UNSIGNED NOT NULL DEFAULT 1, idempotency_key CHAR(64) NOT NULL, status VARCHAR(20) NOT NULL,
 pending_json JSON NOT NULL, issuer_snapshot_json JSON NOT NULL, recipient_snapshot_json JSON NOT NULL,
 payment_snapshot_json JSON NOT NULL, transport_snapshot_json JSON NOT NULL, totals_json JSON NOT NULL,
 created_by INT NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_fiscal_document_idempotency(tenant_id,idempotency_key),
 UNIQUE KEY uq_fiscal_document_version(tenant_id,source_order_id,document_version),
 CONSTRAINT fk_fiscal_document_order FOREIGN KEY(source_order_id) REFERENCES fiscal_orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS fiscal_document_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, fiscal_document_id BIGINT UNSIGNED NOT NULL, source_order_item_id BIGINT UNSIGNED NOT NULL,
 product_id INT NOT NULL, product_snapshot_json JSON NOT NULL, quantity_commercial DECIMAL(18,4) NOT NULL,
 quantity_taxable DECIMAL(18,4) NOT NULL, unit_value_commercial DECIMAL(18,4) NOT NULL,
 unit_value_taxable DECIMAL(18,4) NOT NULL, gross_total DECIMAL(18,2) NOT NULL, discount_amount DECIMAL(18,2) NOT NULL,
 freight_amount DECIMAL(18,2) NOT NULL, insurance_amount DECIMAL(18,2) NOT NULL, other_amount DECIMAL(18,2) NOT NULL,
 net_total DECIMAL(18,2) NOT NULL, included_in_total TINYINT(1) NOT NULL DEFAULT 1,
 fiscal_status VARCHAR(20) NOT NULL, tax_context_json JSON NOT NULL, tax_resolution_json JSON NULL,
 CONSTRAINT fk_fiscal_document_item_document FOREIGN KEY(fiscal_document_id) REFERENCES fiscal_documents(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
