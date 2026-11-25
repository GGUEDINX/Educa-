<?php

namespace App\Controllers;

use App\Models\Curso;
use App\Models\Aula;
use App\Models\QuizPergunta;
use App\Models\QuizAlternativa;

class ConteudoController
{
    private Curso $cursoModel;
    private Aula $aulaModel;
    private QuizPergunta $perguntaModel;
    private QuizAlternativa $alternativaModel;

    public function __construct()
    {
        $this->cursoModel = new Curso();
        $this->aulaModel = new Aula();
        $this->perguntaModel = new QuizPergunta();
        $this->alternativaModel = new QuizAlternativa();
    }

    // ===================== CURSOS =====================

    public function listarCursos(): array
    {
        $cursos = $this->cursoModel->todosOrdenados();
        foreach ($cursos as &$c) {
            $c['total_aulas'] = $this->aulaModel->contarPorCurso($c['id']);
        }
        return $cursos;
    }

    public function buscarCurso(int $id): ?array
    {
        return $this->cursoModel->buscarPorId($id);
    }

    /** @return array{mensagem: string, tipo: string} */
    public function criarCurso(string $titulo, string $descricao, int $cargaHoraria, string $categoria, bool $obrigatorio, int $pontosRecompensa = 10): array
    {
        if (empty($titulo) || $cargaHoraria <= 0) {
            return ['mensagem' => 'Preencha o título e a carga horária do curso.', 'tipo' => 'error'];
        }

        try {
            $this->cursoModel->criar([
                'titulo' => $titulo,
                'descricao' => $descricao,
                'carga_horaria' => $cargaHoraria,
                'categoria' => $categoria ?: 'Geral',
                'obrigatorio' => $obrigatorio ? 1 : 0,
                'pontos_recompensa' => max(0, $pontosRecompensa),
            ]);
            return ['mensagem' => 'Curso criado! Agora adicione as aulas e o quiz dele.', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            // Se alguma coluna ainda não existir (migração não rodada), tenta de novo sem elas
            try {
                $this->cursoModel->criar([
                    'titulo' => $titulo,
                    'descricao' => $descricao,
                    'carga_horaria' => $cargaHoraria,
                    'categoria' => $categoria ?: 'Geral',
                ]);
                return ['mensagem' => 'Curso criado! (Rode as migrações do banco para habilitar "obrigatório" e "pontos de recompensa".)', 'tipo' => 'success'];
            } catch (\PDOException $e2) {
                return ['mensagem' => 'Erro ao criar curso: ' . $e2->getMessage(), 'tipo' => 'error'];
            }
        }
    }

    /** @return array{mensagem: string, tipo: string} */
    public function excluirCurso(int $id): array
    {
        try {
            $this->cursoModel->excluir($id);
            return ['mensagem' => 'Curso removido.', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao excluir curso: ' . $e->getMessage(), 'tipo' => 'error'];
        }
    }

    /** @return array{mensagem: string, tipo: string} */
    public function editarCurso(int $id, string $titulo, string $descricao, int $cargaHoraria, string $categoria, bool $obrigatorio, int $pontosRecompensa = 10): array
    {
        if (empty($titulo) || $cargaHoraria <= 0) {
            return ['mensagem' => 'Preencha o título e a carga horária do curso.', 'tipo' => 'error'];
        }

        try {
            $this->cursoModel->atualizarCurso($id, [
                'titulo' => $titulo,
                'descricao' => $descricao,
                'carga_horaria' => $cargaHoraria,
                'categoria' => $categoria ?: 'Geral',
                'obrigatorio' => $obrigatorio ? 1 : 0,
                'pontos_recompensa' => max(0, $pontosRecompensa),
            ]);
            return ['mensagem' => 'Curso atualizado com sucesso!', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            // Se a coluna de pontos ainda não existir (migração não rodada), tenta de novo sem ela
            try {
                $this->cursoModel->atualizarCurso($id, [
                    'titulo' => $titulo,
                    'descricao' => $descricao,
                    'carga_horaria' => $cargaHoraria,
                    'categoria' => $categoria ?: 'Geral',
                    'obrigatorio' => $obrigatorio ? 1 : 0,
                ]);
                return ['mensagem' => 'Curso atualizado! (Rode a migração do banco para habilitar "pontos de recompensa".)', 'tipo' => 'success'];
            } catch (\PDOException $e2) {
                return ['mensagem' => 'Erro ao atualizar curso: ' . $e2->getMessage(), 'tipo' => 'error'];
            }
        }
    }

    // ===================== AULAS =====================

    public function listarAulas(int $cursoId): array
    {
        return $this->aulaModel->porCurso($cursoId);
    }

    /** @return array{mensagem: string, tipo: string} */
    public function criarAula(int $cursoId, string $titulo, string $urlVideo, int $ordem, int $duracaoMinutos = 3): array
    {
        if (empty($titulo)) {
            return ['mensagem' => 'Dê um título para a aula.', 'tipo' => 'error'];
        }
        $duracaoMinutos = max(1, $duracaoMinutos);
        try {
            try {
                $this->aulaModel->criar([
                    'curso_id' => $cursoId,
                    'titulo' => $titulo,
                    'url_video' => $urlVideo,
                    'ordem' => $ordem,
                    'duracao_estimada_minutos' => $duracaoMinutos,
                ]);
            } catch (\PDOException $e) {
                // Coluna ainda não existe (migração não rodada): cria sem ela
                $this->aulaModel->criar([
                    'curso_id' => $cursoId,
                    'titulo' => $titulo,
                    'url_video' => $urlVideo,
                    'ordem' => $ordem,
                ]);
            }
            return ['mensagem' => 'Aula adicionada à trilha!', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao adicionar aula: ' . $e->getMessage(), 'tipo' => 'error'];
        }
    }

    /** @return array{mensagem: string, tipo: string} */
    public function excluirAula(int $id): array
    {
        try {
            $this->aulaModel->excluir($id);
            return ['mensagem' => 'Aula removida da trilha.', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao excluir aula: ' . $e->getMessage(), 'tipo' => 'error'];
        }
    }

    public function buscarAula(int $id): ?array
    {
        return $this->aulaModel->buscarPorId($id);
    }

    /** @return array{mensagem: string, tipo: string} */
    public function editarAula(int $id, string $titulo, string $urlVideo, int $ordem, int $duracaoMinutos = 3): array
    {
        if (empty($titulo)) {
            return ['mensagem' => 'Dê um título para a aula.', 'tipo' => 'error'];
        }
        $duracaoMinutos = max(1, $duracaoMinutos);
        try {
            try {
                $this->aulaModel->atualizarAula($id, [
                    'titulo' => $titulo,
                    'url_video' => $urlVideo,
                    'ordem' => $ordem,
                    'duracao_estimada_minutos' => $duracaoMinutos,
                ]);
            } catch (\PDOException $e) {
                // Coluna ainda não existe (migração não rodada): atualiza sem ela
                $this->aulaModel->atualizarAula($id, [
                    'titulo' => $titulo,
                    'url_video' => $urlVideo,
                    'ordem' => $ordem,
                ]);
            }
            return ['mensagem' => 'Aula atualizada com sucesso!', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao atualizar aula: ' . $e->getMessage(), 'tipo' => 'error'];
        }
    }

    // ===================== QUIZ =====================

    public function listarQuiz(int $cursoId): array
    {
        return $this->alternativaModel->montarQuizDoCurso($cursoId);
    }

    /**
     * Cria uma pergunta com suas alternativas de uma vez.
     * @param string[] $alternativas
     */
    public function criarPergunta(int $cursoId, string $pergunta, array $alternativas, int $indiceCorreta): array
    {
        $alternativas = array_values(array_filter($alternativas, fn($a) => trim($a) !== ''));

        if (empty($pergunta) || count($alternativas) < 2) {
            return ['mensagem' => 'Escreva a pergunta e pelo menos 2 alternativas.', 'tipo' => 'error'];
        }
        if (!isset($alternativas[$indiceCorreta])) {
            return ['mensagem' => 'Marque qual alternativa é a correta.', 'tipo' => 'error'];
        }

        try {
            $perguntaId = $this->perguntaModel->criar($cursoId, $pergunta);
            foreach ($alternativas as $i => $texto) {
                $this->alternativaModel->criar($perguntaId, $texto, $i === $indiceCorreta);
            }
            return ['mensagem' => 'Pergunta adicionada ao quiz!', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao criar pergunta: ' . $e->getMessage(), 'tipo' => 'error'];
        }
    }

    /** @return array{mensagem: string, tipo: string} */
    public function excluirPergunta(int $id): array
    {
        try {
            $this->perguntaModel->excluir($id); // alternativas somem junto via ON DELETE CASCADE
            return ['mensagem' => 'Pergunta removida do quiz.', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao excluir pergunta: ' . $e->getMessage(), 'tipo' => 'error'];
        }
    }
}
