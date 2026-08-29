CREATE TABLE IF NOT EXISTS tenant_access_rules (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id INT NOT NULL,
 rule_key VARCHAR(32) NOT NULL,
 rule_value VARCHAR(32) NOT NULL,
 reason VARCHAR(500) NOT NULL,
 expires_at DATETIME NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 revoked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY ix_tenant_rule_active (tenant_id, rule_key, revoked_at, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
