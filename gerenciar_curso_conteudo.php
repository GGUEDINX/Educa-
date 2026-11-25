<?php
// gerenciar_curso_conteudo.php
require_once 'auth.php';

if ($_SESSION['tipo_usuario'] !== 'pedagogia') {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/app/autoload.php';

use App\Controllers\ConteudoController;
use App\Core\Security;

$curso_id = (int)($_GET['curso_id'] ?? 0);
$mensagem = '';
$tipo_mensagem = '';
$conteudoController = new ConteudoController();

$curso = $conteudoController->buscarCurso($curso_id);
if (!$curso) {
    header("Location: gerenciar_cursos.php");
    exit();
}

// ==========================================
// AÇÕES: AULAS
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::validarCsrf();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_aula'])) {
    $aula_id_edicao = (int)($_POST['aula_id'] ?? 0);

    if ($aula_id_edicao > 0) {
        $resultado = $conteudoController->editarAula(
            $aula_id_edicao, trim($_POST['titulo_aula']), trim($_POST['url_video'] ?? ''), (int)($_POST['ordem'] ?? 0), (int)($_POST['duracao_minutos'] ?? 3)
        );
    } else {
        $resultado = $conteudoController->criarAula(
            $curso_id, trim($_POST['titulo_aula']), trim($_POST['url_video'] ?? ''), (int)($_POST['ordem'] ?? 0), (int)($_POST['duracao_minutos'] ?? 3)
        );
    }
    $mensagem = $resultado['mensagem'];
    $tipo_mensagem = $resultado['tipo'];
}
if (isset($_POST['excluir_aula'])) {
    $resultado = $conteudoController->excluirAula((int)$_POST['excluir_aula']);
    $mensagem = $resultado['mensagem'];
    $tipo_mensagem = $resultado['tipo'];
}

// ==========================================
// AÇÕES: QUIZ
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_pergunta'])) {
    $alternativas = $_POST['alternativas'] ?? [];
    $resultado = $conteudoController->criarPergunta(
        $curso_id, trim($_POST['pergunta']), $alternativas, (int)($_POST['correta'] ?? -1)
    );
    $mensagem = $resultado['mensagem'];
    $tipo_mensagem = $resultado['tipo'];
}
if (isset($_POST['excluir_pergunta'])) {
    $resultado = $conteudoController->excluirPergunta((int)$_POST['excluir_pergunta']);
    $mensagem = $resultado['mensagem'];
    $tipo_mensagem = $resultado['tipo'];
}

$aulas = $conteudoController->listarAulas($curso_id);
$quiz = $conteudoController->listarQuiz($curso_id);

$aula_em_edicao = null;
if (isset($_GET['editar_aula'])) {
    $aula_em_edicao = $conteudoController->buscarAula((int)$_GET['editar_aula']);
}

require_once 'includes/header.php';
?>

<div class="main-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="main-content">
        <a href="gerenciar_cursos.php" style="text-decoration: none; color: var(--text-gray); font-size: 0.9rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            <i class="bi bi-arrow-left"></i> Voltar para Cursos
        </a>
        <div style="margin-bottom: 2rem;">
            <h1 style="margin: 0; font-size: 1.8rem; font-weight: 700; color: var(--text-white);"><?php echo htmlspecialchars($curso['titulo']); ?></h1>
            <p style="margin: 0.3rem 0 0 0; color: var(--text-gray); font-size: 0.95rem;">Monte a trilha de aulas e o quiz de avaliação deste curso.</p>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div style="padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-weight: 500; font-size: 0.95rem;
                background-color: <?php echo $tipo_mensagem === 'success' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(59, 130, 246, 0.15)'; ?>;
                border: 1px solid <?php echo $tipo_mensagem === 'success' ? '#10b981' : '#3b82f6'; ?>;
                color: <?php echo $tipo_mensagem === 'success' ? '#10b981' : '#60a5fa'; ?>;">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <!-- ============================================== -->
        <!-- AULAS -->
        <!-- ============================================== -->
        <div class="stat-card" style="margin-bottom: 2.5rem;">
            <h2 style="font-size: 1.1rem; margin-top: 0; margin-bottom: 1.25rem; color: var(--text-white); font-weight: 600;">🎬 Aulas da Trilha</h2>

            <form action="gerenciar_curso_conteudo.php?curso_id=<?php echo $curso_id; ?>" method="POST" style="display: grid; grid-template-columns: 2fr 2fr 1fr 1fr auto; gap: 0.75rem; align-items: end; margin-bottom: 0.5rem;">
                <?php echo Security::campoCsrf(); ?>
                <?php if ($aula_em_edicao): ?>
                    <input type="hidden" name="aula_id" value="<?php echo $aula_em_edicao['id']; ?>">
                <?php endif; ?>
                <div>
                    <label style="display: block; font-size: 0.75rem; color: var(--text-gray); margin-bottom: 0.4rem; font-weight: 600;">Título da Aula</label>
                    <input type="text" name="titulo_aula" required placeholder="Ex: Introdução ao tema" value="<?php echo htmlspecialchars($aula_em_edicao['titulo'] ?? ''); ?>" style="width: 100%; padding: 0.6rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; color: var(--text-gray); margin-bottom: 0.4rem; font-weight: 600;">Link do Vídeo (embed)</label>
                    <input type="text" name="url_video" placeholder="https://www.youtube.com/embed/..." value="<?php echo htmlspecialchars($aula_em_edicao['url_video'] ?? ''); ?>" style="width: 100%; padding: 0.6rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; color: var(--text-gray); margin-bottom: 0.4rem; font-weight: 600;">Ordem</label>
                    <input type="number" name="ordem" value="<?php echo $aula_em_edicao['ordem'] ?? (count($aulas) + 1); ?>" style="width: 100%; padding: 0.6rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; color: var(--text-gray); margin-bottom: 0.4rem; font-weight: 600;">Tempo mín. (min)</label>
                    <input type="number" name="duracao_minutos" min="1" value="<?php echo $aula_em_edicao['duracao_estimada_minutos'] ?? 3; ?>" style="width: 100%; padding: 0.6rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); box-sizing: border-box;">
                </div>
                <button type="submit" name="salvar_aula" class="btn-personalizado" style="white-space: nowrap;"><?php echo $aula_em_edicao ? 'Salvar' : '+ Adicionar'; ?></button>
            </form>
            <p style="color: var(--text-gray); font-size: 0.78rem; margin: 0 0 1.5rem 0;">
                <i class="bi bi-info-circle"></i> O funcionário só consegue marcar a aula como concluída depois de permanecer nela pelo tempo mínimo definido — isso evita que ele pule o conteúdo sem assistir.
            </p>
            <?php if ($aula_em_edicao): ?>
                <p style="margin: -1rem 0 1.5rem 0;"><a href="gerenciar_curso_conteudo.php?curso_id=<?php echo $curso_id; ?>" style="color: var(--text-gray); text-decoration: none; font-size: 0.8rem;">Cancelar edição</a></p>
            <?php endif; ?>

            <?php if (empty($aulas)): ?>
                <p style="color: var(--text-gray); font-size: 0.9rem; margin: 0;">Nenhuma aula cadastrada ainda.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($aulas as $a): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; background-color: var(--bg-dark); border-radius: 6px; border: 1px solid var(--border-color);">
                            <span style="color: var(--text-white); font-size: 0.9rem;"><strong style="color: var(--text-gray);">#<?php echo (int)$a['ordem']; ?></strong> &nbsp; <?php echo htmlspecialchars($a['titulo']); ?> <span style="color: var(--text-gray); font-size: 0.8rem;">(<?php echo (int)($a['duracao_estimada_minutos'] ?? 3); ?> min mín.)</span></span>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <a href="gerenciar_curso_conteudo.php?curso_id=<?php echo $curso_id; ?>&editar_aula=<?php echo $a['id']; ?>" style="color: var(--text-gray); text-decoration: none; font-size: 0.85rem;"><i class="bi bi-pencil-fill"></i></a>
                                <form action="gerenciar_curso_conteudo.php?curso_id=<?php echo $curso_id; ?>" method="POST" style="display: inline;" onsubmit="return confirm('Remover esta aula?');">
                                    <?php echo Security::campoCsrf(); ?>
                                    <input type="hidden" name="excluir_aula" value="<?php echo $a['id']; ?>">
                                    <button type="submit" style="color: #60a5fa; background: none; border: none; text-decoration: none; font-size: 0.8rem; cursor: pointer; padding: 0;">Remover</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============================================== -->
        <!-- QUIZ -->
        <!-- ============================================== -->
        <div class="stat-card">
            <h2 style="font-size: 1.1rem; margin-top: 0; margin-bottom: 1.25rem; color: var(--text-white); font-weight: 600;">📝 Quiz de Avaliação</h2>
            <p style="color: var(--text-gray); font-size: 0.85rem; margin: 0 0 1.5rem 0;">O funcionário precisa acertar todas as perguntas para ser aprovado e receber o certificado.</p>

            <form action="gerenciar_curso_conteudo.php?curso_id=<?php echo $curso_id; ?>" method="POST" style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 2rem; padding: 1.25rem; background-color: var(--bg-dark); border-radius: 8px; border: 1px solid var(--border-color);">
                <?php echo Security::campoCsrf(); ?>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-gray); margin-bottom: 0.4rem; font-weight: 600;">Pergunta</label>
                    <input type="text" name="pergunta" required placeholder="Digite a pergunta..." style="width: 100%; padding: 0.65rem; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); box-sizing: border-box;">
                </div>
                <label style="display: block; font-size: 0.8rem; color: var(--text-gray); margin: 0.5rem 0 0.2rem 0; font-weight: 600;">Alternativas (marque a correta)</label>
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <input type="radio" name="correta" value="<?php echo $i; ?>" <?php echo $i === 0 ? 'required' : ''; ?> style="accent-color: #10b981;">
                        <input type="text" name="alternativas[]" placeholder="Alternativa <?php echo $i + 1; ?><?php echo $i < 2 ? ' (obrigatória)' : ' (opcional)'; ?>" <?php echo $i < 2 ? 'required' : ''; ?> style="flex: 1; padding: 0.6rem; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); box-sizing: border-box;">
                    </div>
                <?php endfor; ?>
                <div style="text-align: right; margin-top: 0.5rem;">
                    <button type="submit" name="criar_pergunta" class="btn-personalizado">+ Adicionar Pergunta</button>
                </div>
            </form>

            <?php if (empty($quiz)): ?>
                <p style="color: var(--text-gray); font-size: 0.9rem; margin: 0;">Nenhuma pergunta cadastrada ainda — sem quiz, os funcionários não conseguem concluir o curso.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($quiz as $item): ?>
                        <div style="padding: 1rem; background-color: var(--bg-dark); border-radius: 8px; border: 1px solid var(--border-color);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <p style="color: var(--text-white); font-weight: 700; margin: 0; font-size: 0.9rem;"><?php echo htmlspecialchars($item['pergunta']['pergunta']); ?></p>
                                <form action="gerenciar_curso_conteudo.php?curso_id=<?php echo $curso_id; ?>" method="POST" style="display: inline; margin-left: 1rem;" onsubmit="return confirm('Remover esta pergunta?');">
                                    <?php echo Security::campoCsrf(); ?>
                                    <input type="hidden" name="excluir_pergunta" value="<?php echo $item['pergunta']['id']; ?>">
                                    <button type="submit" style="color: #60a5fa; background: none; border: none; text-decoration: none; font-size: 0.8rem; white-space: nowrap; cursor: pointer; padding: 0;">Remover</button>
                                </form>
                            </div>
                            <ul style="margin: 0; padding-left: 1.2rem; color: var(--text-gray); font-size: 0.85rem;">
                                <?php foreach ($item['alternativas'] as $alt): ?>
                                    <li style="<?php echo $alt['e_correta'] ? 'color: #10b981; font-weight: 600;' : ''; ?>">
                                        <?php echo htmlspecialchars($alt['texto_alternativa']); ?>
                                        <?php echo $alt['e_correta'] ? ' ✓' : ''; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

</body>
</html>
