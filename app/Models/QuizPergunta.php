<?php

namespace App\Models;

use App\Core\Model;

class QuizPergunta extends Model
{
    protected static string $tabela = 'quiz_perguntas';

    public function porCurso(int $cursoId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM quiz_perguntas WHERE curso_id = :curso_id");
        $stmt->execute(['curso_id' => $cursoId]);
        return $stmt->fetchAll();
    }

    public function criar(int $cursoId, string $pergunta): int
    {
        return $this->inserir(['curso_id' => $cursoId, 'pergunta' => $pergunta]);
    }
}
