-- FISCAL-CERT-03: defaults operacionais globais; TaxEngine continua soberano.
CREATE TABLE IF NOT EXISTS platform_fiscal_defaults (
 id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
 entry_internal_cfop VARCHAR(10) NOT NULL,
 entry_interstate_cfop VARCHAR(10) NOT NULL,
 exit_internal_cfop VARCHAR(10) NOT NULL,
 exit_interstate_cfop VARCHAR(10) NOT NULL,
 updated_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT chk_platform_fiscal_defaults_singleton CHECK (id=1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO platform_fiscal_defaults(id,entry_internal_cfop,entry_interstate_cfop,exit_internal_cfop,exit_interstate_cfop)
VALUES(1,'1102','2102','5102','6102') ON DUPLICATE KEY UPDATE id=VALUES(id);
