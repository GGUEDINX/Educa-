<?php
// ferramentas.php
require_once 'auth.php';
require_once __DIR__ . '/app/autoload.php';

use App\Controllers\FerramentaController;
use App\Core\Security;

$empresa_id = $_SESSION['empresa_id'] ?? null;
$tipo_usuario = $_SESSION['tipo_usuario'] ?? 'funcionario';
$mensagem = '';
$tipo_mensagem = '';

if (!$empresa_id) {
    die("Erro: Empresa não identificada na sessão.");
}

$ferramentaController = new FerramentaController();

// ==========================================
// AÇÃO: CADASTRAR FERRAMENTA (Apenas Admin)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::validarCsrf();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_ferramenta']) && $tipo_usuario === 'admin_empresa') {
    $resultado = $ferramentaController->cadastrar(
        (int)$empresa_id,
        trim($_POST['nome']),
        trim($_POST['descricao']),
        trim($_POST['url'])
    );
    $mensagem = $resultado['mensagem'];
    $tipo_mensagem = $resultado['tipo'];
}

// ==========================================
// AÇÃO: EXCLUIR FERRAMENTA (Apenas Admin)
// ==========================================
if (isset($_POST['excluir']) && $tipo_usuario === 'admin_empresa') {
    $resultado = $ferramentaController->excluir((int)$_POST['excluir'], (int)$empresa_id);
    $mensagem = $resultado['mensagem'];
    $tipo_mensagem = $resultado['tipo'];
}

// ==========================================
// BUSCAR FERRAMENTAS DA EMPRESA
// ==========================================
$ferramentas = $ferramentaController->listarPorEmpresa((int)$empresa_id);

require_once 'includes/header.php';
?>

<div class="main-container">

    <?php require_once 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Título da Seção -->
        <div style="margin-bottom: 2rem;">
            <h1 style="margin: 0; font-size: 1.8rem; font-weight: 700; color: var(--text-white);">Ferramentas Corporativas</h1>
            <p style="margin: 0.3rem 0 0 0; color: var(--text-gray); font-size: 0.95rem;">
                <?php echo $tipo_usuario === 'admin_empresa' 
                    ? 'Adicione e gerencie os links de ferramentas úteis para a sua equipe.' 
                    : 'Acesse rapidamente as ferramentas e plataformas disponibilizadas pela sua empresa.'; ?>
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

        <!-- Formulário de Cadastro (Exclusivo para Admin) -->
        <?php if ($tipo_usuario === 'admin_empresa'): ?>
            <div style="background-color: var(--bg-card); padding: 2rem; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 2.5rem;">
                <h2 style="font-size: 1.2rem; margin-top: 0; margin-bottom: 1.5rem; color: var(--text-white); font-weight: 600;">Cadastrar Nova Ferramenta</h2>
                
                <form action="ferramentas.php" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php echo Security::campoCsrf(); ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <div>
                            <label style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.5rem; font-weight: 600;">Nome da Ferramenta</label>
                            <input type="text" name="nome" required placeholder="Ex: Trello, GitHub, Workspace" style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); outline: none;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.5rem; font-weight: 600;">Link de Acesso (URL)</label>
                            <input type="text" name="url" required placeholder="Ex: https://trello.com/sua-equipe" style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); outline: none;">
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.5rem; font-weight: 600;">Descrição / Finalidade (Opcional)</label>
                        <input type="text" name="descricao" placeholder="Ex: Gerenciamento de tarefas e projetos do time." style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-white); outline: none;">
                    </div>
                    <div style="text-align: right; margin-top: 0.5rem;">
                        <button type="submit" name="cadastrar_ferramenta" style="padding: 0.75rem 1.5rem; background-color: var(--primary); color: var(--text-white); border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: var(--transition);">
                            Adicionar Ferramenta
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Lista/Cards de Ferramentas Cadastradas -->
        <h2 style="font-size: 1.2rem; margin-top: 0; margin-bottom: 1.5rem; color: var(--text-white); font-weight: 600;">Links Disponíveis</h2>
        
        <?php if (count($ferramentas) === 0): ?>
            <p style="color: var(--text-gray); font-size: 0.95rem; margin: 0;">Nenhuma ferramenta corporativa foi cadastrada para a sua empresa ainda.</p>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <?php foreach ($ferramentas as $f): ?>
                    <div class="stat-card" style="max-width: 100%; display: flex; flex-direction: column; justify-content: space-between; height: 180px; position: relative;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <h3 style="margin: 0; color: var(--text-white); font-size: 1.1rem; font-weight: 700;"><?php echo htmlspecialchars($f['nome']); ?></h3>
                                
                                <!-- Botão de Excluir (Só aparece para Admin) -->
                                <?php if ($tipo_usuario === 'admin_empresa'): ?>
                                    <form action="ferramentas.php" method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir esta ferramenta?');">
                                        <?php echo Security::campoCsrf(); ?>
                                        <input type="hidden" name="excluir" value="<?php echo $f['id']; ?>">
                                        <button type="submit" title="Remover ferramenta" style="color: #60a5fa; background: none; border: none; text-decoration: none; font-size: 0.8rem; font-weight: 600; cursor: pointer; padding: 0;">
                                            ❌
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <p style="margin: 0; color: var(--text-gray); font-size: 0.85rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo htmlspecialchars($f['descricao'] ?: 'Nenhuma descrição fornecida.'); ?>
                            </p>
                        </div>

                        <div style="margin-top: 1rem;">
                            <a href="abrir_ferramenta.php?id=<?php echo $f['id']; ?>" target="_blank" rel="noopener noreferrer" 
                               style="display: block; text-align: center; background-color: rgba(59, 130, 246, 0.1); color: var(--primary); border: 1px solid rgba(59, 130, 246, 0.2); text-decoration: none; padding: 0.5rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; transition: var(--transition);"
                               onmouseover="this.style.backgroundColor='var(--primary)'; this.style.color='var(--text-white)';"
                               onmouseout="this.style.backgroundColor='rgba(59, 130, 246, 0.1)'; this.style.color='var(--primary)';">
                                Acessar Ferramenta ↗
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>