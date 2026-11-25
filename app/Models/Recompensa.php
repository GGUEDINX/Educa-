<?php

namespace App\Models;

use App\Core\Model;

class Recompensa extends Model
{
    protected static string $tabela = 'recompensas';

    public function criar(array $dados): int
    {
        return $this->inserir($dados);
    }

    /** Todas as recompensas da empresa (para a tela de gestão do admin), ativas e inativas. */
    public function todasDaEmpresa(int $empresaId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM recompensas WHERE empresa_id = :empresa_id ORDER BY ativo DESC, custo_pontos ASC");
        $stmt->execute(['empresa_id' => $empresaId]);
        return $stmt->fetchAll();
    }

    /** Apenas as recompensas disponíveis para resgate (vitrine do funcionário). */
    public function disponiveisDaEmpresa(int $empresaId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM recompensas WHERE empresa_id = :empresa_id AND ativo = 1 ORDER BY custo_pontos ASC");
        $stmt->execute(['empresa_id' => $empresaId]);
        return $stmt->fetchAll();
    }

    public function buscarDaEmpresa(int $id, int $empresaId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM recompensas WHERE id = :id AND empresa_id = :empresa_id LIMIT 1");
        $stmt->execute(['id' => $id, 'empresa_id' => $empresaId]);
        return $stmt->fetch() ?: null;
    }

    public function alternarAtivo(int $id, int $empresaId, bool $ativo): bool
    {
        $stmt = $this->db->prepare("UPDATE recompensas SET ativo = :ativo WHERE id = :id AND empresa_id = :empresa_id");
        $stmt->execute(['ativo' => $ativo ? 1 : 0, 'id' => $id, 'empresa_id' => $empresaId]);
        return $stmt->rowCount() > 0;
    }

    public function excluirDaEmpresa(int $id, int $empresaId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM recompensas WHERE id = :id AND empresa_id = :empresa_id");
        $stmt->execute(['id' => $id, 'empresa_id' => $empresaId]);
        return $stmt->rowCount() > 0;
    }
}
