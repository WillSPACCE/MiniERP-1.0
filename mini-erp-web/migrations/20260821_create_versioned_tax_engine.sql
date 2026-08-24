-- FISCAL-05. Regras tenant-scoped, versionadas e sem conteúdo fiscal/seed.
CREATE TABLE IF NOT EXISTS tax_rule_versions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL,
 rule_code VARCHAR(80) NOT NULL, rule_version INT UNSIGNED NOT NULL, priority INT NOT NULL DEFAULT 0,
 valid_from DATE NOT NULL, valid_to DATE NULL,
 source_document VARCHAR(160) NOT NULL, source_version VARCHAR(40) NOT NULL, source_date DATE NOT NULL,
 conditions_json JSON NOT NULL, cfop CHAR(4) NOT NULL,
 icms_json JSON NOT NULL, ipi_json JSON NOT NULL, pis_json JSON NOT NULL, cofins_json JSON NOT NULL,
 ibs_cbs_json JSON NOT NULL, selective_tax_json JSON NOT NULL,
 status VARCHAR(12) NOT NULL DEFAULT 'ACTIVE', fixture_kind VARCHAR(12) NOT NULL DEFAULT 'PRODUCTION',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_tax_rule_version (tenant_id,rule_code,rule_version),
 KEY idx_tax_rule_effective (tenant_id,status,valid_from,valid_to,priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fiscal_reference_versions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, reference_type VARCHAR(50) NOT NULL,
 source_document VARCHAR(160) NOT NULL, source_version VARCHAR(40) NOT NULL, published_at DATE NOT NULL,
 checksum_sha256 CHAR(64) NOT NULL, effective_from DATE NULL, effective_to DATE NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_fiscal_reference_version (reference_type,source_version,checksum_sha256)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fiscal_classifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, reference_version_id BIGINT UNSIGNED NOT NULL,
 classification_type VARCHAR(30) NOT NULL, code VARCHAR(30) NOT NULL, description VARCHAR(500) NOT NULL,
 metadata_json JSON NOT NULL,
 UNIQUE KEY uq_fiscal_classification (reference_version_id,classification_type,code),
 CONSTRAINT fk_fiscal_classification_version FOREIGN KEY (reference_version_id) REFERENCES fiscal_reference_versions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
