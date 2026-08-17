-- Migration: Add municipio and regime columns to tenants
-- Execute in the main database where table `tenants` exists.

ALTER TABLE tenants
    ADD COLUMN IF NOT EXISTS municipio VARCHAR(128) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS regime VARCHAR(64) DEFAULT NULL;

-- If your MySQL does not support IF NOT EXISTS on ADD COLUMN, run individually.
