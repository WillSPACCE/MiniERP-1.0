-- PLATFORM-01-T05: execute somente após backup e confirmação administrativa.
-- NULL significa versão desconhecida; não preencher tenants legados automaticamente.
ALTER TABLE tenants ADD COLUMN schema_version VARCHAR(32) NULL AFTER db_name;
