<?php

namespace App\Models;

use App\Core\Model;

class Aula extends Model
{
    protected static string $tabela = 'aulas';

    public function porCurso(int $cursoId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM aulas WHERE curso_id = :curso_id ORDER BY ordem ASC");
        $stmt->execute(['curso_id' => $cursoId]);
        return $stmt->fetchAll();
    }

    public function criar(array $dados): int
    {
        return $this->inserir($dados);
    }

    public function atualizarAula(int $id, array $dados): bool
    {
        return $this->atualizar($id, $dados);
    }

    public function contarPorCurso(int $cursoId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM aulas WHERE curso_id = :curso_id");
        $stmt->execute(['curso_id' => $cursoId]);
        return (int) $stmt->fetchColumn();
    }
}
