<?php
// dashboard_funcionario.php
require_once 'auth.php';

// Se for admin, manda para a dashboard de empresa
if ($_SESSION['tipo_usuario'] === 'admin_empresa') {
    header("Location: dashboard_empresa.php");
    exit();
}
// Se for pedagogia, manda para o painel pedagógico
if ($_SESSION['tipo_usuario'] === 'pedagogia') {
    header("Location: dashboard_pedagogico.php");
    exit();
}

require_once __DIR__ . '/app/autoload.php';
require_once 'includes/header.php';

use App\Controllers\CursoController;

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Colaborador';
$empresa_nome = $_SESSION['empresa_nome'] ?? 'Sua Empresa';
$usuario_id = $_SESSION['usuario_id'] ?? null;

$cursos_recentes = $usuario_id ? (new CursoController())->recentesParaUsuario((int)$usuario_id) : [];
?>
<div class="main-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div style="background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%); padding: 2.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <h1 style="margin: 0; font-size: 2rem; color: var(--text-white);">Olá, <?php echo htmlspecialchars($usuario_nome); ?>! 👋</h1>
            <p style="margin: 0.5rem 0 0 0; opacity: 0.9; color: var(--text-white);">Pronto para iniciar os treinamentos disponibilizados pela <strong><?php echo htmlspecialchars($empresa_nome); ?></strong>?</p>
        </div>

        <?php if (empty($cursos_recentes)): ?>
            <div class="stat-card" style="text-align: center;">
                <p style="color: var(--text-gray); margin: 0;">Fique atento! Novos módulos de treinamento estarão disponíveis em breve.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h2 style="color: var(--text-white); font-size: 1.2rem; margin: 0;">🆕 Adicionados Recentemente</h2>
                <a href="cursos.php" style="color: var(--primary); text-decoration: none; font-size: 0.85rem; font-weight: 600;">Ver todos os cursos →</a>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <?php foreach ($cursos_recentes as $c): ?>
                    <div class="stat-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                            <span style="background: rgba(59,130,246,0.1); color: var(--primary); font-size: 0.7rem; font-weight: 700; padding: 0.25rem 0.7rem; border-radius: 20px; text-transform: uppercase;"><?php echo htmlspecialchars($c['categoria']); ?></span>
                            <?php if ($c['concluido']): ?>
                                <span style="background: rgba(16,185,129,0.1); color: #10b981; font-size: 0.7rem; font-weight: 700; padding: 0.25rem 0.7rem; border-radius: 20px;">Concluído</span>
                            <?php endif; ?>
                        </div>
                        <h3 style="color: var(--text-white); margin: 0 0 0.5rem 0; font-size: 1.05rem; font-weight: 700;"><?php echo htmlspecialchars($c['titulo']); ?></h3>
                        <p style="color: var(--text-gray); font-size: 0.85rem; margin: 0 0 1.25rem 0; line-height: 1.4;">
                            <?php echo htmlspecialchars(mb_strimwidth($c['descricao'] ?? '', 0, 100, '...')); ?>
                        </p>
                        <a href="<?php echo $c['concluido'] ? 'certificados.php' : 'aula.php?curso_id=' . $c['id']; ?>"
                           style="display: block; text-align: center; background-color: var(--btn-color, var(--primary)); color: var(--text-white); text-decoration: none; padding: 0.6rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem;">
                            <?php echo $c['concluido'] ? 'Ver Certificado' : 'Começar Agora'; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>