-- Vínculo opcional entre usuário da empresa e pessoa cadastrada.
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS pessoa_id INT NULL AFTER company_id;
CREATE INDEX IF NOT EXISTS ix_usuarios_pessoa_id ON usuarios(pessoa_id);
