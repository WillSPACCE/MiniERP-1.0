CREATE TABLE IF NOT EXISTS tenant_access_policies (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id INT NOT NULL,
 access_mode VARCHAR(20) NOT NULL DEFAULT 'FULL',
 can_issue_fiscal TINYINT(1) NOT NULL DEFAULT 1,
 can_manage_users TINYINT(1) NOT NULL DEFAULT 1,
 can_use_financial TINYINT(1) NOT NULL DEFAULT 1,
 reason VARCHAR(500) NOT NULL,
 starts_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 expires_at DATETIME NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 revoked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY ix_tenant_policy_active (tenant_id, revoked_at, expires_at),
 KEY ix_tenant_policy_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
