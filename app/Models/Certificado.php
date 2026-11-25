<?php

namespace App\Models;

use App\Core\Model;

class Certificado extends Model
{
    protected static string $tabela = 'certificados';

    public function porUsuario(int $usuarioId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM certificados WHERE usuario_id = :usuario_id ORDER BY data_conclusao DESC");
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function buscarPorIdEUsuario(int $id, int $usuarioId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM certificados WHERE id = :id AND usuario_id = :usuario_id");
        $stmt->execute(['id' => $id, 'usuario_id' => $usuarioId]);
        return $stmt->fetch() ?: null;
    }

    public function buscarPorCodigo(string $codigo): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM certificados WHERE codigo_validacao = :codigo LIMIT 1");
        $stmt->execute(['codigo' => $codigo]);
        return $stmt->fetch() ?: null;
    }

    public function criar(array $dados): int
    {
        return $this->inserir($dados);
    }

    /** Top 5 colaboradores que mais emitiram certificados na empresa. */
    public function rankingPorEmpresa(int $empresaId, int $limite = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT u.nome, COUNT(c.id) AS total_certificados
            FROM certificados c
            JOIN usuarios u ON u.id = c.usuario_id
            WHERE u.empresa_id = :empresa_id AND u.tipo_usuario = 'funcionario'
            GROUP BY u.id, u.nome
            ORDER BY total_certificados DESC, MAX(c.data_conclusao) DESC
            LIMIT $limite
        ");
        $stmt->execute(['empresa_id' => $empresaId]);
        return $stmt->fetchAll();
    }

    /** Certificados da empresa que vencem nos próximos 30 dias. */
    public function vencimentosProximos(int $empresaId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.curso_nome, c.validade, u.nome AS nome_funcionario
                FROM certificados c
                JOIN usuarios u ON u.id = c.usuario_id
                WHERE u.empresa_id = :empresa_id
                  AND c.validade IS NOT NULL
                  AND c.validade BETWEEN CURDATE() AND (CURDATE() + INTERVAL 30 DAY)
                ORDER BY c.validade ASC
            ");
            $stmt->execute(['empresa_id' => $empresaId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return []; // coluna validade ainda não existe
        }
    }
}
