<?php
// planos.php
require_once 'auth.php';

// Só o admin da empresa escolhe/visualiza o plano
if ($_SESSION['tipo_usuario'] !== 'admin_empresa') {
    header("Location: dashboard_funcionario.php");
    exit();
}

require_once __DIR__ . '/app/autoload.php';

use App\Controllers\CadastroController;
use App\Core\Security;
use App\Models\Empresa;

$empresa_id = (int)($_SESSION['empresa_id'] ?? 0);
$cadastroController = new CadastroController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['escolher_plano'])) {
    Security::validarCsrf();
    $cadastroController->escolherPlano($empresa_id, $_POST['plano'] ?? 'basico');
    // escolherPlano já redireciona pro dashboard e encerra a execução
}

$empresaAtual = (new Empresa())->buscarPorId($empresa_id);
$plano_atual = $empresaAtual['plano'] ?? null;
$planos = CadastroController::PLANOS;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolha seu plano | educa+</title>
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #ffffff;
            min-height: 100vh;
            padding: 3rem 1.5rem;
            box-sizing: border-box;
        }
        .topo { text-align: center; max-width: 600px; margin: 0 auto 3rem auto; }
        .planos { display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap; max-width: 1100px; margin: 0 auto; }
        .plano {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 2rem;
            width: 300px;
            display: flex;
            flex-direction: column;
        }
        .plano.destaque { border: 2px solid #3b82f6; position: relative; }
        .selo {
            position: absolute; top: -14px; left: 50%; transform: translateX(-50%);
            background: #3b82f6; color: #fff; font-size: 0.75rem; font-weight: 700;
            padding: 0.3rem 1rem; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .preco { font-size: 2.2rem; font-weight: 800; margin: 0.5rem 0 1.5rem 0; }
        .preco span { font-size: 1rem; font-weight: 400; color: #94a3b8; }
        ul { list-style: none; padding: 0; margin: 0 0 2rem 0; flex: 1; }
        li { padding: 0.5rem 0; color: #cbd5e1; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; }
        li::before { content: "✓"; color: #10b981; font-weight: 700; }
        .btn { width: 100%; padding: 0.8rem; background: #3b82f6; border: none; color: white; font-weight: 700; border-radius: 6px; cursor: pointer; }
        .btn:hover { background: #2563eb; }
        .plano:not(.destaque) .btn { background: transparent; border: 1px solid #3b82f6; color: #3b82f6; }
        .plano:not(.destaque) .btn:hover { background: rgba(59,130,246,0.1); }
    </style>
</head>
<body>
    <div class="topo">
        <h2 style="font-weight: 800; margin-bottom: 0.5rem;">educa<span style="color: #3b82f6;">+</span></h2>
        <h1 style="font-size: 1.6rem; margin: 0 0 0.5rem 0;">Escolha o plano da sua empresa</h1>
        <p style="color: #94a3b8; margin: 0;">Você pode mudar de plano quando quiser. Nenhum pagamento é necessário agora.</p>
    </div>

    <div class="planos">
        <?php foreach ($planos as $chave => $p): ?>
            <div class="plano <?php echo $p['destaque'] ? 'destaque' : ''; ?>">
                <?php if ($p['destaque']): ?><span class="selo">Mais popular</span><?php endif; ?>
                <h3 style="margin: 0; font-size: 1.1rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo htmlspecialchars($p['nome']); ?></h3>
                <p class="preco"><?php echo htmlspecialchars($p['preco']); ?> <span><?php echo htmlspecialchars($p['periodo']); ?></span></p>
                <ul>
                    <?php foreach ($p['recursos'] as $recurso): ?>
                        <li><?php echo htmlspecialchars($recurso); ?></li>
                    <?php endforeach; ?>
                </ul>
                <form action="planos.php" method="POST">
                    <?php echo Security::campoCsrf(); ?>
                    <input type="hidden" name="plano" value="<?php echo htmlspecialchars($chave); ?>">
                    <?php if ($plano_atual === $chave): ?>
                        <button type="button" class="btn" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid #10b981; cursor: default;" disabled>
                            ✓ Plano atual
                        </button>
                    <?php else: ?>
                        <button type="submit" name="escolher_plano" class="btn">
                            Escolher <?php echo htmlspecialchars($p['nome']); ?>
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
