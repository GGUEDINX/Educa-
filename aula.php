<?php
// aula.php
require_once 'auth.php';
require_once __DIR__ . '/app/autoload.php';

use App\Controllers\AulaController;
use App\Core\Security;

$usuario_id = $_SESSION['usuario_id'] ?? null;
$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$aula_ativa_id = isset($_GET['aula_id']) ? (int)$_GET['aula_id'] : 0;

if (!$usuario_id || !$curso_id) {
    header("Location: cursos.php");
    exit();
}

$aulaController = new AulaController();

// ==========================================
// AÇÃO (AJAX): REGISTRAR PROGRESSO REAL DO VÍDEO
// Chamado periodicamente pelo player do YouTube via JavaScript.
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_progresso_video'])) {
    Security::validarCsrf();
    $aula_id_prog = (int)($_POST['aula_id'] ?? 0);
    $percentual = (float)($_POST['percentual'] ?? 0);
    $podeConcluir = $aulaController->registrarProgressoVideo($curso_id, $aula_id_prog, $percentual);

    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'pode_concluir' => $podeConcluir]);
    exit();
}

// ==========================================
// AÇÃO: CONCLUIR AULA (revalidado no servidor, não confia no botão)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['concluir_aula'])) {
    Security::validarCsrf();
    $aula_id_concluir = (int)($_POST['aula_id'] ?? 0);
    $ok = $aulaController->concluirAula($curso_id, $aula_id_concluir, (int)$usuario_id);

    if ($ok) {
        // Manda pra próxima aula automaticamente (a própria montarPagina() já resolve qual é)
        header("Location: aula.php?curso_id=$curso_id");
        exit();
    }
    // Se não deu certo (tentou burlar o tempo/vídeo, por exemplo), só recarrega a mesma aula
    header("Location: aula.php?curso_id=$curso_id&aula_id=$aula_id_concluir");
    exit();
}

require_once 'includes/header.php';

$pagina = $aulaController->montarPagina($curso_id, $aula_ativa_id, (int)$usuario_id);
if ($pagina['erro']) {
    die("Erro ao carregar trilha: " . htmlspecialchars($pagina['erro']));
}
$curso = $pagina['curso'];
$aulas = $pagina['aulas'];
$aula_atual = $pagina['aula_atual'];
$ids_assistidas = $pagina['ids_assistidas'];
$todas_assistidas = $pagina['todas_assistidas'];
$ja_assistida = $pagina['ja_assistida'] ?? false;
$pode_concluir = $pagina['pode_concluir'] ?? false;
$segundos_restantes = $pagina['segundos_restantes'] ?? 0;
$youtube_id = $pagina['youtube_id'] ?? null;
$progresso_video = $pagina['progresso_video'] ?? 0;
$csrf_token = \App\Core\Security::tokenCsrf();
?>

<div class="main-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="main-content" style="display: flex; gap: 2rem; flex-wrap: wrap;">
        <!-- Player de Vídeo -->
        <div style="flex: 2; min-width: 320px;">
            <a href="cursos.php" style="text-decoration: none; color: var(--text-gray); font-size: 0.9rem; margin-bottom: 1.5rem; display: block;">
                <i class="bi bi-arrow-left"></i> Voltar para Cursos
            </a>

            <?php if ($aula_atual): ?>
                <div style="position: relative; padding-bottom: 56.25%; height: 0; background-color: #000; border-radius: 12px; overflow: hidden; border: 1px solid var(--border-color);">
                    <?php if ($youtube_id && !$ja_assistida): ?>
                        <!-- Player oficial da API do YouTube: permite rastrear o progresso real de reprodução -->
                        <div id="youtube-player" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></div>
                    <?php else: ?>
                        <!-- Aula já concluída (ou vídeo fora do YouTube): iframe simples de exibição -->
                        <iframe style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
                                src="<?php echo htmlspecialchars($aula_atual['url_video'] ?? ''); ?>" allowfullscreen></iframe>
                    <?php endif; ?>
                </div>
                <h1 style="color: var(--text-white); margin: 1.5rem 0 0.5rem 0;"><?php echo htmlspecialchars($aula_atual['titulo']); ?></h1>
                <p style="color: var(--text-gray); margin-bottom: 1.5rem;"><?php echo htmlspecialchars($aula_atual['descricao'] ?? ''); ?></p>

                <!-- Botão de conclusão (com trava real de tempo mínimo + progresso do vídeo, controlada pelo servidor) -->
                <?php if ($ja_assistida): ?>
                    <div style="display: flex; align-items: center; gap: 0.6rem; padding: 1rem; background-color: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.3); border-radius: 8px; color: #10b981; font-weight: 600;">
                        <i class="bi bi-check-circle-fill"></i> Aula já concluída
                    </div>
                <?php else: ?>
                    <form action="aula.php?curso_id=<?php echo $curso_id; ?>" method="POST" id="form-concluir">
                        <?php echo Security::campoCsrf(); ?>
                        <input type="hidden" name="aula_id" value="<?php echo $aula_atual['id']; ?>">
                        <button type="submit" name="concluir_aula" id="btn-concluir" <?php echo $pode_concluir ? '' : 'disabled'; ?>
                                class="btn-personalizado" style="width: 100%; padding: 0.9rem; font-size: 0.95rem; <?php echo $pode_concluir ? '' : 'opacity: 0.5; cursor: not-allowed;'; ?>">
                            <span id="texto-btn-concluir">
                                <?php echo $pode_concluir ? '✓ Marcar aula como concluída' : 'Aguarde para concluir...'; ?>
                            </span>
                        </button>
                        <p id="texto-ajuda-conclusao" style="color: var(--text-gray); font-size: 0.8rem; text-align: center; margin-top: 0.6rem;">
                            <?php echo $youtube_id
                                ? "É necessário assistir pelo menos 85% do vídeo (e permanecer o tempo mínimo) antes de concluir."
                                : "É necessário permanecer na aula pelo tempo mínimo definido pela pedagogia antes de concluir."; ?>
                        </p>
                    </form>

                    <?php if ($youtube_id): ?>
                    <!-- API oficial do YouTube (gratuita) -->
                    <script src="https://www.youtube.com/iframe_api"></script>
                    <script>
                        (function () {
                            var aulaId = <?php echo (int)$aula_atual['id']; ?>;
                            var cursoId = <?php echo (int)$curso_id; ?>;
                            var csrfToken = <?php echo json_encode($csrf_token); ?>;
                            var podeConcluirServidor = <?php echo $pode_concluir ? 'true' : 'false'; ?>;
                            var maiorProgresso = <?php echo (float)$progresso_video; ?>;
                            var player, intervaloEnvio;

                            function atualizarBotao(podeConcluir) {
                                if (!podeConcluir) return;
                                var btn = document.getElementById('btn-concluir');
                                var texto = document.getElementById('texto-btn-concluir');
                                btn.disabled = false;
                                btn.style.opacity = '1';
                                btn.style.cursor = 'pointer';
                                texto.textContent = '✓ Marcar aula como concluída';
                            }

                            function enviarProgresso(percentual) {
                                var form = new FormData();
                                form.append('csrf_token', csrfToken);
                                form.append('registrar_progresso_video', '1');
                                form.append('aula_id', aulaId);
                                form.append('percentual', percentual);

                                fetch('aula.php?curso_id=' + cursoId, { method: 'POST', body: form })
                                    .then(function (r) { return r.json(); })
                                    .then(function (data) { atualizarBotao(data.pode_concluir); })
                                    .catch(function () {});
                            }

                            // Chamada pela API do YouTube quando o player estiver pronto
                            window.onYouTubeIframeAPIReady = function () {
                                player = new YT.Player('youtube-player', {
                                    videoId: <?php echo json_encode($youtube_id); ?>,
                                    playerVars: { rel: 0, modestbranding: 1 },
                                    events: {
                                        onStateChange: function (event) {
                                            if (event.data === YT.PlayerState.PLAYING) {
                                                if (intervaloEnvio) clearInterval(intervaloEnvio);
                                                intervaloEnvio = setInterval(verificarProgresso, 3000);
                                            } else {
                                                clearInterval(intervaloEnvio);
                                            }
                                        }
                                    }
                                });
                            };

                            function verificarProgresso() {
                                if (!player || typeof player.getDuration !== 'function') return;
                                var duracao = player.getDuration();
                                var atual = player.getCurrentTime();
                                if (!duracao) return;

                                var percentual = (atual / duracao) * 100;
                                if (percentual > maiorProgresso + 2) { // só envia quando avançar de verdade, evita flood
                                    maiorProgresso = percentual;
                                    enviarProgresso(percentual);
                                }
                            }
                        })();
                    </script>
                    <?php endif; ?>

                    <?php if (!$pode_concluir): ?>
                    <script>
                        (function () {
                            var restante = <?php echo (int)$segundos_restantes; ?>;
                            var ehYoutube = <?php echo $youtube_id ? 'true' : 'false'; ?>;
                            var btn = document.getElementById('btn-concluir');
                            var texto = document.getElementById('texto-btn-concluir');

                            var intervalo = setInterval(function () {
                                restante--;
                                if (restante <= 0) {
                                    clearInterval(intervalo);
                                    if (ehYoutube) {
                                        // Tempo mínimo cumprido, mas ainda falta assistir o vídeo de verdade
                                        texto.textContent = 'Assista o vídeo até o fim para concluir...';
                                    } else {
                                        btn.disabled = false;
                                        btn.style.opacity = '1';
                                        btn.style.cursor = 'pointer';
                                        texto.textContent = '✓ Marcar aula como concluída';
                                    }
                                } else {
                                    var min = Math.floor(restante / 60);
                                    var seg = restante % 60;
                                    texto.textContent = 'Aguarde ' + min + ':' + (seg < 10 ? '0' : '') + seg + ' para concluir...';
                                }
                            }, 1000);
                        })();
                    </script>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else: ?>
                <p style="color: var(--text-gray);">Nenhuma aula disponível.</p>
            <?php endif; ?>
        </div>

        <!-- Trilha Lateral -->
        <div style="flex: 1; min-width: 300px;">
            <div style="background-color: var(--bg-card); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
                <h3 style="color: var(--text-white); font-size: 1.1rem; margin-bottom: 1rem;">Conteúdo do Curso</h3>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($aulas as $index => $aula): ?>
                        <?php
                            $assistida = in_array($aula['id'], $ids_assistidas);
                            $travada = !empty($aula['travada']);
                        ?>
                        <?php if ($travada): ?>
                            <div style="padding: 0.8rem; border-radius: 6px; font-size: 0.9rem; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; color: var(--text-gray); opacity: 0.5;">
                               <span><i class="bi bi-lock-fill"></i> <?php echo ($index + 1) . ". " . htmlspecialchars($aula['titulo']); ?></span>
                            </div>
                        <?php else: ?>
                            <a href="aula.php?curso_id=<?php echo $curso_id; ?>&aula_id=<?php echo $aula['id']; ?>" 
                               style="padding: 0.8rem; border-radius: 6px; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;
                               color: <?php echo ($aula['id'] == $aula_atual['id']) ? 'var(--text-white)' : 'var(--text-gray)'; ?>;
                               background-color: <?php echo ($aula['id'] == $aula_atual['id']) ? 'rgba(255,255,255,0.05)' : 'transparent'; ?>;">
                               <span><i class="bi bi-play-circle"></i> <?php echo ($index + 1) . ". " . htmlspecialchars($aula['titulo']); ?></span>
                               <?php if ($assistida): ?>
                                    <i class="bi bi-check-circle-fill" style="color: #10b981;" title="Assistida"></i>
                               <?php endif; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 1.5rem 0;">
                
                <?php if ($todas_assistidas): ?>
                    <a href="quiz.php?curso_id=<?php echo $curso_id; ?>" class="btn-personalizado" style="display: block; text-align: center; padding: 0.8rem; text-decoration: none;">
                        Iniciar Quiz do Curso
                    </a>
                <?php else: ?>
                    <div style="text-align: center; padding: 0.8rem; border-radius: 8px; background-color: rgba(148,163,184,0.08); color: var(--text-gray); font-size: 0.85rem;">
                        <i class="bi bi-lock-fill"></i> Assista todas as aulas, em ordem, para liberar o quiz
                        <div style="margin-top: 0.4rem; font-weight: 700; color: var(--text-white);">
                            <?php echo count($ids_assistidas); ?> / <?php echo count($aulas); ?> aulas concluídas
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
