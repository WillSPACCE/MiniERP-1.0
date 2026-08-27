CREATE TABLE IF NOT EXISTS fiscal_order_transport_details (
    order_id BIGINT UNSIGNED NOT NULL,
    tenant_id INT UNSIGNED NOT NULL,
    vehicle_plate VARCHAR(10) NULL,
    vehicle_state CHAR(2) NULL,
    vehicle_rntc VARCHAR(20) NULL,
    volume_quantity INT UNSIGNED NULL,
    volume_species VARCHAR(60) NULL,
    volume_brand VARCHAR(60) NULL,
    volume_numbering VARCHAR(60) NULL,
    gross_weight DECIMAL(15,3) NULL,
    net_weight DECIMAL(15,3) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (order_id),
    KEY idx_order_transport_tenant (tenant_id, order_id),
    CONSTRAINT fk_order_transport_order FOREIGN KEY (order_id)
        REFERENCES fiscal_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
