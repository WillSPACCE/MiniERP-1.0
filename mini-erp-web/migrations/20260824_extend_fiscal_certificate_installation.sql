-- FISCAL-CERT-03: metadata de instalação/validação, sem PFX ou segredo no banco.
ALTER TABLE fiscal_certificates ADD COLUMN IF NOT EXISTS thumbprint_sha1 CHAR(40) NULL AFTER sha256;
ALTER TABLE fiscal_certificates ADD COLUMN IF NOT EXISTS installed_at DATETIME NULL AFTER uploaded_by;
ALTER TABLE fiscal_certificates ADD COLUMN IF NOT EXISTS installed_by BIGINT UNSIGNED NULL AFTER installed_at;
ALTER TABLE fiscal_certificates ADD COLUMN IF NOT EXISTS validated_at DATETIME NULL AFTER installed_by;
ALTER TABLE fiscal_certificates ADD COLUMN IF NOT EXISTS last_signature_test DATETIME NULL AFTER validated_at;
