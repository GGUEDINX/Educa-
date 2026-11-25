<?php

namespace App\Controllers;

use App\Models\Usuario;
use App\Models\Empresa;
use App\Core\Security;

class CadastroController
{
    /** Planos exibidos na tela — apenas ilustrativos, sem cobrança real. */
    public const PLANOS = [
        'basico' => ['nome' => 'Básico', 'preco' => 'R$ 0', 'periodo' => '/mês', 'destaque' => false,
            'recursos' => ['Até 10 colaboradores', 'Trilhas e certificação', 'Dashboard de engajamento']],
        'profissional' => ['nome' => 'Profissional', 'preco' => 'R$ 199', 'periodo' => '/mês', 'destaque' => true,
            'recursos' => ['Até 50 colaboradores', 'Tudo do Básico', 'Personalização de marca', 'Suporte pedagógico dedicado']],
        'empresarial' => ['nome' => 'Empresarial', 'preco' => 'Sob consulta', 'periodo' => '', 'destaque' => false,
            'recursos' => ['Colaboradores ilimitados', 'Tudo do Profissional', 'Gestor de conta dedicado']],
    ];

    private Usuario $usuarioModel;
    private Empresa $empresaModel;
    private AuthController $authController;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
        $this->empresaModel = new Empresa();
        $this->authController = new AuthController();
    }

    /**
     * Cria a empresa e o usuário administrador, e já autentica a sessão.
     * Retorna uma mensagem de erro (string vazia se deu tudo certo).
     */
    public function cadastrarEmpresa(string $nomeEmpresa, string $nomeResponsavel, string $email, string $senha, string $confirmarSenha): string
    {
        if (empty($nomeEmpresa) || empty($nomeResponsavel) || empty($email) || empty($senha)) {
            return 'Preencha todos os campos.';
        }
        if (strlen($senha) < 6) {
            return 'A senha deve ter pelo menos 6 caracteres.';
        }
        if ($senha !== $confirmarSenha) {
            return 'As senhas não coincidem.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Digite um e-mail válido.';
        }

        try {
            if ($this->usuarioModel->buscarPorEmail($email)) {
                return 'Já existe uma conta com esse e-mail.';
            }

            $empresaId = $this->empresaModel->criar($nomeEmpresa, $nomeResponsavel);

            $usuarioId = $this->usuarioModel->criarFuncionario([
                'nome' => $nomeResponsavel,
                'email' => $email,
                'senha' => Security::hashSenha($senha),
                'tipo_usuario' => 'admin_empresa',
                'empresa_id' => $empresaId,
                'foto_avatar' => 'default.png',
            ]);

            $usuario = $this->usuarioModel->buscarPorId($usuarioId);
            $this->authController->autenticarUsuario($usuario);

            return '';
        } catch (\PDOException $e) {
            error_log('[CadastroController::cadastrarEmpresa] ' . $e->getMessage());
            return 'Não foi possível concluir o cadastro agora. Tente novamente em alguns instantes.';
        }
    }

    /** Salva o plano escolhido (apenas visual, sem cobrança) e manda para o dashboard. */
    public function escolherPlano(int $empresaId, string $plano): void
    {
        if (!isset(self::PLANOS[$plano])) {
            $plano = 'basico';
        }
        $this->empresaModel->definirPlano($empresaId, $plano);
        $this->authController->redirecionarParaDashboard('admin_empresa');
    }
}
