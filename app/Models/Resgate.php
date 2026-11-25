<?php

namespace App\Models;

use App\Core\Model;

class Resgate extends Model
{
    protected static string $tabela = 'resgates';

    public function criar(array $dados): int
    {
        return $this->inserir($dados);
    }

    /** Histórico de resgates do próprio funcionário, com o nome da recompensa. */
    public function doUsuario(int $usuarioId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, rec.nome AS recompensa_nome
            FROM resgates r
            JOIN recompensas rec ON rec.id = r.recompensa_id
            WHERE r.usuario_id = :usuario_id
            ORDER BY r.data_resgate DESC
        ");
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    /** Fila de resgates da empresa (para o admin acompanhar/entregar prêmios). */
    public function pendentesDaEmpresa(int $empresaId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, rec.nome AS recompensa_nome, u.nome AS funcionario_nome
            FROM resgates r
            JOIN recompensas rec ON rec.id = r.recompensa_id
            JOIN usuarios u ON u.id = r.usuario_id
            WHERE rec.empresa_id = :empresa_id AND r.status = 'pendente'
            ORDER BY r.data_resgate ASC
        ");
        $stmt->execute(['empresa_id' => $empresaId]);
        return $stmt->fetchAll();
    }

    public function marcarEntregue(int $id, int $empresaId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE resgates r
            JOIN recompensas rec ON rec.id = r.recompensa_id
            SET r.status = 'entregue'
            WHERE r.id = :id AND rec.empresa_id = :empresa_id
        ");
        $stmt->execute(['id' => $id, 'empresa_id' => $empresaId]);
        return $stmt->rowCount() > 0;
    }
}
