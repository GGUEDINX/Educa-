<?php

namespace App\Controllers;

use App\Models\Usuario;
use App\Core\Security;

class ConfiguracaoController
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    public function buscarDadosDoUsuario(int $usuarioId): ?array
    {
        $usuario = $this->usuarioModel->buscarPorId($usuarioId);
        if (!$usuario) {
            return null;
        }
        return [
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'foto_avatar' => $usuario['foto_avatar'],
            'cor_primaria' => $usuario['cor_primaria'],
            'cor_botoes' => $usuario['cor_botoes'],
            'tema' => $usuario['tema'] ?? 'escuro',
        ];
    }

    /**
     * Processa o upload de avatar, se houver, e retorna o nome de arquivo a ser salvo.
     */
    public function processarUploadAvatar(int $usuarioId, string $avatarAtual, ?array $arquivo): string
    {
        if (!$arquivo || $arquivo['error'] !== UPLOAD_ERR_OK) {
            return $avatarAtual;
        }

        // Limite de 2 MB
        if ($arquivo['size'] > 2 * 1024 * 1024) {
            return $avatarAtual;
        }

        // Valida o tipo REAL do arquivo (não confia só na extensão do nome, que pode ser falsificada)
        $tiposPermitidos = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
        ];
        $tipoReal = mime_content_type($arquivo['tmp_name']);
        if (!isset($tiposPermitidos[$tipoReal])) {
            return $avatarAtual;
        }
        $extensao = $tiposPermitidos[$tipoReal];

        $novoNome = "avatar_" . $usuarioId . "_" . time() . "." . $extensao;
        $destino = "uploads/avatars/" . $novoNome;

        if (!is_dir('uploads/avatars')) {
            mkdir('uploads/avatars', 0777, true);
        }

        if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
            if ($avatarAtual !== 'default.png' && file_exists("uploads/avatars/" . $avatarAtual)) {
                unlink("uploads/avatars/" . $avatarAtual);
            }
            return $novoNome;
        }

        return $avatarAtual;
    }

    /** Garante que a cor recebida é um hexadecimal válido (#rrggbb), evitando injeção de HTML/CSS. */
    private function corValida(string $cor, string $padrao = '#3b82f6'): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $cor) ? $cor : $padrao;
    }

    /** @return array{mensagem: string, tipo: string, avatar: string} */
    public function atualizarPerfil(int $usuarioId, string $nome, string $email, string $senhaNova, string $avatar, string $corPrimaria, string $corBotoes, string $tema = 'escuro'): array
    {
        if (empty($nome) || empty($email)) {
            return ['mensagem' => 'Preencha nome e e-mail.', 'tipo' => 'error', 'avatar' => $avatar];
        }

        if (!empty($senhaNova) && strlen($senhaNova) < 6) {
            return ['mensagem' => 'A nova senha deve ter pelo menos 6 caracteres.', 'tipo' => 'error', 'avatar' => $avatar];
        }

        $corPrimaria = $this->corValida($corPrimaria);
        $corBotoes = $this->corValida($corBotoes);
        $tema = $tema === 'claro' ? 'claro' : 'escuro';

        try {
            if ($this->usuarioModel->emailEmUsoPorOutro($email, $usuarioId)) {
                return ['mensagem' => 'O e-mail digitado já está em uso por outro usuário.', 'tipo' => 'error', 'avatar' => $avatar];
            }

            $dados = [
                'nome' => $nome,
                'email' => $email,
                'foto_avatar' => $avatar,
                'cor_primaria' => $corPrimaria,
                'cor_botoes' => $corBotoes,
                'tema' => $tema,
            ];

            if (!empty($senhaNova)) {
                $dados['senha'] = Security::hashSenha($senhaNova);
            }

            try {
                $this->usuarioModel->atualizarPerfil($usuarioId, $dados);
            } catch (\PDOException $e) {
                // Coluna "tema" ainda não existe (migração não rodada): salva o resto sem ela
                unset($dados['tema']);
                $this->usuarioModel->atualizarPerfil($usuarioId, $dados);
                return ['mensagem' => 'Perfil atualizado! (Rode a migração do banco para habilitar o modo claro/escuro.)', 'tipo' => 'success', 'avatar' => $avatar, 'cor_primaria' => $corPrimaria, 'cor_botoes' => $corBotoes, 'tema' => 'escuro'];
            }

            return ['mensagem' => 'Perfil atualizado com sucesso!', 'tipo' => 'success', 'avatar' => $avatar, 'cor_primaria' => $corPrimaria, 'cor_botoes' => $corBotoes, 'tema' => $tema];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro: ' . $e->getMessage(), 'tipo' => 'error', 'avatar' => $avatar, 'cor_primaria' => $corPrimaria, 'cor_botoes' => $corBotoes, 'tema' => $tema];
        }
    }
}
