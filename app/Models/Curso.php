<?php

namespace App\Models;

use App\Core\Model;

class Curso extends Model
{
    protected static string $tabela = 'cursos';

    /** Lista todos os cursos junto com o status de conclusão do usuário logado. */
    public function todosComProgresso(int $usuarioId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*,
            (SELECT concluido FROM progresso_cursos WHERE curso_id = c.id AND usuario_id = :usuario_id) as concluido
            FROM cursos c
            ORDER BY c.id ASC
        ");
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    /** Cursos adicionados mais recentemente, para destacar no dashboard do funcionário. */
    public function recentesComProgresso(int $usuarioId, int $limite = 3): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*,
            (SELECT concluido FROM progresso_cursos WHERE curso_id = c.id AND usuario_id = :usuario_id) as concluido
            FROM cursos c
            ORDER BY c.criado_em DESC, c.id DESC
            LIMIT $limite
        ");
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function contarTodos(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM cursos")->fetchColumn();
    }

    public function contarObrigatorios(): int
    {
        try {
            return (int) $this->db->query("SELECT COUNT(*) FROM cursos WHERE obrigatorio = 1")->fetchColumn();
        } catch (\PDOException $e) {
            return 0; // coluna ainda não existe
        }
    }

    /** Top 5 cursos mais buscados/iniciados pelos funcionários de uma empresa. */
    public function destaquesPorEmpresa(int $empresaId, int $limite = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT c.titulo, COUNT(u.id) AS total_acessos
            FROM cursos c
            LEFT JOIN progresso_cursos pc ON pc.curso_id = c.id
            LEFT JOIN usuarios u ON u.id = pc.usuario_id AND u.empresa_id = :empresa_id
            GROUP BY c.id, c.titulo
            ORDER BY total_acessos DESC
            LIMIT $limite
        ");
        $stmt->execute(['empresa_id' => $empresaId]);
        return $stmt->fetchAll();
    }

    public function criar(array $dados): int
    {
        return $this->inserir($dados);
    }

    public function atualizarCurso(int $id, array $dados): bool
    {
        return $this->atualizar($id, $dados);
    }

    public function todosOrdenados(): array
    {
        return $this->db->query("SELECT * FROM cursos ORDER BY id DESC")->fetchAll();
    }
}
