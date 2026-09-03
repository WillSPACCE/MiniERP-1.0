-- Amplia o perfil fiscal por produto para importação de planilhas legadas e resolução na venda.
ALTER TABLE product_taxes
    ADD COLUMN IF NOT EXISTS icms_cst VARCHAR(3) NULL AFTER icms,
    ADD COLUMN IF NOT EXISTS icms_csosn VARCHAR(3) NULL AFTER icms_cst,
    ADD COLUMN IF NOT EXISTS icms_consumer_cst VARCHAR(3) NULL AFTER icms_csosn,
    ADD COLUMN IF NOT EXISTS icms_rate DECIMAL(15,6) NULL AFTER icms_consumer_cst,
    ADD COLUMN IF NOT EXISTS pis_output_cst VARCHAR(2) NULL AFTER pis,
    ADD COLUMN IF NOT EXISTS pis_output_rate DECIMAL(15,6) NULL AFTER pis_output_cst,
    ADD COLUMN IF NOT EXISTS pis_input_cst VARCHAR(2) NULL AFTER pis_output_rate,
    ADD COLUMN IF NOT EXISTS pis_input_rate DECIMAL(15,6) NULL AFTER pis_input_cst,
    ADD COLUMN IF NOT EXISTS cofins_output_cst VARCHAR(2) NULL AFTER cofins,
    ADD COLUMN IF NOT EXISTS cofins_output_rate DECIMAL(15,6) NULL AFTER cofins_output_cst,
    ADD COLUMN IF NOT EXISTS cofins_input_cst VARCHAR(2) NULL AFTER cofins_output_rate,
    ADD COLUMN IF NOT EXISTS cofins_input_rate DECIMAL(15,6) NULL AFTER cofins_input_cst,
    ADD COLUMN IF NOT EXISTS ipi_output_cst VARCHAR(2) NULL AFTER ipi,
    ADD COLUMN IF NOT EXISTS ipi_output_rate DECIMAL(15,6) NULL AFTER ipi_output_cst,
    ADD COLUMN IF NOT EXISTS ipi_input_cst VARCHAR(2) NULL AFTER ipi_output_rate,
    ADD COLUMN IF NOT EXISTS source_document VARCHAR(160) NULL AFTER ipi_input_cst,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER source_document;
