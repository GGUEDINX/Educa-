<?php
// suporte_pedagogico.php
require_once 'auth.php';
require_once __DIR__ . '/app/autoload.php';

use App\Controllers\SuportePedagogicoController;
use App\Core\Security;

$usuario_id = $_SESSION['usuario_id'] ?? null;
$empresa_id = $_SESSION['empresa_id'] ?? null;
$tipo_usuario = $_SESSION['tipo_usuario'] ?? 'funcionario';
$eh_equipe_suporte = in_array($tipo_usuario, ['admin_empresa', 'pedagogia'], true);
$mensagem = '';
$tipo_mensagem = '';

if (!$empresa_id) {
    die("Erro: Empresa não identificada na sessão.");
}

$suporteController = new SuportePedagogicoController();

// ==========================================
// AÇÃO: FUNCIONÁRIO ENVIA UMA DÚVIDA
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::validarCsrf();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_duvida'])) {
    $resultado = $suporteController->enviarDuvida(
        (int)$usuario_id, (int)($_POST['curso_id'] ?? 0), trim($_POST['pergunta'] ?? '')
    );
    $mensagem = $resultado['mensagem'];
    $tipo_mensagem = $resultado['tipo'];
}

// ==========================================
// AÇÃO: PEDAGOGIA RESPONDE UMA DÚVIDA
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responder_duvida']) && $eh_equipe_suporte) {
    $resultado = $suporteController->responderDuvida(
        (int)$_POST['duvida_id'], (int)$usuario_id, trim($_POST['resposta'] ?? '')
    );
    $mensagem = $resultado['mensagem'];
    $tipo_mensagem = $resultado['tipo'];
}

// ==========================================
// AÇÃO: FUNCIONÁRIO ENVIA FEEDBACK
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_feedback'])) {
    $resultado = $suporteController->enviarFeedback(
        (int)$usuario_id, (int)($_POST['curso_id_feedback'] ?? 0),
        (int)($_POST['nota'] ?? 0), trim($_POST['comentario'] ?? '')
    );
    $mensagem = $resultado['mensagem'];
    $tipo_mensagem = $resultado['tipo'];
}

$cursos = $suporteController->listarCursos();
$painel = $suporteController->montarPainel($tipo_usuario, (int)$usuario_id, (int)$empresa_id);
$duvidas = $painel['duvidas'];
$feedbacks = $painel['feedbacks'];
$medias_por_curso = $painel['medias_por_curso'];
$erro_tabela = $painel['erro_tabela'];

require_once 'includes/header.php';

function estrelas(int $nota): string
{
    return str_repeat('★', $nota) . str_repeat('☆', 5 - $nota);
}
?>

<div class="main-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div style="margin-bottom: 2rem;">
            <h1 style="margin: 0; font-size: 1.8rem; font-weight: 700; color: var(--text-white);">Suporte Pedagógico</h1>
            <p style="margin: 0.3rem 0 0 0; color: var(--text-gray); font-size: 0.95rem;">
                <?php echo $eh_equipe_suporte
                    ? 'Responda as dúvidas da equipe e acompanhe o feedback sobre os cursos.'
                    : 'Tire suas dúvidas sobre o conteúdo e avalie os treinamentos que você concluiu.'; ?>
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

        <?php if (!$eh_equipe_suporte): ?>
            <!-- ============================================== -->
            <!-- FUNCIONÁRIO: Enviar Dúvida + Deixar Feedback    -->
            <!-- ============================================== -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">

                <!-- Enviar Dúvida -->
                <div class="stat-card">
                    <h2 style="font-size: 1.1rem; margin-top: 0; margin-bottom: 1.25rem; color: var(--text-white); font-weight: 600;">💬 Tirar uma Dúvida</h2>
                    <form action="suporte_pedagogico.php" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php echo Security::campoCsrf(); ?>
                        <div>
                            <label style="display: block; font-size: 0.8rem; color: var(--text-gray); margin-bottom: 0.4rem; font-weight: 600;">Curso</label>
                            <select name="curso_id" required style="width: 100%; padding: 0.7rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white);">
                                <option value="">Selecione o curso...</option>
                                <?php foreach ($cursos as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['titulo']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; color: var(--text-gray); margin-bottom: 0.4rem; font-weight: 600;">Sua dúvida</label>
                            <textarea name="pergunta" required rows="3" placeholder="Escreva sua pergunta sobre o conteúdo do curso..." style="width: 100%; padding: 0.7rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); resize: vertical; box-sizing: border-box; font-family: inherit;"></textarea>
                        </div>
                        <button type="submit" name="enviar_duvida" class="btn-personalizado" style="align-self: flex-end;">Enviar Dúvida</button>
                    </form>
                </div>

                <!-- Deixar Feedback -->
                <div class="stat-card">
                    <h2 style="font-size: 1.1rem; margin-top: 0; margin-bottom: 1.25rem; color: var(--text-white); font-weight: 600;">⭐ Avaliar um Curso</h2>
                    <form action="suporte_pedagogico.php" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php echo Security::campoCsrf(); ?>
                        <div>
                            <label style="display: block; font-size: 0.8rem; color: var(--text-gray); margin-bottom: 0.4rem; font-weight: 600;">Curso</label>
                            <select name="curso_id_feedback" required style="width: 100%; padding: 0.7rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white);">
                                <option value="">Selecione o curso...</option>
                                <?php foreach ($cursos as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['titulo']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; color: var(--text-gray); margin-bottom: 0.4rem; font-weight: 600;">Nota</label>
                            <div style="display: flex; gap: 1.25rem;">
                                <?php for ($n = 1; $n <= 5; $n++): ?>
                                    <label style="display: flex; align-items: center; gap: 0.3rem; color: var(--text-gray); font-size: 0.9rem; cursor: pointer;">
                                        <input type="radio" name="nota" value="<?php echo $n; ?>" <?php echo $n == 5 ? 'checked' : ''; ?> required style="accent-color: var(--primary);"> <?php echo $n; ?>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; color: var(--text-gray); margin-bottom: 0.4rem; font-weight: 600;">Comentário (opcional)</label>
                            <textarea name="comentario" rows="2" placeholder="O que achou da didática deste curso?" style="width: 100%; padding: 0.7rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); resize: vertical; box-sizing: border-box; font-family: inherit;"></textarea>
                        </div>
                        <button type="submit" name="enviar_feedback" class="btn-personalizado" style="align-self: flex-end;">Enviar Feedback</button>
                    </form>
                </div>
            </div>

            <!-- Minhas Dúvidas -->
            <div class="stat-card">
                <h2 style="font-size: 1.1rem; margin-top: 0; margin-bottom: 1.25rem; color: var(--text-white); font-weight: 600;">Minhas Dúvidas</h2>
                <?php if (empty($duvidas)): ?>
                    <p style="color: var(--text-gray); font-size: 0.9rem; margin: 0;">Você ainda não enviou nenhuma dúvida.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($duvidas as $d): ?>
                            <div style="padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-dark);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="color: var(--primary); font-size: 0.8rem; font-weight: 700; text-transform: uppercase;"><?php echo htmlspecialchars($d['titulo_curso']); ?></span>
                                    <span style="font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 20px; text-transform: uppercase;
                                        background-color: <?php echo $d['status'] === 'respondida' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(59, 130, 246, 0.15)'; ?>;
                                        color: <?php echo $d['status'] === 'respondida' ? '#10b981' : '#60a5fa'; ?>;">
                                        <?php echo $d['status'] === 'respondida' ? 'Respondida' : 'Pendente'; ?>
                                    </span>
                                </div>
                                <p style="color: var(--text-white); margin: 0 0 0.5rem 0; font-size: 0.9rem;"><?php echo htmlspecialchars($d['pergunta']); ?></p>
                                <?php if (!empty($d['resposta'])): ?>
                                    <p style="color: var(--text-gray); margin: 0; font-size: 0.85rem; padding-left: 0.75rem; border-left: 2px solid var(--primary);">
                                        <?php echo htmlspecialchars($d['resposta']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- ============================================== -->
            <!-- ADMIN/PEDAGOGIA: Responder Dúvidas + Mural      -->
            <!-- ============================================== -->
            <div class="stat-card" style="margin-bottom: 2.5rem;">
                <h2 style="font-size: 1.1rem; margin-top: 0; margin-bottom: 1.25rem; color: var(--text-white); font-weight: 600;">💬 Dúvidas Acadêmicas</h2>
                <?php if (empty($duvidas)): ?>
                    <p style="color: var(--text-gray); font-size: 0.9rem; margin: 0;">Nenhuma dúvida enviada pela equipe ainda.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($duvidas as $d): ?>
                            <div style="padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-dark);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                                    <span style="color: var(--text-white); font-size: 0.9rem; font-weight: 700;">
                                        <?php echo htmlspecialchars($d['nome_aluno']); ?>
                                        <span style="color: var(--text-gray); font-weight: 400;"> — <?php echo htmlspecialchars($d['titulo_curso']); ?></span>
                                    </span>
                                    <span style="font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 20px; text-transform: uppercase;
                                        background-color: <?php echo $d['status'] === 'respondida' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(59, 130, 246, 0.15)'; ?>;
                                        color: <?php echo $d['status'] === 'respondida' ? '#10b981' : '#60a5fa'; ?>;">
                                        <?php echo $d['status'] === 'respondida' ? 'Respondida' : 'Pendente'; ?>
                                    </span>
                                </div>
                                <p style="color: var(--text-white); margin: 0 0 0.75rem 0; font-size: 0.9rem;"><?php echo htmlspecialchars($d['pergunta']); ?></p>

                                <?php if ($d['status'] === 'respondida'): ?>
                                    <p style="color: var(--text-gray); margin: 0; font-size: 0.85rem; padding-left: 0.75rem; border-left: 2px solid #10b981;">
                                        <strong style="color: #10b981;">Sua resposta:</strong> <?php echo htmlspecialchars($d['resposta']); ?>
                                    </p>
                                <?php else: ?>
                                    <form action="suporte_pedagogico.php" method="POST" style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                    <?php echo Security::campoCsrf(); ?>
                                        <input type="hidden" name="duvida_id" value="<?php echo $d['id']; ?>">
                                        <input type="text" name="resposta" required placeholder="Digite sua resposta..." style="flex: 1; padding: 0.6rem; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white);">
                                        <button type="submit" name="responder_duvida" class="btn-personalizado" style="white-space: nowrap;">Responder</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">

                <!-- Mural de Feedback -->
                <div class="stat-card">
                    <h2 style="font-size: 1.1rem; margin-top: 0; margin-bottom: 1.25rem; color: var(--text-white); font-weight: 600;">🗣️ Mural de Feedback</h2>
                    <?php if (empty($feedbacks)): ?>
                        <p style="color: var(--text-gray); font-size: 0.9rem; margin: 0;">Nenhum feedback recebido ainda.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem; max-height: 420px; overflow-y: auto;">
                            <?php foreach ($feedbacks as $f): ?>
                                <div style="padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-dark);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                        <span style="color: var(--text-white); font-size: 0.85rem; font-weight: 700;"><?php echo htmlspecialchars($f['nome_aluno']); ?></span>
                                        <span style="color: #f59e0b; font-size: 0.9rem;"><?php echo estrelas((int)$f['nota']); ?></span>
                                    </div>
                                    <p style="color: var(--primary); margin: 0 0 0.4rem 0; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;"><?php echo htmlspecialchars($f['titulo_curso']); ?></p>
                                    <?php if (!empty($f['comentario'])): ?>
                                        <p style="color: var(--text-gray); margin: 0; font-size: 0.85rem;"><?php echo htmlspecialchars($f['comentario']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Média por Curso -->
                <div class="stat-card">
                    <h2 style="font-size: 1.1rem; margin-top: 0; margin-bottom: 1.25rem; color: var(--text-white); font-weight: 600;">📊 Nota Média por Curso</h2>
                    <?php if (empty($medias_por_curso)): ?>
                        <p style="color: var(--text-gray); font-size: 0.9rem; margin: 0;">Ainda não há avaliações suficientes.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php foreach ($medias_por_curso as $m): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="color: var(--text-white); font-size: 0.9rem;"><?php echo htmlspecialchars($m['titulo']); ?></span>
                                    <span>
                                        <span style="color: #f59e0b; font-weight: 700;"><?php echo $m['media_nota']; ?> ★</span>
                                        <span style="color: var(--text-gray); font-size: 0.8rem;">(<?php echo $m['total_avaliacoes']; ?>)</span>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p style="color: var(--text-gray); font-size: 0.75rem; margin: 1rem 0 0 0;">Ordenado do curso com pior avaliação para o melhor — fique de olho nos primeiros da lista.</p>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>
