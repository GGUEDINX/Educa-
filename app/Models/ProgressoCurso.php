<?php

namespace App\Models;

use App\Core\Model;

class ProgressoCurso extends Model
{
    protected static string $tabela = 'progresso_cursos';

    public function estaConcluido(int $usuarioId, int $cursoId): bool
    {
        $stmt = $this->db->prepare("
            SELECT concluido FROM progresso_cursos WHERE usuario_id = :usuario_id AND curso_id = :curso_id LIMIT 1
        ");
        $stmt->execute(['usuario_id' => $usuarioId, 'curso_id' => $cursoId]);
        return (bool) $stmt->fetchColumn();
    }

    public function marcarConcluido(int $usuarioId, int $cursoId): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO progresso_cursos (usuario_id, curso_id, concluido, data_conclusao)
            VALUES (:usuario_id, :curso_id, TRUE, NOW())
            ON DUPLICATE KEY UPDATE concluido = TRUE, data_conclusao = NOW()
        ");
        $stmt->execute(['usuario_id' => $usuarioId, 'curso_id' => $cursoId]);
    }

    /**
     * Taxa de conclusão (%) da empresa para os cursos indicados (obrigatórios ou todos).
     */
    public function taxaConclusao(int $empresaId, int $totalFuncionarios, int $totalCursos, bool $apenasObrigatorios): float
    {
        if ($totalFuncionarios === 0 || $totalCursos === 0) {
            return 0;
        }

        $filtro = $apenasObrigatorios ? "AND c.obrigatorio = 1" : "";

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM progresso_cursos pc
            JOIN usuarios u ON u.id = pc.usuario_id
            JOIN cursos c ON c.id = pc.curso_id
            WHERE u.empresa_id = :empresa_id AND pc.concluido = 1 $filtro
        ");
        $stmt->execute(['empresa_id' => $empresaId]);
        $totalConcluidos = (int) $stmt->fetchColumn();

        $totalEsperado = $totalCursos * $totalFuncionarios;
        return $totalEsperado > 0 ? round(($totalConcluidos / $totalEsperado) * 100) : 0;
    }
}
