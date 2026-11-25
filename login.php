<?php
// login.php
require_once __DIR__ . '/app/autoload.php';

use App\Controllers\AuthController;
use App\Core\Security;

Security::iniciarSessaoSegura();
Security::enviarCabecalhosSeguranca();

$auth = new AuthController();
$auth->redirecionarSeJaLogado();

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::validarCsrf();
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'] ?? '';
    $erro = $auth->login($email ?? '', $senha);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | educa+</title>
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            width: 100%;
            max-width: 400px;
            background: #1e293b;
            padding: 2.5rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #94a3b8; font-size: 0.85rem; font-weight: 600; }
        .form-control { width: 100%; padding: 0.75rem; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: white; box-sizing: border-box; }
        .btn { width: 100%; padding: 0.75rem; background: #3b82f6; border: none; color: white; font-weight: 700; border-radius: 6px; cursor: pointer; margin-top: 1rem; }
        .btn:hover { background: #2563eb; }
        .error { background: rgba(59, 130, 246, 0.15); border-left: 4px solid #3b82f6; color: #93c5fd; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="container">
        <h2 style="text-align: center; margin-bottom: 2rem; font-weight: 800;">educa<span style="color: #3b82f6;">+</span></h2>
        
        <?php if (!empty($erro)): ?>
            <div class="error">⚠ <?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <?php echo Security::campoCsrf(); ?>
            <div class="form-group">
                <label>E-mail Corporativo</label>
                <input type="email" name="email" class="form-control" placeholder="seu@email.com" required>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn">Acessar</button>
        </form>
        <p style="text-align: center; margin-top: 1.5rem; font-size: 0.85rem; color: #94a3b8;">
            Ainda não tem conta? <a href="cadastro.php" style="color: #3b82f6; text-decoration: none; font-weight: 600;">Cadastre sua empresa</a>
        </p>
    </div>
</body>
</html>
