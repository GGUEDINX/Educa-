-- ==========================================================
-- MIGRAÇÃO: Cadastro de empresa + escolha de plano (visual)
-- Rode no banco plataforma_treinamento que você já tem.
-- ==========================================================

ALTER TABLE empresas
    ADD COLUMN IF NOT EXISTS plano VARCHAR(50) NULL DEFAULT NULL;

-- ==========================================================
-- CORREÇÃO: a coluna cnpj era única (UNIQUE) e todo cadastro novo
-- estava tentando salvar cnpj = '' (vazio) — como já existia uma
-- empresa antiga com cnpj vazio, o banco recusava o próximo cadastro
-- igual. Passa a aceitar NULL (permite vários "vazios" ao mesmo tempo).
-- ==========================================================
ALTER TABLE empresas MODIFY cnpj VARCHAR(18) NULL;
UPDATE empresas SET cnpj = NULL WHERE cnpj = '';
