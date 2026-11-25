<?php

namespace App\Controllers;

use App\Models\Recompensa;
use App\Models\Resgate;
use App\Models\Usuario;
use App\Core\Database;

class RecompensaController
{
    private Recompensa $recompensaModel;
    private Resgate $resgateModel;
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->recompensaModel = new Recompensa();
        $this->resgateModel = new Resgate();
        $this->usuarioModel = new Usuario();
    }

    // ===================== RANKING (empresa + funcionário) =====================

    public function ranking(int $empresaId): array
    {
        try {
            return $this->usuarioModel->rankingPorPontos($empresaId);
        } catch (\PDOException $e) {
            return []; // migração ainda não rodada
        }
    }

    public function saldoDoFuncionario(int $usuarioId): int
    {
        try {
            return $this->usuarioModel->saldoPontos($usuarioId);
        } catch (\PDOException $e) {
            return 0;
        }
    }

    // ===================== GESTÃO DE RECOMPENSAS (admin_empresa) =====================

    public function listarParaGestao(int $empresaId): array
    {
        try {
            return $this->recompensaModel->todasDaEmpresa($empresaId);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /** @return array{mensagem: string, tipo: string} */
    public function cadastrar(int $empresaId, string $nome, string $descricao, int $custoPontos): array
    {
        if (empty($nome) || $custoPontos <= 0) {
            return ['mensagem' => 'Preencha o nome do prêmio e um custo em pontos maior que zero.', 'tipo' => 'error'];
        }

        try {
            $this->recompensaModel->criar([
                'empresa_id' => $empresaId,
                'nome' => $nome,
                'descricao' => $descricao,
                'custo_pontos' => $custoPontos,
            ]);
            return ['mensagem' => 'Recompensa cadastrada com sucesso!', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao cadastrar recompensa: ' . $e->getMessage(), 'tipo' => 'error'];
        }
    }

    /** @return array{mensagem: string, tipo: string} */
    public function alternarAtivo(int $id, int $empresaId, bool $ativo): array
    {
        try {
            $ok = $this->recompensaModel->alternarAtivo($id, $empresaId, $ativo);
            return $ok
                ? ['mensagem' => $ativo ? 'Recompensa reativada.' : 'Recompensa desativada.', 'tipo' => 'success']
                : ['mensagem' => 'Não foi possível atualizar a recompensa.', 'tipo' => 'error'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao atualizar: ' . $e->getMessage(), 'tipo' => 'error'];
        }
    }

    /** @return array{mensagem: string, tipo: string} */
    public function excluir(int $id, int $empresaId): array
    {
        try {
            $ok = $this->recompensaModel->excluirDaEmpresa($id, $empresaId);
            return $ok
                ? ['mensagem' => 'Recompensa removida.', 'tipo' => 'success']
                : ['mensagem' => 'Não foi possível remover a recompensa.', 'tipo' => 'error'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao excluir: ' . $e->getMessage(), 'tipo' => 'error'];
        }
    }

    public function listarPendentes(int $empresaId): array
    {
        try {
            return $this->resgateModel->pendentesDaEmpresa($empresaId);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /** @return array{mensagem: string, tipo: string} */
    public function marcarEntregue(int $id, int $empresaId): array
    {
        try {
            $ok = $this->resgateModel->marcarEntregue($id, $empresaId);
            return $ok
                ? ['mensagem' => 'Resgate marcado como entregue.', 'tipo' => 'success']
                : ['mensagem' => 'Não foi possível atualizar o resgate.', 'tipo' => 'error'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao atualizar: ' . $e->getMessage(), 'tipo' => 'error'];
        }
    }

    // ===================== VITRINE + RESGATE (funcionário) =====================

    public function listarDisponiveis(int $empresaId): array
    {
        try {
            return $this->recompensaModel->disponiveisDaEmpresa($empresaId);
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function historicoDoFuncionario(int $usuarioId): array
    {
        try {
            return $this->resgateModel->doUsuario($usuarioId);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Troca os pontos do funcionário por uma recompensa. Valida saldo e debita
     * dentro de uma transação para evitar resgates concorrentes furarem o saldo.
     * @return array{mensagem: string, tipo: string}
     */
    public function resgatar(int $usuarioId, int $recompensaId, int $empresaId): array
    {
        $recompensa = $this->recompensaModel->buscarDaEmpresa($recompensaId, $empresaId);

        if (!$recompensa || !$recompensa['ativo']) {
            return ['mensagem' => 'Essa recompensa não está mais disponível.', 'tipo' => 'error'];
        }

        $db = Database::getConexao();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("SELECT pontos FROM usuarios WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $usuarioId]);
            $saldoAtual = (int) $stmt->fetchColumn();

            if ($saldoAtual < (int) $recompensa['custo_pontos']) {
                $db->rollBack();
                return ['mensagem' => 'Você ainda não tem pontos suficientes para essa recompensa.', 'tipo' => 'error'];
            }

            $this->usuarioModel->debitarPontos($usuarioId, (int) $recompensa['custo_pontos']);
            $this->resgateModel->criar([
                'usuario_id' => $usuarioId,
                'recompensa_id' => $recompensaId,
                'pontos_gastos' => $recompensa['custo_pontos'],
            ]);

            $db->commit();
            return ['mensagem' => 'Prêmio resgatado! Aguarde a empresa liberar a entrega.', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            $db->rollBack();
            return ['mensagem' => 'Erro ao resgatar recompensa: ' . $e->getMessage(), 'tipo' => 'error'];
        }
    }
}
