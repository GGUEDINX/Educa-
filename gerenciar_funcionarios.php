<?php
// gerenciar_funcionarios.php
require_once 'auth.php';

// Proteção: apenas administradores de empresas podem gerenciar a equipe
if ($_SESSION['tipo_usuario'] !== 'admin_empresa') {
    header("Location: dashboard_funcionario.php");
    exit();
}

require_once __DIR__ . '/app/autoload.php';

use App\Controllers\FuncionarioController;
use App\Core\Security;

$empresa_id = $_SESSION['empresa_id'] ?? null;
$mensagem = '';
$tipo_mensagem = '';

if (!$empresa_id) {
    die("Erro: Empresa não identificada na sessão.");
}

$funcionarioController = new FuncionarioController();

// ==========================================
// AÇÃO: CADASTRAR FUNCIONÁRIO
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::validarCsrf();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_funcionario'])) {
    $resultado = $funcionarioController->cadastrar(
        (int)$empresa_id,
        trim($_POST['nome']),
        trim($_POST['email']),
        trim($_POST['senha']) // A senha já é armazenada com hash (bcrypt)
    );
    $mensagem = $resultado['mensagem'];
    $tipo_mensagem = $resultado['tipo'];
}

// ==========================================
// AÇÃO: IMPORTAR FUNCIONÁRIOS EM LOTE
// ==========================================
$resultado_importacao = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['importar_funcionarios'])) {
    $conteudo = trim($_POST['lista_funcionarios'] ?? '');

    // Se um arquivo .csv/.txt foi enviado, ele tem prioridade sobre o texto colado
    if (!empty($_FILES['arquivo_funcionarios']['tmp_name']) && $_FILES['arquivo_funcionarios']['error'] === UPLOAD_ERR_OK) {
        $conteudo = file_get_contents($_FILES['arquivo_funcionarios']['tmp_name']);
    }

    if (empty($conteudo)) {
        $mensagem = 'Cole a lista de funcionários ou envie um arquivo.';
        $tipo_mensagem = 'error';
    } else {
        $resultado_importacao = $funcionarioController->importarEmLote((int)$empresa_id, $conteudo);
        $totalCriados = count($resultado_importacao['criados']);
        $mensagem = $totalCriados > 0
            ? "$totalCriados funcionário(s) importado(s) com sucesso!"
            : 'Nenhum funcionário novo foi importado. Confira os detalhes abaixo.';
        $tipo_mensagem = $totalCriados > 0 ? 'success' : 'error';
    }
}

// ==========================================
// AÇÃO: EXCLUIR FUNCIONÁRIO
// ==========================================
if (isset($_POST['excluir'])) {
    $resultado = $funcionarioController->excluir((int)$_POST['excluir'], (int)$empresa_id);
    $mensagem = $resultado['mensagem'];
    $tipo_mensagem = $resultado['tipo'];
}

// ==========================================
// AÇÃO: REDEFINIR SENHA DE UM COLABORADOR
// ==========================================
if (isset($_POST['resetar_senha'])) {
    $resultado = $funcionarioController->resetarSenha(
        (int)$_POST['usuario_id'], (int)$empresa_id, trim($_POST['nova_senha'] ?? '')
    );
    $mensagem = $resultado['mensagem'];
    $tipo_mensagem = $resultado['tipo'];
}

// ==========================================
// BUSCA, PAGINAÇÃO E LISTA DE FUNCIONÁRIOS
// ==========================================
$busca = trim($_GET['busca'] ?? '');
$pagina_atual_num = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 8;

$todos_funcionarios = $funcionarioController->listarPorEmpresa((int)$empresa_id);

if ($busca !== '') {
    $todos_funcionarios = array_values(array_filter($todos_funcionarios, function ($f) use ($busca) {
        return mb_stripos($f['nome'], $busca) !== false || mb_stripos($f['email'], $busca) !== false;
    }));
}

$total_encontrados = count($todos_funcionarios);
$total_paginas = max(1, (int)ceil($total_encontrados / $por_pagina));
$pagina_atual_num = min($pagina_atual_num, $total_paginas);
$funcionarios = array_slice($todos_funcionarios, ($pagina_atual_num - 1) * $por_pagina, $por_pagina);

require_once 'includes/header.php';
?>

<div class="main-container">

    <?php require_once 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Título da Seção -->
        <div style="margin-bottom: 2rem;">
            <h1 style="margin: 0; font-size: 1.8rem; font-weight: 700; color: var(--text-white);">Gerenciar Equipe</h1>
            <p style="margin: 0.3rem 0 0 0; color: var(--text-gray); font-size: 0.95rem;">
                Cadastre novos colaboradores e gerencie os acessos atuais da sua organização.
            </p>
        </div>

        <!-- Alertas de Feedback -->
        <?php if (!empty($mensagem)): ?>
            <div style="padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-weight: 500; font-size: 0.95rem; 
                background-color: <?php echo $tipo_mensagem === 'success' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(59, 130, 246, 0.15)'; ?>;
                border: 1px solid <?php echo $tipo_mensagem === 'success' ? '#10b981' : '#3b82f6'; ?>;
                color: <?php echo $tipo_mensagem === 'success' ? '#10b981' : '#60a5fa'; ?>;">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de Cadastro (Sem card fechado, estilo limpo) -->
        <div style="background-color: var(--bg-card); padding: 2rem; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 2.5rem;">
            <h2 style="font-size: 1.2rem; margin-top: 0; margin-bottom: 1.5rem; color: var(--text-white); font-weight: 600;">Cadastrar Novo Colaborador</h2>
            
            <form action="gerenciar_funcionarios.php" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: flex-end;">
                    <?php echo Security::campoCsrf(); ?>
                <div>
                    <label style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.5rem; font-weight: 600;">Nome Completo</label>
                    <input type="text" name="nome" required placeholder="Ex: João Silva" style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); outline: none;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.5rem; font-weight: 600;">E-mail Corporativo</label>
                    <input type="email" name="email" required placeholder="joao@empresa.com" style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); outline: none;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.5rem; font-weight: 600;">Senha Provisória</label>
                    <input type="password" name="senha" required placeholder="Mínimo 6 dígitos" style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); outline: none;">
                </div>
                <div>
                    <button type="submit" name="cadastrar_funcionario" style="width: 100%; padding: 0.75rem; background-color: var(--primary); color: var(--text-white); border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: var(--transition);">
                        Adicionar Integrante
                    </button>
                </div>
            </form>
        </div>

        <!-- Importação em Lote -->
        <div style="background-color: var(--bg-card); padding: 2rem; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 2.5rem;">
            <h2 style="font-size: 1.2rem; margin-top: 0; margin-bottom: 0.4rem; color: var(--text-white); font-weight: 600;">
                <i class="bi bi-upload"></i> Importar Vários Funcionários de Uma Vez
            </h2>
            <p style="color: var(--text-gray); font-size: 0.85rem; margin: 0 0 1.5rem 0;">
                Cole a lista abaixo (um funcionário por linha, no formato <strong>Nome, e-mail</strong>) ou envie um arquivo .csv com as mesmas colunas. Uma senha provisória é gerada automaticamente para cada um.
            </p>

            <form action="gerenciar_funcionarios.php" method="POST" enctype="multipart/form-data">
                <?php echo Security::campoCsrf(); ?>
                <textarea name="lista_funcionarios" rows="5" placeholder="João Silva, joao@empresa.com&#10;Maria Souza, maria@empresa.com&#10;Pedro Lima, pedro@empresa.com" style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); box-sizing: border-box; resize: vertical; font-family: monospace; font-size: 0.85rem; margin-bottom: 1rem;"></textarea>

                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; color: var(--text-gray); margin-bottom: 0.4rem;">Ou envie um arquivo .csv / .txt</label>
                        <input type="file" name="arquivo_funcionarios" accept=".csv,.txt" style="color: var(--text-gray); font-size: 0.85rem;">
                    </div>
                    <button type="submit" name="importar_funcionarios" style="padding: 0.75rem 1.5rem; background-color: var(--primary); color: var(--text-white); border: none; border-radius: 6px; font-weight: 600; cursor: pointer; white-space: nowrap;">
                        Importar Lista
                    </button>
                </div>
            </form>

            <?php if ($resultado_importacao): ?>
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">

                    <?php if (!empty($resultado_importacao['criados'])): ?>
                        <p style="color: #10b981; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.75rem;">
                            <i class="bi bi-check-circle-fill"></i> <?php echo count($resultado_importacao['criados']); ?> conta(s) criada(s) — anote as senhas abaixo, elas não aparecem de novo:
                        </p>
                        <div style="overflow-x: auto; margin-bottom: 1.5rem;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                                <thead>
                                    <tr style="text-align: left; color: var(--text-gray); border-bottom: 1px solid var(--border-color);">
                                        <th style="padding: 0.5rem;">Nome</th>
                                        <th style="padding: 0.5rem;">E-mail</th>
                                        <th style="padding: 0.5rem;">Senha Provisória</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resultado_importacao['criados'] as $c): ?>
                                        <tr style="border-bottom: 1px solid var(--border-color);">
                                            <td style="padding: 0.5rem; color: var(--text-white);"><?php echo htmlspecialchars($c['nome']); ?></td>
                                            <td style="padding: 0.5rem; color: var(--text-white);"><?php echo htmlspecialchars($c['email']); ?></td>
                                            <td style="padding: 0.5rem; color: #10b981; font-family: monospace; font-weight: 700;"><?php echo htmlspecialchars($c['senha']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($resultado_importacao['ja_existentes'])): ?>
                        <p style="color: #60a5fa; font-size: 0.85rem; margin-bottom: 0.4rem;">
                            <i class="bi bi-info-circle-fill"></i> <?php echo count($resultado_importacao['ja_existentes']); ?> e-mail(s) já cadastrado(s), foram ignorados:
                            <?php echo htmlspecialchars(implode(', ', array_column($resultado_importacao['ja_existentes'], 'email'))); ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($resultado_importacao['invalidas'])): ?>
                        <p style="color: #f59e0b; font-size: 0.85rem; margin-bottom: 0;">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo count($resultado_importacao['invalidas']); ?> linha(s) não reconhecida(s) (formato inválido):
                            <?php foreach ($resultado_importacao['invalidas'] as $inv): ?>
                                <br><span style="font-family: monospace;">Linha <?php echo $inv['linha']; ?>: "<?php echo htmlspecialchars($inv['conteudo']); ?>"</span>
                            <?php endforeach; ?>
                        </p>
                    <?php endif; ?>

                </div>
            <?php endif; ?>
        </div>

        <!-- Lista de Funcionários Cadastrados -->
        <div style="background-color: var(--bg-card); padding: 2rem; border-radius: 8px; border: 1px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.2rem; margin: 0; color: var(--text-white); font-weight: 600;">
                    Integrantes da Equipe <span style="color: var(--text-gray); font-weight: 400; font-size: 0.9rem;">(<?php echo $total_encontrados; ?>)</span>
                </h2>
                <form action="gerenciar_funcionarios.php" method="GET" style="display: flex; gap: 0.5rem;">
                    <input type="text" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Buscar por nome ou e-mail..." style="padding: 0.6rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); min-width: 220px;">
                    <button type="submit" style="padding: 0.6rem 1rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); cursor: pointer;"><i class="bi bi-search"></i></button>
                    <?php if ($busca !== ''): ?>
                        <a href="gerenciar_funcionarios.php" style="align-self: center; color: var(--text-gray); text-decoration: none; font-size: 0.8rem;">Limpar</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (count($funcionarios) === 0): ?>
                <p style="color: var(--text-gray); font-size: 0.95rem; margin: 0;">
                    <?php echo $busca !== '' ? 'Nenhum colaborador encontrado para essa busca.' : 'Sua empresa ainda não possui colaboradores cadastrados.'; ?>
                </p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 1rem 0.5rem; color: var(--text-gray); font-size: 0.85rem; text-transform: uppercase;">Colaborador</th>
                                <th style="padding: 1rem 0.5rem; color: var(--text-gray); font-size: 0.85rem; text-transform: uppercase;">Contato</th>
                                <th style="padding: 1rem 0.5rem; color: var(--text-gray); font-size: 0.85rem; text-transform: uppercase; text-align: right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($funcionarios as $func): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <td style="padding: 1rem 0.5rem; display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background-color: var(--bg-dark); display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                                            👤
                                        </div>
                                        <span style="font-weight: 500; color: var(--text-white);"><?php echo htmlspecialchars($func['nome']); ?></span>
                                    </td>
                                    <td style="padding: 1rem 0.5rem; color: var(--text-gray); font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($func['email']); ?>
                                    </td>
                                    <td style="padding: 1rem 0.5rem; text-align: right; white-space: nowrap;">
                                        <form action="gerenciar_funcionarios.php" method="POST" style="display: inline;"
                                              onsubmit="var novaSenha = prompt('Digite a nova senha para ' + <?php echo json_encode($func['nome']); ?> + ' (mínimo 6 caracteres):'); if (!novaSenha) return false; this.nova_senha.value = novaSenha; return confirm('Confirmar redefinição de senha?');">
                                            <?php echo Security::campoCsrf(); ?>
                                            <input type="hidden" name="usuario_id" value="<?php echo $func['id']; ?>">
                                            <input type="hidden" name="nova_senha" value="">
                                            <button type="submit" name="resetar_senha"
                                               style="color: var(--primary); background: none; text-decoration: none; font-size: 0.85rem; font-weight: 600; padding: 0.4rem 0.8rem; border-radius: 4px; border: 1px solid rgba(59, 130, 246, 0.2); cursor: pointer; margin-right: 0.4rem;">
                                                Redefinir Senha
                                            </button>
                                        </form>
                                        <form action="gerenciar_funcionarios.php" method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja remover este colaborador? Essa ação não pode ser desfeita.');">
                                            <?php echo Security::campoCsrf(); ?>
                                            <input type="hidden" name="excluir" value="<?php echo $func['id']; ?>">
                                            <button type="submit"
                                               style="color: #60a5fa; background: none; text-decoration: none; font-size: 0.85rem; font-weight: 600; padding: 0.4rem 0.8rem; border-radius: 4px; border: 1px solid rgba(59, 130, 246, 0.2); transition: var(--transition); cursor: pointer;"
                                               onmouseover="this.style.backgroundColor='rgba(59, 130, 246, 0.1)'"
                                               onmouseout="this.style.backgroundColor='transparent'">
                                                Remover
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_paginas > 1): ?>
                    <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 1.5rem;">
                        <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                            <a href="gerenciar_funcionarios.php?pagina=<?php echo $p; ?><?php echo $busca !== '' ? '&busca=' . urlencode($busca) : ''; ?>"
                               style="padding: 0.5rem 0.9rem; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600;
                               background-color: <?php echo $p === $pagina_atual_num ? 'var(--primary)' : 'var(--bg-dark)'; ?>;
                               color: var(--text-white); border: 1px solid var(--border-color);"><?php echo $p; ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

</body>
</html>