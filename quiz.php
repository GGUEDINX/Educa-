<?php
// quiz.php
require_once 'auth.php';
require_once __DIR__ . '/app/autoload.php';

use App\Controllers\QuizController;
use App\Core\Security;

$usuario_id = $_SESSION['usuario_id'] ?? null;
$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$mensagem = '';
$sucesso = false;

if (!$usuario_id || !$curso_id) {
    header("Location: cursos.php");
    exit();
}

$quizController = new QuizController();

if (!$quizController->podeAcessarQuiz((int)$usuario_id, $curso_id)) {
    header("Location: aula.php?curso_id=$curso_id");
    exit();
}

try {
    $curso = $quizController->buscarCurso($curso_id);
    $quiz_completo = $quizController->montarQuiz($curso_id);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::validarCsrf();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_quiz'])) {
        $respostas_usuario = $_POST['resposta'] ?? [];
        $resultado = $quizController->corrigirEEmitirCertificado(
            (int)$usuario_id, $curso_id, $curso, $quiz_completo, $respostas_usuario
        );
        $sucesso = $resultado['aprovado'];
        $mensagem = $resultado['mensagem'];
    }
} catch (PDOException $e) {
    die("Erro no processamento do Quiz: " . $e->getMessage());
}

require_once 'includes/header.php';
?>

<div class="main-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="main-content" style="max-width: 800px; margin: 0 auto;">
        
        <div style="margin-bottom: 2rem;">
            <a href="aula.php?curso_id=<?php echo $curso_id; ?>" style="text-decoration: none; color: var(--text-gray); font-size: 0.9rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;" onmouseover="this.style.color='var(--text-white)'" onmouseout="this.style.color='var(--text-gray)'">
                <i class="bi bi-arrow-left"></i> Voltar para a Trilha de Aulas
            </a>
            <h1 style="color: var(--text-white); font-size: 1.8rem; font-weight: 700; margin: 0;">Avaliação do Treinamento</h1>
            <p style="color: var(--text-gray); font-size: 0.95rem; margin-top: 0.3rem;">Curso: <?php echo htmlspecialchars($curso['titulo']); ?></p>
        </div>

        <!-- Alertas de Feedback -->
        <?php if (!empty($mensagem)): ?>
            <div style="padding: 1.2rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 600; font-size: 1rem; text-align: center;
                background-color: <?php echo $sucesso ? 'rgba(16, 185, 129, 0.15)' : 'rgba(59, 130, 246, 0.15)'; ?>;
                border: 1px solid <?php echo $sucesso ? '#10b981' : '#3b82f6'; ?>;
                color: <?php echo $sucesso ? '#10b981' : '#60a5fa'; ?>;">
                <?php echo htmlspecialchars($mensagem); ?>
                
                <?php if ($sucesso): ?>
                    <div style="margin-top: 1rem;">
                        <a href="certificados.php" class="btn-personalizado" style="padding: 0.5rem 1rem; font-size: 0.9rem; text-decoration: none; display: inline-block; border-radius: 4px;">
                            <i class="bi bi-patch-check"></i> Ir para Meus Certificados
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$sucesso && count($quiz_completo) > 0): ?>
            <!-- Formulário de Quiz -->
            <form action="quiz.php?curso_id=<?php echo $curso_id; ?>" method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <?php echo Security::campoCsrf(); ?>
                
                <?php foreach ($quiz_completo as $index => $item): ?>
                    <div style="background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem;">
                        <p style="margin: 0 0 1rem 0; color: var(--text-white); font-weight: 700; font-size: 1.05rem;">
                            Questão <?php echo $index + 1; ?>: <?php echo htmlspecialchars($item['pergunta']['pergunta']); ?>
                        </p>
                        
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php foreach ($item['alternativas'] as $alt): ?>
                                <label style="display: flex; align-items: flex-start; gap: 0.75rem; background-color: rgba(255, 255, 255, 0.02); padding: 0.8rem 1rem; border-radius: 6px; border: 1px solid var(--border-color); cursor: pointer; color: var(--text-gray); font-size: 0.95rem; transition: var(--transition);"
                                       onmouseover="this.style.borderColor='var(--primary)'; this.style.color='var(--text-white)';"
                                       onmouseout="if(!this.querySelector('input').checked) { this.style.borderColor='var(--border-color)'; this.style.color='var(--text-gray)'; }">
                                    <input type="radio" name="resposta[<?php echo $item['pergunta']['id']; ?>]" value="<?php echo $alt['id']; ?>" required style="margin-top: 0.2rem; accent-color: var(--primary);">
                                    <span><?php echo htmlspecialchars($alt['texto_alternativa']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <button type="submit" name="enviar_quiz" class="btn-personalizado" style="padding: 1rem; border-radius: 8px; font-weight: 700; font-size: 1rem; cursor: pointer; border: 0; width: 100%;">
                    Enviar Minhas Respostas
                </button>
            </form>
        <?php elseif (!$sucesso && count($quiz_completo) === 0): ?>
            <div style="background-color: var(--bg-card); padding: 3rem; border-radius: 12px; text-align: center; border: 1px solid var(--border-color);">
                <i class="bi bi-patch-question-fill" style="font-size: 3rem; color: var(--text-gray);"></i>
                <h3 style="color: var(--text-white); margin-top: 1rem;">Nenhuma pergunta cadastrada para este curso ainda.</h3>
            </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>