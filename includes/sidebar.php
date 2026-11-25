<?php
// includes/sidebar.php
require_once __DIR__ . '/../app/autoload.php';

use App\Controllers\SuportePedagogicoController;

$pagina_atual = basename($_SERVER['PHP_SELF']);
$tipo_usuario = $_SESSION['tipo_usuario'] ?? 'funcionario';

// Notificação: quantas respostas de dúvida o funcionário ainda não viu
$duvidas_nao_lidas = 0;
if ($tipo_usuario === 'funcionario' && isset($_SESSION['usuario_id'])) {
    try {
        $duvidas_nao_lidas = (new SuportePedagogicoController())->contarNaoLidas((int)$_SESSION['usuario_id']);
    } catch (\Throwable $e) {
        $duvidas_nao_lidas = 0;
    }
}

if ($tipo_usuario === 'pedagogia') {
    // Painel próprio, separado do painel da empresa
    $links = [
        ['link' => 'dashboard_pedagogico.php', 'icon' => 'bi-grid-1x2-fill', 'nome' => 'Dashboard'],
        ['link' => 'gerenciar_cursos.php', 'icon' => 'bi-collection-play-fill', 'nome' => 'Biblioteca de Módulos'],
        ['link' => 'suporte_pedagogico.php', 'icon' => 'bi-chat-left-text-fill', 'nome' => 'Suporte Pedagógico'],
        ['link' => 'configuracoes.php', 'icon' => 'bi-gear-fill', 'nome' => 'Configurações'],
    ];
} else {
    $links = [
        ['link' => $tipo_usuario === 'admin_empresa' ? 'dashboard_empresa.php' : 'dashboard_funcionario.php', 'icon' => 'bi-grid-1x2-fill', 'nome' => 'Dashboard'],
        ['link' => 'ferramentas.php', 'icon' => 'bi-tools', 'nome' => 'Ferramentas'],
        ['link' => 'cursos.php', 'icon' => 'bi-mortarboard-fill', 'nome' => 'Cursos', 'perfil' => 'funcionario'],
        ['link' => 'certificados.php', 'icon' => 'bi-patch-check-fill', 'nome' => 'Certificados', 'perfil' => 'funcionario'],
        ['link' => 'recompensas.php', 'icon' => 'bi-shop', 'nome' => $tipo_usuario === 'admin_empresa' ? 'Loja & Vouchers' : 'Loja'],
        ['link' => 'solicitacoes.php', 'icon' => 'bi-chat-square-text-fill', 'nome' => 'Solicitações'],
        ['link' => 'suporte_pedagogico.php', 'icon' => 'bi-mortarboard', 'nome' => 'Suporte Pedagógico', 'badge' => $duvidas_nao_lidas],
        ['link' => 'configuracoes.php', 'icon' => 'bi-gear-fill', 'nome' => 'Configurações'],
        ['link' => 'gerenciar_funcionarios.php', 'icon' => 'bi-people-fill', 'nome' => 'Gestão de Equipe', 'perfil' => 'admin_empresa'],
        ['link' => 'planos.php', 'icon' => 'bi-gem', 'nome' => 'Meu Plano', 'perfil' => 'admin_empresa']
    ];
}
?>

<!-- SIDEBAR SEM FIXED (Fica naturalmente à esquerda) -->
<aside style="width: 260px; min-width: 260px; background-color: var(--bg-card); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 2rem 1.5rem; box-sizing: border-box; flex-shrink: 0;">
    
    <!-- Logo e+ educa+ -->
    <div style="margin-bottom: 3rem; padding-left: 0.5rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div style="width: 35px; height: 35px; background-color: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: var(--text-white); font-size: 1.2rem;">e+</div>
            <span style="font-size: 1.35rem; font-weight: 800; color: var(--text-white); letter-spacing: 0.5px;">educa+</span>
        </div>
    </div>

    <!-- Navegação -->
    <nav style="display: flex; flex-direction: column; gap: 0.5rem; flex-grow: 1;">
        <?php
        foreach ($links as $item) {
            if (isset($item['perfil']) && $item['perfil'] !== $tipo_usuario) continue;
            
            $ativo = ($pagina_atual == $item['link']);
            $cor_texto = $ativo ? 'var(--text-white)' : 'var(--text-gray)';
            $fundo_ativo = $ativo ? 'rgba(59, 130, 246, 0.1)' : 'transparent'; // Azul suave
            $borda_ativa = $ativo ? '3px solid var(--primary)' : '3px solid transparent';
            
            $badge = '';
            if (!empty($item['badge'])) {
                $badge = '<span style="background-color: #f59e0b; color: var(--bg-card); font-size: 0.7rem; font-weight: 700; padding: 0.1rem 0.5rem; border-radius: 20px; margin-left: auto;">' . (int)$item['badge'] . '</span>';
            }

            echo '<a href="'.$item['link'].'" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.8rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.95rem; font-weight: 600; color: '.$cor_texto.'; background-color: '.$fundo_ativo.'; border-left: '.$borda_ativa.'; transition: var(--transition);">
                    <i class="bi '.$item['icon'].'"></i> '.$item['nome'].' '.$badge.'
                  </a>';
        }
        ?>
    </nav>

    <!-- Botão de Sair -->
    <div style="border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
        <a href="logout.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.8rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.95rem; font-weight: 600; color: #60a5fa;">
            <i class="bi bi-box-arrow-left"></i> Sair da Conta
        </a>
    </div>
</aside>