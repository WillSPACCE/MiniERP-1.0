-- Contas a receber e pagar vinculadas aos pedidos e despesas manuais.
CREATE TABLE IF NOT EXISTS financial_accounts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id INT UNSIGNED NOT NULL,
 account_type VARCHAR(10) NOT NULL,
 source_type VARCHAR(30) NOT NULL DEFAULT 'MANUAL',
 source_id BIGINT UNSIGNED NULL,
 person_id INT NULL,
 description VARCHAR(255) NOT NULL,
 document_number VARCHAR(80) NULL,
 issue_date DATE NOT NULL,
 due_date DATE NOT NULL,
 original_amount DECIMAL(18,2) NOT NULL,
 paid_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
 status VARCHAR(20) NOT NULL DEFAULT 'OPEN',
 category VARCHAR(80) NULL,
 payment_method VARCHAR(40) NULL,
 notes VARCHAR(500) NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_financial_source (tenant_id,account_type,source_type,source_id),
 KEY ix_financial_due (tenant_id,account_type,status,due_date),
 KEY ix_financial_person (tenant_id,person_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financial_payments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id INT UNSIGNED NOT NULL,
 account_id BIGINT UNSIGNED NOT NULL,
 amount DECIMAL(18,2) NOT NULL,
 paid_at DATETIME NOT NULL,
 payment_method VARCHAR(40) NULL,
 notes VARCHAR(500) NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY ix_financial_payment_account (tenant_id,account_id,paid_at),
 CONSTRAINT fk_financial_payment_account FOREIGN KEY(account_id) REFERENCES financial_accounts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
