-- FISCAL-02C: histórico e auditoria operacional, aditiva e sem dados reais.
ALTER TABLE fiscal_certificates DROP INDEX IF EXISTS uq_active_certificate;
ALTER TABLE fiscal_certificates ADD COLUMN IF NOT EXISTS uploaded_by BIGINT UNSIGNED NULL AFTER secret_reference;
ALTER TABLE fiscal_certificates ADD COLUMN IF NOT EXISTS deactivated_by BIGINT UNSIGNED NULL AFTER uploaded_by;
ALTER TABLE fiscal_certificates ADD COLUMN IF NOT EXISTS deactivated_at DATETIME NULL AFTER deactivated_by;
CREATE INDEX IF NOT EXISTS ix_active_certificate ON fiscal_certificates (tenant_id,establishment_id,active);
CREATE TABLE IF NOT EXISTS fiscal_certificate_audit (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,tenant_id BIGINT UNSIGNED NOT NULL,establishment_id BIGINT UNSIGNED NOT NULL,certificate_id BIGINT UNSIGNED NOT NULL,action VARCHAR(32) NOT NULL,actor_id BIGINT UNSIGNED NOT NULL,details_json LONGTEXT NULL,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,KEY ix_certificate_audit_scope (tenant_id,establishment_id,certificate_id)) ENGINE=InnoDB;
ALTER TABLE fiscal_series_audit ADD COLUMN IF NOT EXISTS action VARCHAR(32) NOT NULL DEFAULT 'UPDATE' AFTER fiscal_series_id;
ALTER TABLE fiscal_series_audit ADD COLUMN IF NOT EXISTS before_json LONGTEXT NULL AFTER reason;
ALTER TABLE fiscal_series_audit ADD COLUMN IF NOT EXISTS after_json LONGTEXT NULL AFTER before_json;
