-- FISCAL-CONFIG-01: configuracoes fiscais da empresa, aditiva, vazia e sem defaults tributarios.
CREATE TABLE IF NOT EXISTS establishment_fiscal_settings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id BIGINT UNSIGNED NOT NULL, establishment_id BIGINT UNSIGNED NOT NULL,
 environment TINYINT UNSIGNED NOT NULL DEFAULT 2, primary_model CHAR(2) NOT NULL DEFAULT '55',
 deduct_icms_from_pis_cofins_base TINYINT(1) NOT NULL DEFAULT 0, default_cst_csosn VARCHAR(3) NULL, final_consumer_cst_csosn VARCHAR(3) NULL,
 default_cbenef VARCHAR(20) NULL, final_consumer_cbenef VARCHAR(20) NULL, funrural_rate DECIMAL(15,6) NULL, simple_credit_rate DECIMAL(15,6) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_est_fiscal_settings_scope(tenant_id,establishment_id), KEY ix_est_fiscal_settings_tenant(tenant_id,establishment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS establishment_cfop_defaults (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id BIGINT UNSIGNED NOT NULL, establishment_id BIGINT UNSIGNED NOT NULL,
 operation_context VARCHAR(32) NOT NULL, cfop CHAR(4) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_est_cfop_context(tenant_id,establishment_id,operation_context)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS establishment_csc_credentials (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id BIGINT UNSIGNED NOT NULL, establishment_id BIGINT UNSIGNED NOT NULL, environment TINYINT UNSIGNED NOT NULL,
 id_csc VARCHAR(20) NOT NULL, secret_reference VARCHAR(500) NOT NULL, secret_suffix CHAR(4) NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1,
 created_by BIGINT UNSIGNED NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_est_csc_environment(tenant_id,establishment_id,environment)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS establishment_icms_defaults (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id BIGINT UNSIGNED NOT NULL, establishment_id BIGINT UNSIGNED NOT NULL, uf CHAR(2) NOT NULL,
 juridica_rate DECIMAL(15,6) NULL, final_consumer_rate DECIMAL(15,6) NULL, reduction_rate DECIMAL(15,6) NULL, deferral_rate DECIMAL(15,6) NULL,
 mva_rate DECIMAL(15,6) NULL, simple_mva_rate DECIMAL(15,6) NULL, st_rate DECIMAL(15,6) NULL, st_reduction_rate DECIMAL(15,6) NULL,
 internal_rate DECIMAL(15,6) NULL, fcp_rate DECIMAL(15,6) NULL, cst_csosn VARCHAR(3) NULL, valid_from DATE NOT NULL, valid_to DATE NULL,
 active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_est_icms_version(tenant_id,establishment_id,uf,valid_from), KEY ix_est_icms_effective(tenant_id,establishment_id,uf,active,valid_from,valid_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS establishment_legacy_tax_defaults (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id BIGINT UNSIGNED NOT NULL, establishment_id BIGINT UNSIGNED NOT NULL,
 pis_output_cst CHAR(2) NULL, pis_output_rate DECIMAL(15,6) NULL, pis_input_cst CHAR(2) NULL, pis_input_rate DECIMAL(15,6) NULL,
 cofins_output_cst CHAR(2) NULL, cofins_output_rate DECIMAL(15,6) NULL, cofins_input_cst CHAR(2) NULL, cofins_input_rate DECIMAL(15,6) NULL,
 ipi_output_cst CHAR(2) NULL, ipi_output_rate DECIMAL(15,6) NULL, ipi_input_cst CHAR(2) NULL, ipi_input_rate DECIMAL(15,6) NULL, ipi_cenq VARCHAR(3) NULL,
 ipi_applicability VARCHAR(20) NOT NULL DEFAULT 'PENDING', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_est_legacy_tax_scope(tenant_id,establishment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS establishment_rtc_defaults (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id BIGINT UNSIGNED NOT NULL, establishment_id BIGINT UNSIGNED NOT NULL, document_scope VARCHAR(3) NOT NULL,
 ibs_cbs_cst VARCHAR(3) NULL, cclass_trib VARCHAR(30) NULL, ibs_uf_rate DECIMAL(15,6) NULL, ibs_municipal_rate DECIMAL(15,6) NULL, cbs_rate DECIMAL(15,6) NULL,
 ibs_reduction_rate DECIMAL(15,6) NULL, cbs_reduction_rate DECIMAL(15,6) NULL, ibs_deferral_rate DECIMAL(15,6) NULL, cbs_deferral_rate DECIMAL(15,6) NULL,
 is_enabled TINYINT(1) NOT NULL DEFAULT 0, is_cst VARCHAR(3) NULL, is_classification VARCHAR(30) NULL, is_rate DECIMAL(15,6) NULL,
 is_type VARCHAR(16) NOT NULL DEFAULT 'NONE', is_unit VARCHAR(10) NULL, is_specific_value DECIMAL(18,6) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_est_rtc_scope(tenant_id,establishment_id,document_scope)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS establishment_fiscal_settings_audit (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id BIGINT UNSIGNED NOT NULL, establishment_id BIGINT UNSIGNED NOT NULL,
 setting_group VARCHAR(40) NOT NULL, actor_id BIGINT UNSIGNED NOT NULL, before_json LONGTEXT NULL, after_json LONGTEXT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY ix_est_fiscal_audit(tenant_id,establishment_id,setting_group,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
