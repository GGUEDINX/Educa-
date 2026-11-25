<?php
// solicitacoes.php
require_once 'auth.php';
require_once __DIR__ . '/app/autoload.php';

use App\Controllers\SolicitacaoController;
use App\Core\Security;

$usuario_id = $_SESSION['usuario_id'] ?? null;
$empresa_id = $_SESSION['empresa_id'] ?? null;
$tipo_usuario = $_SESSION['tipo_usuario'] ?? 'funcionario';
$mensagem = '';
$tipo_mensagem = '';

if (!$empresa_id) {
    die("Erro: Empresa não identificada na sessão.");
}

$solicitacaoController = new SolicitacaoController();

// ==========================================
// AÇÃO: FUNCIONÁRIO ENVIA UMA NOVA SOLICITAÇÃO
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::validarCsrf();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar_curso'])) {
    $resultado = $solicitacaoController->enviar(
        (int)$usuario_id, (int)$empresa_id,
        trim($_POST['titulo_sugerido']), trim($_POST['descricao'] ?? '')
    );
    $mensagem = $resultado['mensagem'];
    $tipo_mensagem = $resultado['tipo'];
}

// ==========================================
// AÇÃO: EMPRESA APROVA OU RECUSA UMA SOLICITAÇÃO
// ==========================================
if ($tipo_usuario === 'admin_empresa' && isset($_GET['acao']) && isset($_GET['id'])) {
    $resultado = $solicitacaoController->responder((int)$_GET['id'], (int)$empresa_id, $_GET['acao']);
    if ($resultado['mensagem'] !== '') {
        $mensagem = $resultado['mensagem'];
        $tipo_mensagem = $resultado['tipo'];
    }
}

// ==========================================
// BUSCAR SOLICITAÇÕES
// ==========================================
$resultadoLista = $solicitacaoController->listar($tipo_usuario, (int)$usuario_id, (int)$empresa_id);
$solicitacoes = $resultadoLista['lista'];
$erro_tabela = $resultadoLista['erro_tabela'];

require_once 'includes/header.php';

$badges = [
    'pendente' => ['bg' => 'rgba(59, 130, 246, 0.15)', 'cor' => '#60a5fa', 'texto' => 'Pendente'],
    'aprovado' => ['bg' => 'rgba(16, 185, 129, 0.15)', 'cor' => '#10b981', 'texto' => 'Aprovado'],
    'recusado' => ['bg' => 'rgba(148, 163, 184, 0.15)', 'cor' => 'var(--text-gray)', 'texto' => 'Recusado'],
];
?>

<div class="main-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div style="margin-bottom: 2rem;">
            <h1 style="margin: 0; font-size: 1.8rem; font-weight: 700; color: var(--text-white);">Solicitações de Curso</h1>
            <p style="margin: 0.3rem 0 0 0; color: var(--text-gray); font-size: 0.95rem;">
                <?php echo $tipo_usuario === 'admin_empresa'
                    ? 'Veja e responda às sugestões de treinamento enviadas pela sua equipe.'
                    : 'Não encontrou o curso que precisa? Sugira aqui para a sua empresa.'; ?>
            </p>
        </div>

        <?php if ($erro_tabela): ?>
            <div style="padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; background-color: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; color: #60a5fa;">
                Este recurso ainda não foi ativado no banco de dados. Rode o arquivo <strong>db/migracao_dashboard.sql</strong> para habilitá-lo.
            </div>
        <?php endif; ?>

        <?php if (!empty($mensagem)): ?>
            <div style="padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-weight: 500; font-size: 0.95rem;
                background-color: <?php echo $tipo_mensagem === 'success' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(59, 130, 246, 0.15)'; ?>;
                border: 1px solid <?php echo $tipo_mensagem === 'success' ? '#10b981' : '#3b82f6'; ?>;
                color: <?php echo $tipo_mensagem === 'success' ? '#10b981' : '#60a5fa'; ?>;">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <?php if ($tipo_usuario !== 'admin_empresa'): ?>
            <!-- Formulário de sugestão (Funcionário) -->
            <div class="stat-card" style="margin-bottom: 2.5rem;">
                <h2 style="font-size: 1.2rem; margin-top: 0; margin-bottom: 1.5rem; color: var(--text-white); font-weight: 600;">Sugerir Novo Curso</h2>
                <form action="solicitacoes.php" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php echo Security::campoCsrf(); ?>
                    <div>
                        <label style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.5rem; font-weight: 600;">Nome do Curso</label>
                        <input type="text" name="titulo_sugerido" required placeholder="Ex: Excel Avançado" style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); outline: none; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.5rem; font-weight: 600;">Por que esse curso seria útil? (Opcional)</label>
                        <input type="text" name="descricao" placeholder="Ex: Vai ajudar no dia a dia com relatórios." style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); outline: none; box-sizing: border-box;">
                    </div>
                    <div style="text-align: right;">
                        <button type="submit" name="solicitar_curso" class="btn-personalizado">Enviar Sugestão</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Lista de Solicitações -->
        <div class="stat-card">
            <h2 style="font-size: 1.2rem; margin-top: 0; margin-bottom: 1.5rem; color: var(--text-white); font-weight: 600;">
                <?php echo $tipo_usuario === 'admin_empresa' ? 'Sugestões da Equipe' : 'Minhas Sugestões'; ?>
            </h2>

            <?php if (count($solicitacoes) === 0): ?>
                <p style="color: var(--text-gray); font-size: 0.95rem; margin: 0;">Nenhuma solicitação por aqui ainda.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($solicitacoes as $s): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-dark); flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 200px;">
                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.3rem;">
                                    <span style="color: var(--text-white); font-weight: 700;"><?php echo htmlspecialchars($s['titulo_sugerido']); ?></span>
                                    <span style="background-color: <?php echo $badges[$s['status']]['bg']; ?>; color: <?php echo $badges[$s['status']]['cor']; ?>; font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 20px; text-transform: uppercase;">
                                        <?php echo $badges[$s['status']]['texto']; ?>
                                    </span>
                                </div>
                                <?php if ($tipo_usuario === 'admin_empresa'): ?>
                                    <p style="margin: 0; color: var(--text-gray); font-size: 0.85rem;">Sugerido por <?php echo htmlspecialchars($s['nome_funcionario']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($s['descricao'])): ?>
                                    <p style="margin: 0.3rem 0 0 0; color: var(--text-gray); font-size: 0.85rem;"><?php echo htmlspecialchars($s['descricao']); ?></p>
                                <?php endif; ?>
                            </div>

                            <?php if ($tipo_usuario === 'admin_empresa' && $s['status'] === 'pendente'): ?>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="solicitacoes.php?acao=aprovar&id=<?php echo $s['id']; ?>" style="text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Aprovar</a>
                                    <a href="solicitacoes.php?acao=recusar&id=<?php echo $s['id']; ?>" style="text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; background-color: rgba(59, 130, 246, 0.1); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">Recusar</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
