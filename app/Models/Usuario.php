<?php

namespace App\Models;

use App\Core\Model;

class Usuario extends Model
{
    protected static string $tabela = 'usuarios';

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function emailEmUsoPorOutro(string $email, int $idAtual): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
        $stmt->execute(['email' => $email, 'id' => $idAtual]);
        return $stmt->rowCount() > 0;
    }

    public function registrarAcesso(int $id): void
    {
        try {
            $this->db->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = :id")
                ->execute(['id' => $id]);
        } catch (\PDOException $e) {
            // Coluna ultimo_acesso ainda não existe (migração não rodada) - ignora
        }
    }

    public function atualizarSenha(int $id, string $novoHash): bool
    {
        return $this->atualizar($id, ['senha' => $novoHash]);
    }

    public function estaBloqueado(array $usuario): bool
    {
        return !empty($usuario['bloqueado_ate']) && strtotime($usuario['bloqueado_ate']) > time();
    }

    public function registrarTentativaFalha(int $id, int $tentativasAtuais): void
    {
        try {
            $novasTentativas = $tentativasAtuais + 1;
            $bloquearPor = $novasTentativas >= 5 ? "DATE_ADD(NOW(), INTERVAL 15 MINUTE)" : "NULL";
            $this->db->prepare("
                UPDATE usuarios SET tentativas_login = :tentativas, bloqueado_ate = $bloquearPor WHERE id = :id
            ")->execute(['tentativas' => $novasTentativas, 'id' => $id]);
        } catch (\PDOException $e) {
            // Colunas ainda não existem (migração não rodada) - ignora
        }
    }

    public function resetarTentativas(int $id): void
    {
        try {
            $this->db->prepare("UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL WHERE id = :id")
                ->execute(['id' => $id]);
        } catch (\PDOException $e) {
            // Colunas ainda não existem (migração não rodada) - ignora
        }
    }

    public function atualizarPerfil(int $id, array $dados): bool
    {
        return $this->atualizar($id, $dados);
    }

    public function contarFuncionariosPorEmpresa(int $empresaId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id = :empresa_id AND tipo_usuario = 'funcionario'");
        $stmt->execute(['empresa_id' => $empresaId]);
        return (int) $stmt->fetchColumn();
    }

    public function contarAtivosUltimaSemana(int $empresaId): ?int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM usuarios
                WHERE empresa_id = :empresa_id AND tipo_usuario = 'funcionario'
                  AND ultimo_acesso >= (NOW() - INTERVAL 7 DAY)
            ");
            $stmt->execute(['empresa_id' => $empresaId]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            return null; // coluna ainda não existe
        }
    }

    public function listarFuncionariosPorEmpresa(int $empresaId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE empresa_id = :empresa_id AND tipo_usuario = 'funcionario' ORDER BY nome ASC");
        $stmt->execute(['empresa_id' => $empresaId]);
        return $stmt->fetchAll();
    }

    public function criarFuncionario(array $dados): int
    {
        return $this->inserir($dados);
    }

    public function excluirFuncionario(int $id, int $empresaId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = :id AND empresa_id = :empresa_id AND tipo_usuario = 'funcionario'");
        $stmt->execute(['id' => $id, 'empresa_id' => $empresaId]);
        return $stmt->rowCount() > 0;
    }

    public function resetarSenhaFuncionario(int $id, int $empresaId, string $novoHash): bool
    {
        $stmt = $this->db->prepare("
            UPDATE usuarios SET senha = :senha, tentativas_login = 0, bloqueado_ate = NULL
            WHERE id = :id AND empresa_id = :empresa_id AND tipo_usuario = 'funcionario'
        ");
        $stmt->execute(['senha' => $novoHash, 'id' => $id, 'empresa_id' => $empresaId]);
        return $stmt->rowCount() > 0;
    }

    // ===================== RECOMPENSAS =====================

    public function creditarPontos(int $id, int $pontos): void
    {
        $this->db->prepare("UPDATE usuarios SET pontos = pontos + :pontos WHERE id = :id")
            ->execute(['pontos' => $pontos, 'id' => $id]);
    }

    public function debitarPontos(int $id, int $pontos): void
    {
        $this->db->prepare("UPDATE usuarios SET pontos = pontos - :pontos WHERE id = :id")
            ->execute(['pontos' => $pontos, 'id' => $id]);
    }

    public function saldoPontos(int $id): int
    {
        $stmt = $this->db->prepare("SELECT pontos FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    /** Ranking de funcionários da empresa por pontos, do maior para o menor. */
    public function rankingPorPontos(int $empresaId, int $limite = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT id, nome, foto_avatar, pontos
            FROM usuarios
            WHERE empresa_id = :empresa_id AND tipo_usuario = 'funcionario'
            ORDER BY pontos DESC, nome ASC
            LIMIT $limite
        ");
        $stmt->execute(['empresa_id' => $empresaId]);
        return $stmt->fetchAll();
    }
}
