<?php
// configuracoes.php
require_once 'auth.php';
require_once __DIR__ . '/app/autoload.php';

use App\Controllers\ConfiguracaoController;
use App\Core\Security;

$usuario_id = $_SESSION['usuario_id'] ?? null;
$empresa_id = $_SESSION['empresa_id'] ?? null;
$tipo_usuario = $_SESSION['tipo_usuario'] ?? 'funcionario';

if (!$usuario_id) {
    header("Location: login.php");
    exit();
}

$mensagem = '';
$tipo_mensagem = '';
$configController = new ConfiguracaoController();

// ==========================================
// AÇÃO: ATUALIZAR PERFIL E AVATAR
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::validarCsrf();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_perfil'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha_nova = trim($_POST['senha_nova'] ?? '');
    $cor_primaria = $_POST['cor_primaria'] ?? '#3b82f6';
    $cor_botoes = $_POST['cor_botoes'] ?? '#3b82f6';
    $tema = $_POST['tema'] ?? 'escuro';
    $avatar_nome = $_SESSION['usuario_avatar'] ?? 'default.png';

    // Processa o upload da nova foto (se houver)
    $avatar_nome = $configController->processarUploadAvatar((int)$usuario_id, $avatar_nome, $_FILES['avatar'] ?? null);

    $resultado = $configController->atualizarPerfil(
        (int)$usuario_id, $nome, $email, $senha_nova, $avatar_nome, $cor_primaria, $cor_botoes, $tema
    );
    $mensagem = $resultado['mensagem'];
    $tipo_mensagem = $resultado['tipo'];

    if ($tipo_mensagem === 'success') {
        $_SESSION['usuario_nome'] = $nome;
        $_SESSION['usuario_avatar'] = $avatar_nome;
        $_SESSION['cor_destaque'] = $resultado['cor_primaria'];
        $_SESSION['cor_botoes'] = $resultado['cor_botoes'];
        $_SESSION['tema'] = $resultado['tema'];
    }
}

// ==========================================
// BUSCAR DADOS DO USUÁRIO
// ==========================================
$user_data = $configController->buscarDadosDoUsuario((int)$usuario_id);

require_once 'includes/header.php';
?>

<style>
    /* Estilo limpo e espaçoso (as cores vêm das variáveis globais definidas no header.php) */
    .form-label { display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.6rem; font-weight: 600; text-transform: uppercase; }
    .form-input { width: 100%; padding: 0.85rem; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-white); outline: none; transition: 0.2s; }
    .form-input:focus { border-color: var(--primary); }
    .color-input { width: 100%; height: 48px; padding: 0.3rem; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; }

    .tema-opcao { flex: 1; text-align: center; padding: 1.25rem; border-radius: 8px; border: 2px solid var(--border-color); cursor: pointer; transition: 0.2s; }
    .tema-opcao:has(input:checked) { border-color: var(--primary); background-color: rgba(59, 130, 246, 0.08); }
    .tema-opcao input { display: none; }
</style>

<!-- CONTAINER PRINCIPAL FLEX: Isso impede que a sidebar empurre o conteúdo -->
<div class="main-container" style="display: flex; min-height: 100vh; background-color: var(--bg-dark);">
    
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="main-content" style="flex: 1; padding: 2.5rem; max-width: 1200px;">
        
        <div style="margin-bottom: 2rem;">
            <h1 style="color: var(--text-white); margin: 0 0 0.5rem 0; font-size: 1.8rem;">Configurações de Perfil</h1>
            <p style="color: var(--text-gray); margin: 0;">Gerencie suas informações pessoais e credenciais de acesso.</p>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div style="padding: 1rem; border-radius: 8px; margin-bottom: 2rem; background-color: <?php echo $tipo_mensagem === 'success' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(59, 130, 246, 0.1)'; ?>; border: 1px solid <?php echo $tipo_mensagem === 'success' ? '#10b981' : '#3b82f6'; ?>; color: <?php echo $tipo_mensagem === 'success' ? '#10b981' : '#60a5fa'; ?>;">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <!-- Formulário Espaçoso (Sem Cards apertados) -->
        <form action="configuracoes.php" method="POST" enctype="multipart/form-data" style="max-width: 800px;">
                    <?php echo Security::campoCsrf(); ?>
            
            <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2.5rem;">
                <div style="width: 90px; height: 90px; border-radius: 50%; background: var(--bg-card); display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px solid var(--primary);">
                    <?php 
                    $avatar = $user_data['foto_avatar'] ?? 'default.png';
                    $avatar_path = 'uploads/avatars/' . $avatar;
                    if ($avatar !== 'default.png' && file_exists($avatar_path)): 
                    ?>
                        <img src="<?php echo $avatar_path; ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <span style="font-size: 2.5rem;">👤</span>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="form-label">Nova Foto de Perfil</label>
                    <input type="file" name="avatar" accept="image/*" style="color: var(--text-gray); font-size: 0.9rem;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-bottom: 1.5rem;">
                <div>
                    <label class="form-label">Nome Completo</label>
                    <input type="text" name="nome" class="form-input" required value="<?php echo htmlspecialchars($user_data['nome'] ?? ''); ?>">
                </div>
                <div>
                    <label class="form-label">E-mail de Acesso</label>
                    <input type="email" name="email" class="form-input" required value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>">
                </div>
            </div>
            
            <div style="margin-bottom: 2.5rem;">
                <label class="form-label">Nova Senha (Deixe em branco para manter a atual)</label>
                <input type="password" name="senha_nova" class="form-input" placeholder="Digite apenas se quiser alterar">
            </div>

            <div style="margin-bottom: 1rem;">
                <h2 style="color: var(--text-white); font-size: 1.1rem; margin: 0 0 0.3rem 0;">Personalização de Cores</h2>
                <p style="color: var(--text-gray); margin: 0; font-size: 0.85rem;">Escolha as cores usadas na sua interface (logo, menu ativo e botões).</p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-bottom: 2.5rem;">
                <div>
                    <label class="form-label">Cor de Destaque (logo, links, menu)</label>
                    <input type="color" name="cor_primaria" class="color-input" value="<?php echo htmlspecialchars($user_data['cor_primaria'] ?? '#3b82f6'); ?>">
                </div>
                <div>
                    <label class="form-label">Cor dos Botões</label>
                    <input type="color" name="cor_botoes" class="color-input" value="<?php echo htmlspecialchars($user_data['cor_botoes'] ?? '#3b82f6'); ?>">
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <h2 style="color: var(--text-white); font-size: 1.1rem; margin: 0 0 0.3rem 0;">Aparência</h2>
                <p style="color: var(--text-gray); margin: 0; font-size: 0.85rem;">Escolha entre o tema escuro ou claro para a interface.</p>
            </div>

            <div style="display: flex; gap: 1rem; margin-bottom: 2.5rem; max-width: 500px;">
                <label class="tema-opcao">
                    <input type="radio" name="tema" value="escuro" <?php echo ($user_data['tema'] ?? 'escuro') !== 'claro' ? 'checked' : ''; ?>>
                    <i class="bi bi-moon-stars-fill" style="font-size: 1.5rem; color: var(--text-white);"></i>
                    <p style="margin: 0.5rem 0 0 0; color: var(--text-white); font-weight: 600; font-size: 0.9rem;">Escuro</p>
                </label>
                <label class="tema-opcao">
                    <input type="radio" name="tema" value="claro" <?php echo ($user_data['tema'] ?? 'escuro') === 'claro' ? 'checked' : ''; ?>>
                    <i class="bi bi-sun-fill" style="font-size: 1.5rem; color: var(--text-white);"></i>
                    <p style="margin: 0.5rem 0 0 0; color: var(--text-white); font-weight: 600; font-size: 0.9rem;">Claro</p>
                </label>
            </div>

            <button type="submit" name="atualizar_perfil" class="btn-personalizado">Salvar Alterações</button>
        </form>

    </main>
</div>
</body>
</html>