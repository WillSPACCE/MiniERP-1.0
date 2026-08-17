-- 2026-08-14 - adicionar tenant_id (não destrutivo)
-- Faça backup do banco antes de executar.

-- ATENÇÃO: alguns servidores MySQL não suportam IF NOT EXISTS em ALTER TABLE.
-- Se seu MySQL não suportar, remova o IF NOT EXISTS e cheque previamente com SHOW COLUMNS.

ALTER TABLE produtos ADD COLUMN IF NOT EXISTS tenant_id INT NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS tenant_id INT NULL;

CREATE INDEX IF NOT EXISTS ix_produtos_tenant_id ON produtos(tenant_id);
CREATE INDEX IF NOT EXISTS ix_usuarios_tenant_id ON usuarios(tenant_id);

-- Após executar a migration, você pode popular tenant_id a partir de company_id:
-- UPDATE produtos SET tenant_id = company_id WHERE tenant_id IS NULL;
-- UPDATE usuarios SET tenant_id = company_id WHERE tenant_id IS NULL;
