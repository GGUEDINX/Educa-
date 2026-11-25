<?php
// cadastro.php
require_once __DIR__ . '/app/autoload.php';

use App\Controllers\CadastroController;
use App\Controllers\AuthController;
use App\Core\Security;

Security::iniciarSessaoSegura();
Security::enviarCabecalhosSeguranca();

(new AuthController())->redirecionarSeJaLogado();

$cadastroController = new CadastroController();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::validarCsrf();
    $erro = $cadastroController->cadastrarEmpresa(
        trim($_POST['nome_empresa'] ?? ''),
        trim($_POST['nome_responsavel'] ?? ''),
        trim($_POST['email'] ?? ''),
        $_POST['senha'] ?? '',
        $_POST['confirmar_senha'] ?? ''
    );

    if ($erro === '') {
        header("Location: planos.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastre sua empresa | educa+</title>
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem 0;
            box-sizing: border-box;
        }
        .container {
            width: 100%;
            max-width: 440px;
            background: #1e293b;
            padding: 2.5rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #94a3b8; font-size: 0.85rem; font-weight: 600; }
        .form-control { width: 100%; padding: 0.75rem; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: white; box-sizing: border-box; }
        .btn { width: 100%; padding: 0.75rem; background: #3b82f6; border: none; color: white; font-weight: 700; border-radius: 6px; cursor: pointer; margin-top: 0.5rem; }
        .btn:hover { background: #2563eb; }
        .error { background: rgba(59, 130, 246, 0.15); border-left: 4px solid #3b82f6; color: #93c5fd; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="container">
        <h2 style="text-align: center; margin-bottom: 0.3rem; font-weight: 800;">educa<span style="color: #3b82f6;">+</span></h2>
        <p style="text-align: center; margin: 0 0 2rem 0; color: #94a3b8; font-size: 0.9rem;">Cadastre sua empresa e comece agora</p>

        <?php if (!empty($erro)): ?>
            <div class="error">⚠ <?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <form action="cadastro.php" method="POST">
            <?php echo Security::campoCsrf(); ?>
            <div class="form-group">
                <label>Nome da Empresa</label>
                <input type="text" name="nome_empresa" class="form-control" placeholder="Ex: Tech Solutions" required value="<?php echo htmlspecialchars($_POST['nome_empresa'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Seu nome</label>
                <input type="text" name="nome_responsavel" class="form-control" placeholder="Nome do responsável" required value="<?php echo htmlspecialchars($_POST['nome_responsavel'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>E-mail Corporativo</label>
                <input type="email" name="email" class="form-control" placeholder="seu@empresa.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" class="form-control" placeholder="Mínimo 6 caracteres" required>
            </div>
            <div class="form-group">
                <label>Confirmar Senha</label>
                <input type="password" name="confirmar_senha" class="form-control" placeholder="Digite a senha novamente" required>
            </div>
            <button type="submit" class="btn">Continuar</button>
        </form>
        <p style="text-align: center; margin-top: 1.5rem; font-size: 0.85rem; color: #94a3b8;">
            Já tem conta? <a href="login.php" style="color: #3b82f6; text-decoration: none; font-weight: 600;">Fazer login</a>
        </p>
    </div>
</body>
</html>
