<?php

namespace App\Core;

use PDO;

/**
 * Classe base para todos os Models.
 * Cada Model filho só precisa definir a propriedade $tabela.
 */
abstract class Model
{
    protected static string $tabela = '';
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConexao();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM " . static::$tabela . " WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function todos(): array
    {
        return $this->db->query("SELECT * FROM " . static::$tabela)->fetchAll();
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM " . static::$tabela . " WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * INSERT genérico: recebe um array associativo [coluna => valor].
     * Retorna o ID inserido.
     */
    protected function inserir(array $dados): int
    {
        $colunas = implode(', ', array_keys($dados));
        $placeholders = ':' . implode(', :', array_keys($dados));

        $stmt = $this->db->prepare("INSERT INTO " . static::$tabela . " ($colunas) VALUES ($placeholders)");
        $stmt->execute($dados);

        return (int) $this->db->lastInsertId();
    }

    /**
     * UPDATE genérico: recebe o ID e um array associativo [coluna => valor].
     */
    protected function atualizar(int $id, array $dados): bool
    {
        $sets = [];
        foreach (array_keys($dados) as $coluna) {
            $sets[] = "$coluna = :$coluna";
        }
        $sql = "UPDATE " . static::$tabela . " SET " . implode(', ', $sets) . " WHERE id = :id";

        $dados['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($dados);
    }
}
