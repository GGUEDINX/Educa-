<?php

namespace App\Models;

use App\Core\Model;

class Ferramenta extends Model
{
    protected static string $tabela = 'ferramentas';

    public function porEmpresa(int $empresaId): array
    {
        $stmt = $this->db->prepare("SELECT id, nome, descricao, url FROM ferramentas WHERE empresa_id = :empresa_id ORDER BY nome ASC");
        $stmt->execute(['empresa_id' => $empresaId]);
        return $stmt->fetchAll();
    }

    public function buscarUrl(int $id, int $empresaId): ?string
    {
        $stmt = $this->db->prepare("SELECT url FROM ferramentas WHERE id = :id AND empresa_id = :empresa_id");
        $stmt->execute(['id' => $id, 'empresa_id' => $empresaId]);
        $ferramenta = $stmt->fetch();
        return $ferramenta['url'] ?? null;
    }

    public function criar(array $dados): int
    {
        return $this->inserir($dados);
    }

    public function excluirDaEmpresa(int $id, int $empresaId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM ferramentas WHERE id = :id AND empresa_id = :empresa_id");
        $stmt->execute(['id' => $id, 'empresa_id' => $empresaId]);
        return $stmt->rowCount() > 0;
    }

    public function contarPorEmpresa(int $empresaId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM ferramentas WHERE empresa_id = :empresa_id");
        $stmt->execute(['empresa_id' => $empresaId]);
        return (int) $stmt->fetchColumn();
    }

    public function registrarClique(int $id): void
    {
        try {
            $this->db->prepare("UPDATE ferramentas SET cliques = cliques + 1 WHERE id = :id")
                ->execute(['id' => $id]);
        } catch (\PDOException $e) {
            // Coluna "cliques" ainda não existe (migração não rodada) - ignora
        }
    }

    public function topAcessadas(int $empresaId, int $limite = 3): array
    {
        try {
            $stmt = $this->db->prepare("SELECT nome, cliques FROM ferramentas WHERE empresa_id = :empresa_id ORDER BY cliques DESC LIMIT $limite");
            $stmt->execute(['empresa_id' => $empresaId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }
}
