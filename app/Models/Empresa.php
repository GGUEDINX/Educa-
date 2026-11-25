<?php

namespace App\Models;

use App\Core\Model;

class Empresa extends Model
{
    protected static string $tabela = 'empresas';

    public function buscarNome(int $id): ?string
    {
        $stmt = $this->db->prepare("SELECT nome FROM empresas WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $empresa = $stmt->fetch();
        return $empresa['nome'] ?? null;
    }

    public function criar(string $nomeEmpresa, string $nomeResponsavel): int
    {
        return $this->inserir([
            'nome_empresa' => $nomeEmpresa,
            'nome' => $nomeResponsavel,
            'cnpj' => null,
        ]);
    }

    public function definirPlano(int $empresaId, string $plano): void
    {
        try {
            $this->atualizar($empresaId, ['plano' => $plano]);
        } catch (\PDOException $e) {
            // Coluna "plano" ainda não existe (migração não rodada) - ignora silenciosamente
        }
    }
}
