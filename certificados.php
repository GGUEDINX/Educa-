<?php
// certificados.php
require_once 'auth.php';
require_once __DIR__ . '/app/autoload.php';
require_once 'includes/header.php';

use App\Controllers\CertificadoController;

$usuario_id = $_SESSION['usuario_id'];
$certificados = (new CertificadoController())->listarDoUsuario((int)$usuario_id);
?>

<div class="main-container">
    <?php require_once 'includes/sidebar.php'; ?>
    <main class="main-content">
        <h1 style="color: var(--text-white); margin-bottom: 2rem;">Meus Certificados</h1>
        
        <?php if (empty($certificados)): ?>
            <p style="color: var(--text-gray);">Nenhum certificado emitido. Complete um curso para receber o seu!</p>
        <?php else: ?>
            <div style="display: grid; gap: 1rem;">
                <?php foreach ($certificados as $cert): ?>
                    <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--border-color);">
                        <div>
                            <h3 style="color: var(--text-white); margin: 0;"><?php echo htmlspecialchars($cert['curso_nome']); ?></h3>
                            <p style="color: var(--text-gray); margin: 0.3rem 0 0 0; font-size: 0.9rem;">
                                Código: <?php echo $cert['codigo_validacao']; ?> | Concluído em: <?php echo date('d/m/Y', strtotime($cert['data_conclusao'])); ?>
                            </p>
                        </div>
                        <a href="emitir_certificado.php?id=<?php echo $cert['id']; ?>" class="btn-personalizado" style="text-decoration: none; padding: 0.5rem 1rem;">
                            <i class="bi bi-download"></i> PDF
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>