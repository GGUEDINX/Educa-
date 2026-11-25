<?php
// cursos.php
require_once 'auth.php';
require_once __DIR__ . '/app/autoload.php';
require_once 'includes/header.php';

use App\Controllers\CursoController;

$usuario_id = $_SESSION['usuario_id'];
$tipo_usuario = $_SESSION['tipo_usuario'];

$cursos = (new CursoController())->listarParaUsuario((int)$usuario_id);
?>

<div class="main-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Cabeçalho da Página -->
        <div style="margin-bottom: 2.5rem;">
            <h1 style="color: var(--text-white); font-size: 2rem; font-weight: 800; margin: 0;">Trilhas de Aprendizado</h1>
            <p style="color: var(--text-gray); margin-top: 0.5rem; font-size: 1rem;">
                Acompanhe e complete os treinamentos disponíveis para o seu desenvolvimento.
            </p>
        </div>

        <!-- Grid de Cursos -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            
            <?php foreach ($cursos as $curso): ?>
                <div style="background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                    
                    <!-- Cabeçalho do Card -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="width: 45px; height: 45px; background-color: rgba(var(--primary-rgb), 0.1); display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 1.2rem;">
                            🎓
                        </div>
                        <?php if ($curso['concluido']): ?>
                            <span style="background-color: rgba(16, 185, 129, 0.1); color: #10b981; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 20px;">
                                Concluído
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Informações -->
                    <div>
                        <h3 style="color: var(--text-white); font-size: 1.2rem; margin: 0 0 0.5rem 0; font-weight: 700;">
                            <?php echo htmlspecialchars($curso['titulo']); ?>
                        </h3>
                        <p style="color: var(--text-gray); font-size: 0.9rem; line-height: 1.5; margin: 0;">
                            <?php echo htmlspecialchars($curso['descricao']); ?>
                        </p>
                    </div>

                    <!-- Rodapé do Card (Ação) -->
                    <div style="margin-top: auto; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                        <?php if ($tipo_usuario === 'funcionario'): ?>
                            <?php if ($curso['concluido']): ?>
                                <a href="certificados.php" style="text-decoration: none; padding: 0.6rem 1.5rem; border-radius: 6px; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: fit-content; margin: 0 auto; box-sizing: border-box; text-align: center; background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">
                                    Ver Certificado <i class="bi bi-arrow-up-right"></i>
                                </a>
                            <?php else: ?>
                                <a href="aula.php?curso_id=<?php echo $curso['id']; ?>" 
                                   class="btn-personalizado" 
                                   style="text-decoration: none; padding: 0.6rem 1.5rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; display: block; width: fit-content; margin: 0 auto; text-align: center;">
                                    Iniciar Trilha de Aulas
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </main>
</div>

</body>
</html>