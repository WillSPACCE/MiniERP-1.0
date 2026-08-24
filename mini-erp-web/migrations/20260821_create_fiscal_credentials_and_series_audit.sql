-- FISCAL-02A/02B: aditiva, idempotente e sem dados/segredos embutidos.
CREATE TABLE IF NOT EXISTS fiscal_certificates (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id BIGINT UNSIGNED NOT NULL, establishment_id BIGINT UNSIGNED NOT NULL,
 storage_reference VARCHAR(500) NOT NULL, file_name VARCHAR(255) NOT NULL, sha256 CHAR(64) NOT NULL, fingerprint_sha256 CHAR(64) NOT NULL,
 subject TEXT NOT NULL, issuer TEXT NOT NULL, serial_number VARCHAR(255) NOT NULL, tax_id VARCHAR(32) NULL,
 valid_from DATETIME NOT NULL, valid_until DATETIME NOT NULL, status VARCHAR(32) NOT NULL, secret_reference VARCHAR(500) NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_active_certificate (tenant_id,establishment_id,active), KEY ix_certificate_scope (tenant_id,establishment_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS fiscal_series_audit (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id BIGINT UNSIGNED NOT NULL, establishment_id BIGINT UNSIGNED NOT NULL,
 fiscal_series_id BIGINT UNSIGNED NOT NULL, model CHAR(2) NOT NULL, series INT UNSIGNED NOT NULL,
 old_next_number BIGINT UNSIGNED NULL, new_next_number BIGINT UNSIGNED NOT NULL, changed_by BIGINT UNSIGNED NOT NULL,
 reason VARCHAR(500) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY ix_series_audit_scope (tenant_id,establishment_id,model,series)
) ENGINE=InnoDB;
