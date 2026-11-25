<?php
// dashboard_pedagogico.php
require_once 'auth.php';

// Proteção: apenas o perfil Pedagogia acessa este painel
if ($_SESSION['tipo_usuario'] !== 'pedagogia') {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/app/autoload.php';

use App\Controllers\ConteudoController;
use App\Models\DuvidaCurso;

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Pedagogia';
$empresa_id = (int)($_SESSION['empresa_id'] ?? 0);

$cursos = (new ConteudoController())->listarCursos();
$total_cursos = count($cursos);
$total_aulas = array_sum(array_column($cursos, 'total_aulas'));

try {
    $duvidas_pendentes = (new DuvidaCurso())->contarPendentes($empresa_id);
} catch (PDOException $e) {
    $duvidas_pendentes = 0;
}

require_once 'includes/header.php';
?>

<div class="main-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="main-content">
        <section class="banner-admin">
            <h1 style="margin: 0; font-size: 2rem; font-weight: 800; color: var(--text-white);">
                Painel Pedagógico
            </h1>
            <p style="margin: 0.5rem 0 0 0; opacity: 0.9; color: var(--text-white); font-size: 1rem;">
                Olá, <strong><?php echo htmlspecialchars($usuario_nome); ?></strong>! Aqui você cria as trilhas de aprendizado e acompanha o suporte aos alunos.
            </p>
        </section>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin: 2rem 0;">

            <div class="stat-card">
                <h3 style="margin: 0 0 1rem 0; color: var(--text-gray); font-size: 0.85rem; text-transform: uppercase; font-weight: 700;">Cursos Ativos</h3>
                <p style="font-size: 2.5rem; font-weight: 800; margin: 0; color: var(--primary);"><?php echo $total_cursos; ?></p>
            </div>

            <div class="stat-card">
                <h3 style="margin: 0 0 1rem 0; color: var(--text-gray); font-size: 0.85rem; text-transform: uppercase; font-weight: 700;">Aulas Cadastradas</h3>
                <p style="font-size: 2.5rem; font-weight: 800; margin: 0; color: var(--primary);"><?php echo $total_aulas; ?></p>
            </div>

            <div class="stat-card">
                <h3 style="margin: 0 0 1rem 0; color: var(--text-gray); font-size: 0.85rem; text-transform: uppercase; font-weight: 700;">Dúvidas Pendentes</h3>
                <p style="font-size: 2.5rem; font-weight: 800; margin: 0 0 0.75rem 0; color: <?php echo $duvidas_pendentes > 0 ? '#60a5fa' : '#10b981'; ?>;"><?php echo $duvidas_pendentes; ?></p>
                <a href="suporte_pedagogico.php" style="color: var(--primary); text-decoration: none; font-size: 0.85rem; font-weight: 600;">Responder agora →</a>
            </div>

        </div>

        <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="color: var(--text-white); margin: 0 0 0.3rem 0; font-size: 1.2rem;">Biblioteca de Módulos</h2>
                <p style="color: var(--text-gray); margin: 0; font-size: 0.9rem;">Crie novos cursos, adicione aulas em vídeo e monte o quiz de avaliação.</p>
            </div>
            <a href="gerenciar_cursos.php" class="btn-personalizado" style="text-decoration: none; padding: 0.85rem 1.5rem; border-radius: 6px; font-weight: 700;">
                Gerenciar Conteúdo →
            </a>
        </div>

    </main>
</div>

</body>
</html>
