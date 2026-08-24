CREATE TABLE IF NOT EXISTS tenant_schema_migrations (
 migration_id VARCHAR(190) NOT NULL PRIMARY KEY,
 checksum CHAR(64) NOT NULL,
 applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 execution_status VARCHAR(32) NOT NULL,
 duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
 operator_source VARCHAR(190) NOT NULL,
 KEY ix_tenant_schema_migrations_status (execution_status, applied_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
