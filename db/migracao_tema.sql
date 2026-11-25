-- ==========================================================
-- MIGRAÇÃO: modo claro/escuro
-- Rode no banco plataforma_treinamento que você já tem.
-- ==========================================================

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS tema ENUM('escuro','claro') NOT NULL DEFAULT 'escuro';
