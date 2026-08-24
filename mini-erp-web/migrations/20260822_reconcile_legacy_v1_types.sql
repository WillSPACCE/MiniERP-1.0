-- Widening of tipo_pessoa is lossless. codigo_ibge narrowing requires a precheck of all existing values.
ALTER TABLE clientes MODIFY COLUMN tipo_pessoa VARCHAR(100) DEFAULT 'cliente';
ALTER TABLE clientes MODIFY COLUMN codigo_ibge VARCHAR(7) DEFAULT '';
