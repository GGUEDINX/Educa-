<?php

namespace App\Models;

use App\Core\Model;

class ProgressoAula extends Model
{
    protected static string $tabela = 'progresso_aulas';

    public function marcarAssistida(int $usuarioId, int $aulaId): void
    {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO progresso_aulas (usuario_id, aula_id) VALUES (:usuario_id, :aula_id)
        ");
        $stmt->execute(['usuario_id' => $usuarioId, 'aula_id' => $aulaId]);
    }

    /** IDs das aulas que o usuário já assistiu, dentro de um curso específico. */
    public function idsAssistidasNoCurso(int $usuarioId, int $cursoId): array
    {
        $stmt = $this->db->prepare("
            SELECT pa.aula_id FROM progresso_aulas pa
            JOIN aulas a ON a.id = pa.aula_id
            WHERE pa.usuario_id = :usuario_id AND a.curso_id = :curso_id
        ");
        $stmt->execute(['usuario_id' => $usuarioId, 'curso_id' => $cursoId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** Verifica se o usuário já assistiu a TODAS as aulas de um curso. */
    public function assistiuTodasAsAulas(int $usuarioId, int $cursoId): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM aulas WHERE curso_id = :curso_id");
        $stmt->execute(['curso_id' => $cursoId]);
        $totalAulas = (int) $stmt->fetchColumn();

        if ($totalAulas === 0) {
            return true; // curso sem aulas cadastradas não bloqueia o quiz
        }

        return count($this->idsAssistidasNoCurso($usuarioId, $cursoId)) >= $totalAulas;
    }
}
