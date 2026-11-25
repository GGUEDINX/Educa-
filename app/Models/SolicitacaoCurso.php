<?php

namespace App\Models;

use App\Core\Model;

class SolicitacaoCurso extends Model
{
    protected static string $tabela = 'solicitacoes_curso';

    public function criar(int $usuarioId, int $empresaId, string $titulo, string $descricao): int
    {
        return $this->inserir([
            'usuario_id' => $usuarioId,
            'empresa_id' => $empresaId,
            'titulo_sugerido' => $titulo,
            'descricao' => $descricao,
        ]);
    }

    public function porEmpresa(int $empresaId): array
    {
        $stmt = $this->db->prepare("
            SELECT sc.*, u.nome AS nome_funcionario
            FROM solicitacoes_curso sc
            JOIN usuarios u ON u.id = sc.usuario_id
            WHERE sc.empresa_id = :empresa_id
            ORDER BY FIELD(sc.status, 'pendente', 'aprovado', 'recusado'), sc.data_solicitacao DESC
        ");
        $stmt->execute(['empresa_id' => $empresaId]);
        return $stmt->fetchAll();
    }

    public function porUsuario(int $usuarioId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM solicitacoes_curso WHERE usuario_id = :usuario_id ORDER BY data_solicitacao DESC");
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function atualizarStatus(int $id, int $empresaId, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE solicitacoes_curso SET status = :status WHERE id = :id AND empresa_id = :empresa_id");
        return $stmt->execute(['status' => $status, 'id' => $id, 'empresa_id' => $empresaId]);
    }

    public function contarPendentes(int $empresaId): int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM solicitacoes_curso WHERE empresa_id = :empresa_id AND status = 'pendente'");
            $stmt->execute(['empresa_id' => $empresaId]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            return 0;
        }
    }
}
