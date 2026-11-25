<?php

namespace App\Models;

use App\Core\Model;

class DuvidaCurso extends Model
{
    protected static string $tabela = 'duvidas_curso';

    public function criar(int $usuarioId, int $cursoId, string $pergunta): int
    {
        return $this->inserir([
            'usuario_id' => $usuarioId,
            'curso_id' => $cursoId,
            'pergunta' => $pergunta,
        ]);
    }

    public function responder(int $id, int $respondidoPor, string $resposta): bool
    {
        $stmt = $this->db->prepare("
            UPDATE duvidas_curso
            SET resposta = :resposta, respondido_por = :respondido_por, status = 'respondida', data_resposta = NOW()
            WHERE id = :id
        ");
        return $stmt->execute(['resposta' => $resposta, 'respondido_por' => $respondidoPor, 'id' => $id]);
    }

    /** Todas as dúvidas dos funcionários de uma empresa (para a pedagogia responder). */
    public function porEmpresa(int $empresaId): array
    {
        $stmt = $this->db->prepare("
            SELECT d.*, u.nome AS nome_aluno, c.titulo AS titulo_curso
            FROM duvidas_curso d
            JOIN usuarios u ON u.id = d.usuario_id
            JOIN cursos c ON c.id = d.curso_id
            WHERE u.empresa_id = :empresa_id
            ORDER BY FIELD(d.status, 'pendente', 'respondida'), d.data_pergunta DESC
        ");
        $stmt->execute(['empresa_id' => $empresaId]);
        return $stmt->fetchAll();
    }

    public function porUsuario(int $usuarioId): array
    {
        $stmt = $this->db->prepare("
            SELECT d.*, c.titulo AS titulo_curso
            FROM duvidas_curso d
            JOIN cursos c ON c.id = d.curso_id
            WHERE d.usuario_id = :usuario_id
            ORDER BY d.data_pergunta DESC
        ");
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function contarPendentes(int $empresaId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM duvidas_curso d
            JOIN usuarios u ON u.id = d.usuario_id
            WHERE u.empresa_id = :empresa_id AND d.status = 'pendente'
        ");
        $stmt->execute(['empresa_id' => $empresaId]);
        return (int) $stmt->fetchColumn();
    }

    /** Quantas respostas o funcionário ainda não viu (para o "sininho" de notificação). */
    public function contarRespondidasNaoLidas(int $usuarioId): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM duvidas_curso
                WHERE usuario_id = :usuario_id AND status = 'respondida' AND lida_pelo_aluno = 0
            ");
            $stmt->execute(['usuario_id' => $usuarioId]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            return 0; // coluna ainda não existe (migração não rodada)
        }
    }

    /** Marca todas as dúvidas do funcionário como lidas (chamado ao abrir a página de suporte). */
    public function marcarTodasComoLidas(int $usuarioId): void
    {
        try {
            $this->db->prepare("UPDATE duvidas_curso SET lida_pelo_aluno = 1 WHERE usuario_id = :usuario_id")
                ->execute(['usuario_id' => $usuarioId]);
        } catch (\PDOException $e) {
            // coluna ainda não existe (migração não rodada) - ignora
        }
    }
}
