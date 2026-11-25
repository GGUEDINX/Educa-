-- ==========================================================
-- MIGRAÇÃO: aulas assistidas + notificação de dúvida respondida
-- Rode no banco plataforma_treinamento que você já tem.
-- ==========================================================

CREATE TABLE IF NOT EXISTS progresso_aulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    aula_id INT NOT NULL,
    data_conclusao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY usuario_aula (usuario_id, aula_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE CASCADE
);

ALTER TABLE duvidas_curso
    ADD COLUMN IF NOT EXISTS lida_pelo_aluno TINYINT(1) NOT NULL DEFAULT 0;
