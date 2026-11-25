<?php

namespace App\Controllers;

use App\Models\Usuario;
use App\Models\Empresa;
use App\Core\Security;

class AuthController
{
    private Usuario $usuarioModel;
    private Empresa $empresaModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
        $this->empresaModel = new Empresa();
    }

    /** Se já existe sessão ativa, redireciona para o dashboard certo. */
    public function redirecionarSeJaLogado(): void
    {
        if (isset($_SESSION['usuario_id']) && isset($_SESSION['tipo_usuario'])) {
            $this->redirecionarParaDashboard($_SESSION['tipo_usuario']);
        }
    }
    /**
     * Processa o formulário de login.
     * Retorna uma mensagem de erro (string vazia se deu tudo certo e o usuário já foi redirecionado).
     */
    public function login(string $email, string $senha): string
    {
        if (empty($email) || empty($senha)) {
            return 'Preencha todos os campos.';
        }

        try {
            $usuario = $this->usuarioModel->buscarPorEmail($email);

            // Mensagem genérica de propósito: não revela se o e-mail existe ou não
            if (!$usuario) {
                return 'E-mail ou senha incorretos.';
            }

            if ($this->usuarioModel->estaBloqueado($usuario)) {
                return 'Muitas tentativas erradas. Tente novamente em alguns minutos.';
            }

            if (!Security::verificarSenha($senha, $usuario['senha'])) {
                $this->usuarioModel->registrarTentativaFalha($usuario['id'], (int)($usuario['tentativas_login'] ?? 0));
                return 'E-mail ou senha incorretos.';
            }

            // Login certo: zera tentativas e, se a senha ainda estava em texto puro, já re-hasheia
            $this->usuarioModel->resetarTentativas($usuario['id']);
            if (!preg_match('/^\$2[abxy]\$/', $usuario['senha'])) {
                $this->usuarioModel->atualizarSenha($usuario['id'], Security::hashSenha($senha));
            }

            // Regenera o ID de sessão para evitar fixação de sessão
            session_regenerate_id(true);

            $this->criarSessao($usuario);
            $this->usuarioModel->registrarAcesso($usuario['id']);
            $this->redirecionarParaDashboard($_SESSION['tipo_usuario']);
            return '';
        } catch (\PDOException $e) {
            error_log('[AuthController::login] ' . $e->getMessage());
            return 'Não foi possível acessar sua conta agora. Tente novamente em alguns instantes.';
        }
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        header("Location: login.php");
        exit();
    }

    /** Autentica um usuário já validado (usado no login normal e logo após um cadastro novo). */
    public function autenticarUsuario(array $usuario): void
    {
        session_regenerate_id(true);
        $this->criarSessao($usuario);
        $this->usuarioModel->registrarAcesso($usuario['id']);
    }

    private function criarSessao(array $usuario): void
    {
        $_SESSION['usuario_id']     = $usuario['id'];
        $_SESSION['usuario_nome']   = $usuario['nome'] ?? $usuario['nome_usuario'] ?? 'Usuário';
        $_SESSION['usuario_avatar'] = $usuario['foto_avatar'] ?? 'default.png';
        $_SESSION['empresa_id']     = $usuario['empresa_id'] ?? null;
        $_SESSION['tipo_usuario']   = $usuario['tipo_usuario'] ?? 'funcionario';

        // Personalização de cores (vem do banco) — validadas para evitar injeção via CSS/HTML
        $_SESSION['cor_destaque'] = Security::corHexValida($usuario['cor_primaria'] ?? null);
        $_SESSION['cor_botoes']   = Security::corHexValida($usuario['cor_botoes'] ?? null);
        $_SESSION['tema']         = ($usuario['tema'] ?? 'escuro') === 'claro' ? 'claro' : 'escuro';

        $_SESSION['empresa_nome'] = !empty($usuario['empresa_id'])
            ? ($this->empresaModel->buscarNome($usuario['empresa_id']) ?? 'Tech Solutions')
            : 'Tech Solutions';
    }

    public function redirecionarParaDashboard(string $tipoUsuario): void
    {
        $destino = match ($tipoUsuario) {
            'admin_empresa' => 'dashboard_empresa.php',
            'pedagogia' => 'dashboard_pedagogico.php',
            default => 'dashboard_funcionario.php',
        };
        header("Location: $destino");
        exit();
    }
}
