-- ==========================================================
-- MIGRAÇÃO: tempo mínimo de permanência por aula
-- Rode no banco plataforma_treinamento que você já tem.
-- ==========================================================

ALTER TABLE aulas
    ADD COLUMN IF NOT EXISTS duracao_estimada_minutos INT NOT NULL DEFAULT 3;
