<?php

namespace App\Models;

use App\Core\Model;

class QuizAlternativa extends Model
{
    protected static string $tabela = 'quiz_alternativas';

    public function porPergunta(int $perguntaId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM quiz_alternativas WHERE pergunta_id = :pergunta_id");
        $stmt->execute(['pergunta_id' => $perguntaId]);
        return $stmt->fetchAll();
    }

    public function criar(int $perguntaId, string $texto, bool $correta): int
    {
        return $this->inserir(['pergunta_id' => $perguntaId, 'texto_alternativa' => $texto, 'e_correta' => $correta ? 1 : 0]);
    }

    /** Monta o quiz completo (perguntas + alternativas) de um curso, já agrupado. */
    public function montarQuizDoCurso(int $cursoId): array
    {
        $perguntaModel = new QuizPergunta();
        $perguntas = $perguntaModel->porCurso($cursoId);

        $quizCompleto = [];
        foreach ($perguntas as $pergunta) {
            $quizCompleto[] = [
                'pergunta' => $pergunta,
                'alternativas' => $this->porPergunta($pergunta['id']),
            ];
        }
        return $quizCompleto;
    }
}
