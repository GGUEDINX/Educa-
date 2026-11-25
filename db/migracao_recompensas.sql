-- ==========================================================
-- MIGRAÇÃO: sistema de pontos e recompensas
-- Rode no banco plataforma_treinamento que você já tem.
-- ==========================================================

-- Saldo de pontos de cada usuário (créditos ganhos - resgates feitos)
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS pontos INT NOT NULL DEFAULT 0;

-- Quantos pontos cada curso concede ao ser concluído (definido curso a curso pela equipe pedagógica)
ALTER TABLE cursos
    ADD COLUMN IF NOT EXISTS pontos_recompensa INT NOT NULL DEFAULT 10;

-- Prêmios cadastrados pela empresa, trocáveis por pontos
CREATE TABLE IF NOT EXISTS recompensas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT DEFAULT NULL,
    custo_pontos INT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

-- Resgates de recompensas feitos pelos funcionários
CREATE TABLE IF NOT EXISTS resgates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    recompensa_id INT NOT NULL,
    pontos_gastos INT NOT NULL,
    status ENUM('pendente', 'entregue') NOT NULL DEFAULT 'pendente',
    data_resgate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (recompensa_id) REFERENCES recompensas(id) ON DELETE CASCADE
);
