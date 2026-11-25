-- ==========================================================
-- plataforma_treinamento — DUMP COMPLETO E ATUALIZADO
-- Inclui: schema original + todas as migrações aplicadas até agora
-- (dashboard/KPIs, suporte pedagógico, perfil pedagogia, segurança,
-- aulas assistidas, modo claro/escuro) + os dados que você já tinha.
-- Importe este arquivo UMA VEZ, em um banco vazio, e pronto.
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS plataforma_treinamento CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE plataforma_treinamento;

-- ==========================================================
-- TABELAS
-- ==========================================================

CREATE TABLE empresas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome_empresa VARCHAR(150) NOT NULL,
  cnpj VARCHAR(18) NOT NULL,
  data_cadastro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  nome VARCHAR(100) NOT NULL,
  UNIQUE KEY cnpj (cnpj)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  tipo_usuario ENUM('admin_empresa','funcionario','pedagogia') NOT NULL,
  empresa_id INT NOT NULL,
  foto_avatar VARCHAR(255) DEFAULT 'default.png',
  cor_tema VARCHAR(7) DEFAULT '#2563eb',
  cor_primaria VARCHAR(7) DEFAULT '#3b82f6',
  cor_botoes VARCHAR(7) DEFAULT '#3b82f6',
  ultimo_acesso DATETIME NULL,
  tentativas_login INT NOT NULL DEFAULT 0,
  bloqueado_ate DATETIME NULL,
  tema ENUM('escuro','claro') NOT NULL DEFAULT 'escuro',
  UNIQUE KEY email (email),
  KEY empresa_id (empresa_id),
  CONSTRAINT usuarios_ibfk_1 FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cursos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(150) NOT NULL,
  descricao TEXT DEFAULT NULL,
  carga_horaria INT NOT NULL,
  categoria VARCHAR(50) DEFAULT 'Geral',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  obrigatorio TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE aulas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  url_video VARCHAR(255) DEFAULT NULL,
  ordem INT DEFAULT 0,
  KEY curso_id (curso_id),
  CONSTRAINT aulas_ibfk_1 FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE certificados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  curso_nome VARCHAR(150) NOT NULL,
  carga_horaria INT NOT NULL,
  data_conclusao DATE NOT NULL,
  codigo_validacao VARCHAR(50) NOT NULL,
  validade DATE NULL,
  UNIQUE KEY codigo_validacao (codigo_validacao),
  KEY usuario_id (usuario_id),
  CONSTRAINT certificados_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ferramentas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  descricao TEXT DEFAULT NULL,
  url VARCHAR(255) NOT NULL,
  empresa_id INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cliques INT NOT NULL DEFAULT 0,
  KEY empresa_id (empresa_id),
  CONSTRAINT ferramentas_ibfk_1 FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE progresso_cursos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  curso_id INT NOT NULL,
  concluido TINYINT(1) DEFAULT 0,
  data_conclusao TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY usuario_curso (usuario_id, curso_id),
  KEY curso_id (curso_id),
  CONSTRAINT progresso_cursos_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT progresso_cursos_ibfk_2 FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE quiz_perguntas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  pergunta TEXT NOT NULL,
  CONSTRAINT quiz_perguntas_curso_fk FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE quiz_alternativas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pergunta_id INT NOT NULL,
  texto_alternativa TEXT NOT NULL,
  e_correta TINYINT(1) DEFAULT 0,
  KEY pergunta_id (pergunta_id),
  CONSTRAINT quiz_alternativas_ibfk_1 FOREIGN KEY (pergunta_id) REFERENCES quiz_perguntas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE solicitacoes_curso (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  empresa_id INT NOT NULL,
  titulo_sugerido VARCHAR(150) NOT NULL,
  descricao TEXT,
  status ENUM('pendente','aprovado','recusado') NOT NULL DEFAULT 'pendente',
  data_solicitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT solicitacoes_curso_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT solicitacoes_curso_ibfk_2 FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE duvidas_curso (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  usuario_id INT NOT NULL,
  pergunta TEXT NOT NULL,
  resposta TEXT NULL,
  respondido_por INT NULL,
  status ENUM('pendente','respondida') NOT NULL DEFAULT 'pendente',
  data_pergunta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  data_resposta DATETIME NULL,
  lida_pelo_aluno TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT duvidas_curso_ibfk_1 FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
  CONSTRAINT duvidas_curso_ibfk_2 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT duvidas_curso_ibfk_3 FOREIGN KEY (respondido_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE feedback_curso (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  usuario_id INT NOT NULL,
  nota TINYINT NOT NULL,
  comentario TEXT NULL,
  data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT feedback_curso_ibfk_1 FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
  CONSTRAINT feedback_curso_ibfk_2 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE progresso_aulas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  aula_id INT NOT NULL,
  data_conclusao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY usuario_aula (usuario_id, aula_id),
  CONSTRAINT progresso_aulas_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT progresso_aulas_ibfk_2 FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- DADOS
-- ==========================================================

INSERT INTO empresas (id, nome_empresa, cnpj, data_cadastro, nome) VALUES
(1, '', '', '2026-07-14 21:56:28', 'Tech Solutions');

-- Observação: as senhas abaixo são todas "123456" já em formato hash (bcrypt) —
-- é exatamente o que o sistema salva automaticamente a partir de agora.
-- As cores do Luis Carlos também foram resetadas para azul (o vermelho era só teste).
INSERT INTO usuarios (id, nome, email, senha, tipo_usuario, empresa_id, foto_avatar, cor_tema, cor_primaria, cor_botoes, ultimo_acesso) VALUES
(3, 'Guilherme Admin', 'admin@techsolutions.com', '$2b$12$c2oH.MQlmbvA1SpDI4uT4.kplUFcKpVmB2hn3jSnMiSdKw7v.N01y', 'admin_empresa', 1, 'default.png', '#0062ff', '#3b82f6', '#3b82f6', NULL),
(4, 'Luis Carlos', 'luis@techsolutions.com', '$2b$12$c2oH.MQlmbvA1SpDI4uT4.kplUFcKpVmB2hn3jSnMiSdKw7v.N01y', 'funcionario', 1, 'avatar_4_1784068092.png', '#2563eb', '#3b82f6', '#3b82f6', NULL),
(5, 'Equipe Pedagógica', 'pedagogia@techsolutions.com', '$2b$12$c2oH.MQlmbvA1SpDI4uT4.kplUFcKpVmB2hn3jSnMiSdKw7v.N01y', 'pedagogia', 1, 'default.png', '#3b82f6', '#3b82f6', '#3b82f6', NULL);

INSERT INTO cursos (id, titulo, descricao, carga_horaria, categoria, criado_em, obrigatorio) VALUES
(1, 'Comunicação Assertiva e Resolução de Conflitos', 'Desenvolva soft skills cruciais como empatia, escuta ativa e técnicas para mediar e resolver atritos no ambiente corporativo.', 12, 'Soft Skills', '2026-07-14 22:44:24', 0),
(2, 'Introdução ao Desenvolvimento Web', 'Aprenda os fundamentos de HTML, CSS, PHP e como estruturar e integrar bancos de dados com MySQL.', 40, 'Tecnologia', '2026-07-14 22:44:24', 0),
(3, 'Fundamentos de Gestão de Projetos', 'Entenda o ciclo de vida de projetos utilizando metodologias ágeis, Kanban, ciclos PDCA e mapeamento de riscos corporativos.', 20, 'Gestão', '2026-07-14 22:44:24', 0);

INSERT INTO certificados (id, usuario_id, curso_nome, carga_horaria, data_conclusao, codigo_validacao, validade) VALUES
(2, 3, 'Introdução ao Desenvolvimento Web', 40, '2026-07-14', 'EDUCA-987123-XYZ', NULL),
(3, 4, 'Comunicação Assertiva e Resolução de Conflitos', 12, '2026-07-14', 'EDUCA-004-001-773641', NULL);

INSERT INTO progresso_cursos (id, usuario_id, curso_id, concluido, data_conclusao) VALUES
(1, 4, 1, 1, '2026-07-14 22:49:03');

INSERT INTO quiz_perguntas (id, curso_id, pergunta) VALUES
(1, 1, 'Qual das alternativas abaixo melhor define a Comunicação Assertiva?'),
(2, 1, 'O que é um elemento fundamental da Escuta Ativa?');

INSERT INTO quiz_alternativas (id, pergunta_id, texto_alternativa, e_correta) VALUES
(1, 1, 'Falar o que pensa sem se importar com os sentimentos alheios.', 0),
(2, 1, 'Expressar suas ideias, sentimentos e necessidades de forma clara, honesta e respeitosa.', 1),
(3, 1, 'Concordar com tudo o que o outro diz para evitar desentendimentos.', 0),
(4, 2, 'Interromper o interlocutor para expor sua opinião mais rápido.', 0),
(5, 2, 'Ouvir focado em formular sua resposta enquanto o outro fala.', 0),
(6, 2, 'Prestar atenção genuína ao interlocutor, validando seus sentimentos e demonstrando interesse real.', 1);

-- ==========================================================
-- AUTO_INCREMENT (continua de onde parou)
-- ==========================================================
ALTER TABLE empresas AUTO_INCREMENT = 2;
ALTER TABLE usuarios AUTO_INCREMENT = 6;
ALTER TABLE cursos AUTO_INCREMENT = 4;
ALTER TABLE aulas AUTO_INCREMENT = 1;
ALTER TABLE certificados AUTO_INCREMENT = 4;
ALTER TABLE ferramentas AUTO_INCREMENT = 1;
ALTER TABLE progresso_cursos AUTO_INCREMENT = 2;
ALTER TABLE quiz_perguntas AUTO_INCREMENT = 3;
ALTER TABLE quiz_alternativas AUTO_INCREMENT = 7;
ALTER TABLE solicitacoes_curso AUTO_INCREMENT = 1;
ALTER TABLE duvidas_curso AUTO_INCREMENT = 1;
ALTER TABLE feedback_curso AUTO_INCREMENT = 1;
ALTER TABLE progresso_aulas AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

