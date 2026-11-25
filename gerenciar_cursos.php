<?php
// gerenciar_cursos.php
require_once 'auth.php';

if ($_SESSION['tipo_usuario'] !== 'pedagogia') {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/app/autoload.php';

use App\Controllers\ConteudoController;
use App\Core\Security;

$mensagem = '';
$tipo_mensagem = '';
$conteudoController = new ConteudoController();

// ==========================================
// AÇÃO: CRIAR OU EDITAR CURSO
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::validarCsrf();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_curso'])) {
    $curso_id_edicao = (int)($_POST['curso_id'] ?? 0);

    if ($curso_id_edicao > 0) {
        $resultado = $conteudoController->editarCurso(
            $curso_id_edicao,
            trim($_POST['titulo']),
            trim($_POST['descricao'] ?? ''),
            (int)($_POST['carga_horaria'] ?? 0),
            trim($_POST['categoria'] ?? ''),
            isset($_POST['obrigatorio']),
            (int)($_POST['pontos_recompensa'] ?? 10)
        );
    } else {
        $resultado = $conteudoController->criarCurso(
            trim($_POST['titulo']),
            trim($_POST['descricao'] ?? ''),
            (int)($_POST['carga_horaria'] ?? 0),
            trim($_POST['categoria'] ?? ''),
            isset($_POST['obrigatorio']),
            (int)($_POST['pontos_recompensa'] ?? 10)
        );
    }
    $mensagem = $resultado['mensagem'];
    $tipo_mensagem = $resultado['tipo'];
}

// ==========================================
// AÇÃO: EXCLUIR CURSO
// ==========================================
if (isset($_POST['excluir'])) {
    $resultado = $conteudoController->excluirCurso((int)$_POST['excluir']);
    $mensagem = $resultado['mensagem'];
    $tipo_mensagem = $resultado['tipo'];
}

// Curso sendo editado no momento (se houver)
$curso_em_edicao = null;
if (isset($_GET['editar'])) {
    $curso_em_edicao = $conteudoController->buscarCurso((int)$_GET['editar']);
}

// ==========================================
// BUSCA E PAGINAÇÃO
// ==========================================
$busca = trim($_GET['busca'] ?? '');
$pagina_atual_num = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 6;

$todos_cursos = $conteudoController->listarCursos();

if ($busca !== '') {
    $todos_cursos = array_values(array_filter($todos_cursos, function ($c) use ($busca) {
        return mb_stripos($c['titulo'], $busca) !== false || mb_stripos($c['categoria'], $busca) !== false;
    }));
}

$total_cursos_encontrados = count($todos_cursos);
$total_paginas = max(1, (int)ceil($total_cursos_encontrados / $por_pagina));
$pagina_atual_num = min($pagina_atual_num, $total_paginas);
$cursos = array_slice($todos_cursos, ($pagina_atual_num - 1) * $por_pagina, $por_pagina);

require_once 'includes/header.php';
?>

<div class="main-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div style="margin-bottom: 2rem;">
            <h1 style="margin: 0; font-size: 1.8rem; font-weight: 700; color: var(--text-white);">Biblioteca de Módulos</h1>
            <p style="margin: 0.3rem 0 0 0; color: var(--text-gray); font-size: 0.95rem;">
                Crie os cursos que vão compor as trilhas de aprendizado da plataforma.
            </p>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div style="padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-weight: 500; font-size: 0.95rem;
                background-color: <?php echo $tipo_mensagem === 'success' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(59, 130, 246, 0.15)'; ?>;
                border: 1px solid <?php echo $tipo_mensagem === 'success' ? '#10b981' : '#3b82f6'; ?>;
                color: <?php echo $tipo_mensagem === 'success' ? '#10b981' : '#60a5fa'; ?>;">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <!-- Formulário: Criar / Editar Curso -->
        <div class="stat-card" style="margin-bottom: 2.5rem;">
            <h2 style="font-size: 1.2rem; margin-top: 0; margin-bottom: 1.5rem; color: var(--text-white); font-weight: 600;">
                <?php echo $curso_em_edicao ? 'Editar Curso: ' . htmlspecialchars($curso_em_edicao['titulo']) : 'Novo Curso'; ?>
            </h2>
            <form action="gerenciar_cursos.php" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php echo Security::campoCsrf(); ?>
                    <?php if ($curso_em_edicao): ?>
                        <input type="hidden" name="curso_id" value="<?php echo $curso_em_edicao['id']; ?>">
                    <?php endif; ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.5rem; font-weight: 600;">Título do Curso</label>
                        <input type="text" name="titulo" required placeholder="Ex: Excel Avançado" value="<?php echo htmlspecialchars($curso_em_edicao['titulo'] ?? ''); ?>" style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.5rem; font-weight: 600;">Categoria</label>
                        <input type="text" name="categoria" placeholder="Ex: Tecnologia, Gestão, Soft Skills" value="<?php echo htmlspecialchars($curso_em_edicao['categoria'] ?? ''); ?>" style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.5rem; font-weight: 600;">Carga Horária (horas)</label>
                        <input type="number" name="carga_horaria" min="1" required placeholder="Ex: 20" value="<?php echo htmlspecialchars($curso_em_edicao['carga_horaria'] ?? ''); ?>" style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.5rem; font-weight: 600;">Pontos ao concluir</label>
                        <input type="number" name="pontos_recompensa" min="0" required placeholder="Ex: 10" value="<?php echo htmlspecialchars((string)($curso_em_edicao['pontos_recompensa'] ?? 10)); ?>" style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); box-sizing: border-box;">
                    </div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.5rem; font-weight: 600;">Descrição</label>
                    <textarea name="descricao" rows="2" placeholder="Do que se trata este curso?" style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); box-sizing: border-box; resize: vertical; font-family: inherit;"><?php echo htmlspecialchars($curso_em_edicao['descricao'] ?? ''); ?></textarea>
                </div>
                <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-gray); font-size: 0.9rem; cursor: pointer;">
                    <input type="checkbox" name="obrigatorio" <?php echo !empty($curso_em_edicao['obrigatorio']) ? 'checked' : ''; ?> style="accent-color: var(--primary);"> Marcar como curso obrigatório para todos os colaboradores
                </label>
                <div style="text-align: right; display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <?php if ($curso_em_edicao): ?>
                        <a href="gerenciar_cursos.php" style="align-self: center; color: var(--text-gray); text-decoration: none; font-size: 0.85rem;">Cancelar edição</a>
                    <?php endif; ?>
                    <button type="submit" name="salvar_curso" class="btn-personalizado"><?php echo $curso_em_edicao ? 'Salvar Alterações' : 'Criar Curso'; ?></button>
                </div>
            </form>
        </div>

        <!-- Busca -->
        <form action="gerenciar_cursos.php" method="GET" style="display: flex; gap: 0.75rem; margin-bottom: 1.5rem;">
            <input type="text" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Buscar por título ou categoria..." style="flex: 1; max-width: 350px; padding: 0.7rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white);">
            <button type="submit" style="padding: 0.7rem 1.2rem; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); cursor: pointer;"><i class="bi bi-search"></i> Buscar</button>
            <?php if ($busca !== ''): ?>
                <a href="gerenciar_cursos.php" style="align-self: center; color: var(--text-gray); text-decoration: none; font-size: 0.85rem;">Limpar</a>
            <?php endif; ?>
        </form>

        <!-- Lista de Cursos -->
        <h2 style="font-size: 1.2rem; margin-top: 0; margin-bottom: 1.5rem; color: var(--text-white); font-weight: 600;">
            Cursos Cadastrados <span style="color: var(--text-gray); font-weight: 400; font-size: 0.9rem;">(<?php echo $total_cursos_encontrados; ?>)</span>
        </h2>

        <?php if (empty($cursos)): ?>
            <p style="color: var(--text-gray); font-size: 0.95rem;"><?php echo $busca !== '' ? 'Nenhum curso encontrado para essa busca.' : 'Nenhum curso cadastrado ainda. Crie o primeiro acima!'; ?></p>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <?php foreach ($cursos as $c): ?>
                    <div class="stat-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem; gap: 0.5rem;">
                            <h3 style="margin: 0; color: var(--text-white); font-size: 1.05rem; font-weight: 700;"><?php echo htmlspecialchars($c['titulo']); ?></h3>
                            <div style="display: flex; gap: 0.6rem; flex-shrink: 0;">
                                <a href="gerenciar_cursos.php?editar=<?php echo $c['id']; ?>" title="Editar curso" style="color: var(--text-gray); text-decoration: none; font-size: 0.9rem;"><i class="bi bi-pencil-fill"></i></a>
                                <form action="gerenciar_cursos.php" method="POST" style="display: inline;" onsubmit="return confirm('Excluir este curso? Todas as aulas e perguntas do quiz dele também serão removidas.');">
                                    <?php echo Security::campoCsrf(); ?>
                                    <input type="hidden" name="excluir" value="<?php echo $c['id']; ?>">
                                    <button type="submit" title="Excluir curso" style="color: #60a5fa; background: none; border: none; text-decoration: none; font-size: 0.8rem; cursor: pointer; padding: 0;">❌</button>
                                </form>
                            </div>
                        </div>
                        <p style="color: var(--text-gray); font-size: 0.85rem; margin: 0 0 1rem 0; line-height: 1.4;">
                            <?php echo htmlspecialchars($c['descricao'] ?: 'Sem descrição.'); ?>
                        </p>
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1rem; font-size: 0.75rem;">
                            <span style="background: rgba(59,130,246,0.1); color: var(--primary); padding: 0.2rem 0.6rem; border-radius: 20px;"><?php echo htmlspecialchars($c['categoria']); ?></span>
                            <span style="background: rgba(148,163,184,0.1); color: var(--text-gray); padding: 0.2rem 0.6rem; border-radius: 20px;"><?php echo (int)$c['carga_horaria']; ?>h</span>
                            <span style="background: rgba(148,163,184,0.1); color: var(--text-gray); padding: 0.2rem 0.6rem; border-radius: 20px;"><?php echo $c['total_aulas']; ?> aula(s)</span>
                            <?php if (isset($c['pontos_recompensa'])): ?>
                                <span style="background: rgba(16,185,129,0.1); color: #10b981; padding: 0.2rem 0.6rem; border-radius: 20px;"><i class="bi bi-star-fill"></i> <?php echo (int)$c['pontos_recompensa']; ?> pts</span>
                            <?php endif; ?>
                            <?php if (!empty($c['obrigatorio'])): ?>
                                <span style="background: rgba(245,158,11,0.1); color: #f59e0b; padding: 0.2rem 0.6rem; border-radius: 20px;">Obrigatório</span>
                            <?php endif; ?>
                        </div>
                        <a href="gerenciar_curso_conteudo.php?curso_id=<?php echo $c['id']; ?>" style="display: block; text-align: center; background-color: var(--primary); color: var(--text-white); text-decoration: none; padding: 0.6rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem;">
                            Gerenciar Aulas e Quiz
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_paginas > 1): ?>
                <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
                    <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                        <a href="gerenciar_cursos.php?pagina=<?php echo $p; ?><?php echo $busca !== '' ? '&busca=' . urlencode($busca) : ''; ?>"
                           style="padding: 0.5rem 0.9rem; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600;
                           background-color: <?php echo $p === $pagina_atual_num ? 'var(--primary)' : 'var(--bg-card)'; ?>;
                           color: var(--text-white); border: 1px solid var(--border-color);"><?php echo $p; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </main>
</div>

</body>
</html>
