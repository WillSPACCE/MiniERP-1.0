-- Solicitações de acesso são isoladas por empresa e podem usar o mesmo e-mail.
ALTER TABLE usuarios DROP INDEX IF EXISTS email;
CREATE UNIQUE INDEX IF NOT EXISTS ux_usuarios_tenant_email ON usuarios(tenant_id, email);

CREATE TABLE IF NOT EXISTS user_oauth_identities (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    tenant_id INT NOT NULL,
    provider VARCHAR(20) NOT NULL,
    provider_subject VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY ux_oauth_tenant_provider_subject (tenant_id, provider, provider_subject),
    UNIQUE KEY ux_oauth_user_provider (user_id, provider),
    KEY ix_oauth_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
