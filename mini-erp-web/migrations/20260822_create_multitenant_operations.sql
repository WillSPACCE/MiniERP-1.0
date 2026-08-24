CREATE TABLE IF NOT EXISTS platform_migration_plans (
 plan_id CHAR(32) PRIMARY KEY,admin_id BIGINT UNSIGNED NOT NULL,migration_id VARCHAR(190) NOT NULL,checksum CHAR(64) NOT NULL,
 tenant_ids_json LONGTEXT NOT NULL,simulation_json LONGTEXT NOT NULL,created_at DATETIME NOT NULL,expires_at DATETIME NOT NULL,consumed_at DATETIME NULL,
 KEY ix_migration_plan_expiry (expires_at,consumed_at),CONSTRAINT fk_migration_plan_admin FOREIGN KEY(admin_id) REFERENCES platform_admin_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS platform_database_operations (
 operation_id CHAR(32) PRIMARY KEY,plan_id CHAR(32) NOT NULL UNIQUE,admin_id BIGINT UNSIGNED NOT NULL,migration_id VARCHAR(190) NOT NULL,
 checksum CHAR(64) NOT NULL,risk VARCHAR(32) NOT NULL,reason VARCHAR(500) NOT NULL,created_at DATETIME NOT NULL,started_at DATETIME NULL,finished_at DATETIME NULL,status VARCHAR(32) NOT NULL,
 CONSTRAINT fk_database_operation_plan FOREIGN KEY(plan_id) REFERENCES platform_migration_plans(plan_id),CONSTRAINT fk_database_operation_admin FOREIGN KEY(admin_id) REFERENCES platform_admin_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS platform_database_operation_targets (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,operation_id CHAR(32) NOT NULL,tenant_id INT NOT NULL,db_name VARCHAR(190) NOT NULL,status VARCHAR(32) NOT NULL,
 backup_path VARCHAR(500) NULL,backup_size BIGINT UNSIGNED NULL,backup_sha256 CHAR(64) NULL,started_at DATETIME NULL,finished_at DATETIME NULL,duration_ms INT UNSIGNED NULL,
 validation_json LONGTEXT NULL,error_message VARCHAR(1000) NULL,UNIQUE KEY uq_operation_tenant(operation_id,tenant_id),KEY ix_operation_target_status(status),
 CONSTRAINT fk_operation_target_operation FOREIGN KEY(operation_id) REFERENCES platform_database_operations(operation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
