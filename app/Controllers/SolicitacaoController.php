<?php

namespace App\Controllers;

use App\Models\SolicitacaoCurso;

class SolicitacaoController
{
    private SolicitacaoCurso $solicitacaoModel;

    public function __construct()
    {
        $this->solicitacaoModel = new SolicitacaoCurso();
    }

    /** @return array{mensagem: string, tipo: string} */
    public function enviar(int $usuarioId, int $empresaId, string $titulo, string $descricao): array
    {
        if (empty($titulo)) {
            return ['mensagem' => 'Digite o nome do curso que você gostaria de ver na plataforma.', 'tipo' => 'error'];
        }

        try {
            $this->solicitacaoModel->criar($usuarioId, $empresaId, $titulo, $descricao);
            return ['mensagem' => 'Sugestão enviada! Sua empresa vai analisar o pedido.', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Não foi possível enviar. Fale com o administrador (é necessário rodar a migração do banco de dados).', 'tipo' => 'error'];
        }
    }

    /** @return array{mensagem: string, tipo: string} */
    public function responder(int $id, int $empresaId, string $acao): array
    {
        $novoStatus = $acao === 'aprovar' ? 'aprovado' : ($acao === 'recusar' ? 'recusado' : null);
        if (!$novoStatus) {
            return ['mensagem' => '', 'tipo' => ''];
        }

        try {
            $this->solicitacaoModel->atualizarStatus($id, $empresaId, $novoStatus);
            return ['mensagem' => 'Solicitação atualizada.', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao atualizar a solicitação.', 'tipo' => 'error'];
        }
    }

    /** @return array{lista: array, erro_tabela: bool} */
    public function listar(string $tipoUsuario, int $usuarioId, int $empresaId): array
    {
        try {
            $lista = $tipoUsuario === 'admin_empresa'
                ? $this->solicitacaoModel->porEmpresa($empresaId)
                : $this->solicitacaoModel->porUsuario($usuarioId);
            return ['lista' => $lista, 'erro_tabela' => false];
        } catch (\PDOException $e) {
            return ['lista' => [], 'erro_tabela' => true];
        }
    }
}
