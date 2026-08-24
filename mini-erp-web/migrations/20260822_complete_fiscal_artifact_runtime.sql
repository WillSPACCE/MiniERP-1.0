ALTER TABLE fiscal_number_reservations
    ADD COLUMN IF NOT EXISTS fiscal_document_version INT UNSIGNED NOT NULL DEFAULT 1 AFTER fiscal_document_id,
    ADD COLUMN IF NOT EXISTS fiscal_series_id BIGINT UNSIGNED NULL AFTER fiscal_document_version,
    ADD COLUMN IF NOT EXISTS cnf VARCHAR(32) NULL AFTER environment,
    ADD COLUMN IF NOT EXISTS access_key VARCHAR(64) NULL AFTER cnf,
    ADD COLUMN IF NOT EXISTS idempotency_key VARCHAR(128) NULL AFTER status,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

CREATE INDEX IF NOT EXISTS idx_reservation_document ON fiscal_number_reservations (tenant_id, fiscal_document_id);
CREATE INDEX IF NOT EXISTS idx_reservation_series ON fiscal_number_reservations (fiscal_series_id);
CREATE INDEX IF NOT EXISTS idx_reservation_idempotency ON fiscal_number_reservations (tenant_id, idempotency_key);
CREATE UNIQUE INDEX IF NOT EXISTS uq_reservation_document_version ON fiscal_number_reservations (tenant_id, fiscal_document_id, fiscal_document_version);
CREATE UNIQUE INDEX IF NOT EXISTS uq_reservation_number ON fiscal_number_reservations (tenant_id, establishment_id, model, series, number);
CREATE UNIQUE INDEX IF NOT EXISTS uq_reservation_access_key ON fiscal_number_reservations (tenant_id, access_key);

ALTER TABLE fiscal_artifacts
    ADD COLUMN IF NOT EXISTS fiscal_document_version INT UNSIGNED NOT NULL DEFAULT 1 AFTER fiscal_document_id,
    ADD COLUMN IF NOT EXISTS certificate_id BIGINT UNSIGNED NULL AFTER fiscal_document_version,
    ADD COLUMN IF NOT EXISTS number_reservation_id BIGINT UNSIGNED NULL AFTER certificate_id,
    ADD COLUMN IF NOT EXISTS model CHAR(2) NOT NULL DEFAULT '55' AFTER number_reservation_id,
    ADD COLUMN IF NOT EXISTS environment TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER model,
    ADD COLUMN IF NOT EXISTS series INT UNSIGNED NOT NULL DEFAULT 1 AFTER environment,
    ADD COLUMN IF NOT EXISTS number BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER series,
    ADD COLUMN IF NOT EXISTS access_key VARCHAR(64) NOT NULL DEFAULT '' AFTER number,
    ADD COLUMN IF NOT EXISTS schema_package VARCHAR(80) NOT NULL DEFAULT 'nfe' AFTER status,
    ADD COLUMN IF NOT EXISTS schema_version VARCHAR(32) NOT NULL DEFAULT '010e-v1.02' AFTER schema_package,
    ADD COLUMN IF NOT EXISTS schema_checksum CHAR(64) NOT NULL DEFAULT '' AFTER schema_version,
    ADD COLUMN IF NOT EXISTS sha256 CHAR(64) NOT NULL DEFAULT '' AFTER storage_reference,
    ADD COLUMN IF NOT EXISTS size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER sha256,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

CREATE INDEX IF NOT EXISTS idx_artifact_document ON fiscal_artifacts (tenant_id, fiscal_document_id);
CREATE INDEX IF NOT EXISTS idx_artifact_access_key ON fiscal_artifacts (tenant_id, access_key);
CREATE INDEX IF NOT EXISTS idx_artifact_reservation ON fiscal_artifacts (number_reservation_id);
CREATE INDEX IF NOT EXISTS idx_artifact_certificate ON fiscal_artifacts (certificate_id);
CREATE INDEX IF NOT EXISTS idx_artifact_status ON fiscal_artifacts (tenant_id, status);
CREATE UNIQUE INDEX IF NOT EXISTS uq_artifact_document_version ON fiscal_artifacts (tenant_id, fiscal_document_id, fiscal_document_version, artifact_type);
