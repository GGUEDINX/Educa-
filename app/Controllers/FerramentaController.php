<?php

namespace App\Controllers;

use App\Models\Ferramenta;

class FerramentaController
{
    private Ferramenta $ferramentaModel;

    public function __construct()
    {
        $this->ferramentaModel = new Ferramenta();
    }

    /** @return array{mensagem: string, tipo: string} */
    public function cadastrar(int $empresaId, string $nome, string $descricao, string $url): array
    {
        if (!empty($url) && !preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "https://" . $url;
        }

        if (empty($nome) || empty($url)) {
            return ['mensagem' => 'Preencha todos os campos obrigatórios.', 'tipo' => 'error'];
        }

        try {
            $this->ferramentaModel->criar([
                'nome' => $nome,
                'descricao' => $descricao,
                'url' => $url,
                'empresa_id' => $empresaId,
            ]);
            return ['mensagem' => 'Ferramenta cadastrada com sucesso!', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao cadastrar ferramenta: ' . $e->getMessage(), 'tipo' => 'error'];
        }
    }

    /** @return array{mensagem: string, tipo: string} */
    public function excluir(int $id, int $empresaId): array
    {
        try {
            $removida = $this->ferramentaModel->excluirDaEmpresa($id, $empresaId);
            return $removida
                ? ['mensagem' => 'Ferramenta removida com sucesso!', 'tipo' => 'success']
                : ['mensagem' => 'Não foi possível remover a ferramenta.', 'tipo' => 'error'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao excluir: ' . $e->getMessage(), 'tipo' => 'error'];
        }
    }

    public function listarPorEmpresa(int $empresaId): array
    {
        try {
            return $this->ferramentaModel->porEmpresa($empresaId);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /** Usado pelo abrir_ferramenta.php: registra o clique e retorna a URL de destino. */
    public function abrirERegistrarClique(int $id, int $empresaId): ?string
    {
        $url = $this->ferramentaModel->buscarUrl($id, $empresaId);
        if ($url) {
            $this->ferramentaModel->registrarClique($id);
        }
        return $url;
    }
}
