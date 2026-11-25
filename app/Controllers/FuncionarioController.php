<?php

namespace App\Controllers;

use App\Models\Usuario;
use App\Core\Security;

class FuncionarioController
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    /** @return array{mensagem: string, tipo: string} */
    public function cadastrar(int $empresaId, string $nome, string $email, string $senha): array
    {
        if (empty($nome) || empty($email) || empty($senha)) {
            return ['mensagem' => 'Preencha todos os campos obrigatórios.', 'tipo' => 'error'];
        }

        if (strlen($senha) < 6) {
            return ['mensagem' => 'A senha deve ter pelo menos 6 caracteres.', 'tipo' => 'error'];
        }

        try {
            if ($this->usuarioModel->buscarPorEmail($email)) {
                return ['mensagem' => 'Este e-mail já está cadastrado por outro usuário.', 'tipo' => 'error'];
            }

            $this->usuarioModel->criarFuncionario([
                'nome' => $nome,
                'email' => $email,
                'senha' => Security::hashSenha($senha),
                'tipo_usuario' => 'funcionario',
                'empresa_id' => $empresaId,
                'foto_avatar' => 'default.png',
            ]);
            return ['mensagem' => 'Funcionário cadastrado com sucesso!', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao cadastrar: ' . $e->getMessage(), 'tipo' => 'error'];
        }
    }

    /** @return array{mensagem: string, tipo: string} */
    public function excluir(int $id, int $empresaId): array
    {
        try {
            $removido = $this->usuarioModel->excluirFuncionario($id, $empresaId);
            return $removido
                ? ['mensagem' => 'Funcionário removido com sucesso!', 'tipo' => 'success']
                : ['mensagem' => 'Não foi possível remover o funcionário (permissão negada ou usuário inexistente).', 'tipo' => 'error'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao excluir: ' . $e->getMessage(), 'tipo' => 'error'];
        }
    }

    /** @return array{mensagem: string, tipo: string} */
    public function resetarSenha(int $id, int $empresaId, string $novaSenha): array
    {
        if (strlen($novaSenha) < 6) {
            return ['mensagem' => 'A nova senha deve ter pelo menos 6 caracteres.', 'tipo' => 'error'];
        }

        try {
            $sucesso = $this->usuarioModel->resetarSenhaFuncionario($id, $empresaId, Security::hashSenha($novaSenha));
            return $sucesso
                ? ['mensagem' => 'Senha redefinida com sucesso! Avise o colaborador da nova senha.', 'tipo' => 'success']
                : ['mensagem' => 'Não foi possível redefinir a senha (permissão negada ou usuário inexistente).', 'tipo' => 'error'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao redefinir senha: ' . $e->getMessage(), 'tipo' => 'error'];
        }
    }

    public function listarPorEmpresa(int $empresaId): array
    {
        try {
            return $this->usuarioModel->listarFuncionariosPorEmpresa($empresaId);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Importa vários funcionários de uma vez, a partir de um texto colado (ou arquivo .csv).
     * Cada linha esperada no formato: Nome, email@empresa.com
     * Uma senha provisória aleatória é gerada para cada um.
     *
     * @return array{criados: array, ja_existentes: array, invalidas: array}
     */
    public function importarEmLote(int $empresaId, string $conteudo): array
    {
        $criados = [];
        $jaExistentes = [];
        $invalidas = [];

        $linhas = preg_split('/\r\n|\r|\n/', trim($conteudo));

        foreach ($linhas as $numeroLinha => $linha) {
            $linha = trim($linha);
            if ($linha === '') {
                continue;
            }

            // Aceita separador por vírgula, ponto e vírgula ou tabulação (comum em CSV/planilha)
            $partes = preg_split('/[,;\t]/', $linha, 2);
            $nome = trim($partes[0] ?? '');
            $email = trim($partes[1] ?? '');

            if (empty($nome) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalidas[] = ['linha' => $numeroLinha + 1, 'conteudo' => $linha];
                continue;
            }

            try {
                if ($this->usuarioModel->buscarPorEmail($email)) {
                    $jaExistentes[] = ['nome' => $nome, 'email' => $email];
                    continue;
                }

                $senhaProvisoria = $this->gerarSenhaProvisoria();

                $this->usuarioModel->criarFuncionario([
                    'nome' => $nome,
                    'email' => $email,
                    'senha' => Security::hashSenha($senhaProvisoria),
                    'tipo_usuario' => 'funcionario',
                    'empresa_id' => $empresaId,
                    'foto_avatar' => 'default.png',
                ]);

                $criados[] = ['nome' => $nome, 'email' => $email, 'senha' => $senhaProvisoria];
            } catch (\PDOException $e) {
                $invalidas[] = ['linha' => $numeroLinha + 1, 'conteudo' => $linha];
            }
        }

        return ['criados' => $criados, 'ja_existentes' => $jaExistentes, 'invalidas' => $invalidas];
    }

    private function gerarSenhaProvisoria(): string
    {
        // 8 caracteres, fáceis de digitar (sem caracteres ambíguos como 0/O, 1/l)
        $caracteres = 'abcdefghjkmnpqrstuvwxyzACDEFGHJKMNPQRSTUVWXYZ23456789';
        $senha = '';
        for ($i = 0; $i < 8; $i++) {
            $senha .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }
        return $senha;
    }
}
