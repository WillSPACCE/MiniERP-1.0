-- FISCAL-NOTES-01: histórico fiscal imutável, sem segredos e sem seed.
CREATE TABLE IF NOT EXISTS fiscal_document_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NOT NULL,
 fiscal_document_id BIGINT UNSIGNED NOT NULL,
 event_type VARCHAR(48) NOT NULL,
 stage VARCHAR(32) NOT NULL,
 status VARCHAR(32) NOT NULL,
 code VARCHAR(80) NULL,
 message VARCHAR(1000) NULL,
 metadata_json LONGTEXT NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY ix_fiscal_event_document(tenant_id,fiscal_document_id,id),
 KEY ix_fiscal_event_status(tenant_id,status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
