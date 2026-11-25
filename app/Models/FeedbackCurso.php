<?php

namespace App\Models;

use App\Core\Model;

class FeedbackCurso extends Model
{
    protected static string $tabela = 'feedback_curso';

    public function criar(int $usuarioId, int $cursoId, int $nota, string $comentario): int
    {
        return $this->inserir([
            'usuario_id' => $usuarioId,
            'curso_id' => $cursoId,
            'nota' => $nota,
            'comentario' => $comentario,
        ]);
    }

    /** Mural de feedback: todos os feedbacks enviados pelos funcionários da empresa. */
    public function porEmpresa(int $empresaId): array
    {
        $stmt = $this->db->prepare("
            SELECT f.*, u.nome AS nome_aluno, c.titulo AS titulo_curso
            FROM feedback_curso f
            JOIN usuarios u ON u.id = f.usuario_id
            JOIN cursos c ON c.id = f.curso_id
            WHERE u.empresa_id = :empresa_id
            ORDER BY f.data_envio DESC
        ");
        $stmt->execute(['empresa_id' => $empresaId]);
        return $stmt->fetchAll();
    }

    public function mediaNotaPorCurso(int $empresaId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.titulo, ROUND(AVG(f.nota), 1) AS media_nota, COUNT(f.id) AS total_avaliacoes
            FROM feedback_curso f
            JOIN usuarios u ON u.id = f.usuario_id
            JOIN cursos c ON c.id = f.curso_id
            WHERE u.empresa_id = :empresa_id
            GROUP BY c.id, c.titulo
            ORDER BY media_nota ASC
        ");
        $stmt->execute(['empresa_id' => $empresaId]);
        return $stmt->fetchAll();
    }
}
