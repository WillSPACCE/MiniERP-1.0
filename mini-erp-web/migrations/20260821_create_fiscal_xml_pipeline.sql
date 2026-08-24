-- FISCAL-06B: aditiva e idempotente; executar somente após backup e auditoria.
CREATE TABLE IF NOT EXISTS fiscal_series (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id BIGINT UNSIGNED NOT NULL,
 establishment_id BIGINT UNSIGNED NOT NULL, model CHAR(2) NOT NULL, series INT UNSIGNED NOT NULL,
 next_number BIGINT UNSIGNED NOT NULL DEFAULT 1, environment TINYINT UNSIGNED NOT NULL DEFAULT 2,
 emission_type TINYINT UNSIGNED NOT NULL DEFAULT 1, process_version VARCHAR(40) NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_fiscal_series (tenant_id,establishment_id,model,series),
 CHECK (model IN ('55','65')), CHECK (environment IN (1,2)), CHECK (next_number > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fiscal_number_reservations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id BIGINT UNSIGNED NOT NULL,
 establishment_id BIGINT UNSIGNED NOT NULL, fiscal_document_id BIGINT UNSIGNED NOT NULL,
 model CHAR(2) NOT NULL, series INT UNSIGNED NOT NULL, fiscal_number BIGINT UNSIGNED NOT NULL,
 environment TINYINT UNSIGNED NOT NULL, numeric_code CHAR(8) NULL, access_key CHAR(44) NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'RESERVED', created_by BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_document_reservation (tenant_id,fiscal_document_id),
 UNIQUE KEY uq_fiscal_number (tenant_id,establishment_id,model,series,fiscal_number),
 UNIQUE KEY uq_access_key (access_key)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fiscal_artifacts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id BIGINT UNSIGNED NOT NULL,
 establishment_id BIGINT UNSIGNED NOT NULL, fiscal_document_id BIGINT UNSIGNED NOT NULL,
 artifact_type VARCHAR(40) NOT NULL, status VARCHAR(40) NOT NULL,
 storage_reference VARCHAR(500) NOT NULL, sha256 CHAR(64) NOT NULL, size BIGINT UNSIGNED NOT NULL,
 created_by BIGINT UNSIGNED NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_document_artifact (tenant_id,fiscal_document_id,artifact_type),
 KEY ix_artifact_scope (tenant_id,establishment_id,fiscal_document_id)
) ENGINE=InnoDB;
