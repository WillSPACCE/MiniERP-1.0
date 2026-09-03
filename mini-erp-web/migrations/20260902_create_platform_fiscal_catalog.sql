-- Catálogo fiscal global versionado para NCM oficial compartilhado por todos os tenants.
CREATE TABLE IF NOT EXISTS platform_fiscal_catalog_versions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 catalog_type VARCHAR(20) NOT NULL,
 source_name VARCHAR(120) NOT NULL,
 source_version VARCHAR(120) NOT NULL,
 source_date DATE NOT NULL,
 checksum_sha256 CHAR(64) NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 item_count INT UNSIGNED NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_platform_fiscal_catalog_checksum(catalog_type,checksum_sha256),
 KEY ix_platform_fiscal_catalog_active(catalog_type,active,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS platform_fiscal_catalog_entries (
 version_id BIGINT UNSIGNED NOT NULL,
 code VARCHAR(20) NOT NULL,
 description VARCHAR(1000) NOT NULL,
 valid_from DATE NULL,
 valid_to DATE NULL,
 metadata_json JSON NOT NULL,
 PRIMARY KEY(version_id,code),
 KEY ix_platform_fiscal_catalog_code(code),
 FULLTEXT KEY ft_platform_fiscal_catalog_description(description),
 CONSTRAINT fk_platform_fiscal_catalog_version FOREIGN KEY(version_id) REFERENCES platform_fiscal_catalog_versions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
