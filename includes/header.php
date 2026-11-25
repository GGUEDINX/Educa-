<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tema = $_SESSION['tema'] ?? 'escuro';

if ($tema === 'claro') {
    $cor_fundo_pagina = '#f1f5f9';
    $cor_fundo_card = '#ffffff';
    $cor_borda = '#e2e8f0';
    $cor_texto_secundario = '#64748b';
    $cor_texto_principal = '#0f172a';
} else {
    $cor_fundo_pagina = '#0f172a';
    $cor_fundo_card = '#1e293b';
    $cor_borda = '#334155';
    $cor_texto_secundario = '#94a3b8';
    $cor_texto_principal = '#ffffff';
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-tema="<?php echo $tema; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel | educa+</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary: <?php echo $_SESSION['cor_destaque'] ?? '#3b82f6'; ?>;
            --btn-color: <?php echo $_SESSION['cor_botoes'] ?? ($_SESSION['cor_destaque'] ?? '#3b82f6'); ?>;
            --primary-rgb: 59, 130, 246;
            --bg-dark: <?php echo $cor_fundo_pagina; ?>;
            --bg-card: <?php echo $cor_fundo_card; ?>;
            --border-color: <?php echo $cor_borda; ?>;
            --text-gray: <?php echo $cor_texto_secundario; ?>;
            --text-white: <?php echo $cor_texto_principal; ?>;
            --transition: 0.3s;
        }
        
        /* O BODY DEFINE A SIDEBAR NA ESQUERDA E O RESTO NA DIREITA */
        body { 
            margin: 0; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: var(--bg-dark); 
            color: var(--text-white);
            display: flex; 
            min-height: 100vh;
        }
    </style>
</head>
<body>

<!-- 1. CARREGA A SIDEBAR (Fica na esquerda até o topo) -->
<?php require_once 'sidebar.php'; ?>

<!-- 2. CRIA O ESPAÇO DA DIREITA (Onde vai ficar o header do usuário e as páginas) -->
<div style="flex: 1; display: flex; flex-direction: column;">
    
    <!-- HEADER DO USUÁRIO (Apenas foto e nome, sem logo) -->
    <header style="display: flex; align-items: center; justify-content: flex-end; padding: 1rem 2rem; background: var(--bg-card); border-bottom: 1px solid var(--border-color);">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span style="font-size: 0.9rem; color: var(--text-gray);">
                <?php echo htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário'); ?>
            </span>
            <div style="width: 38px; height: 38px; border-radius: 50%; background: var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px solid var(--primary);">
                <?php 
                $avatar = $_SESSION['usuario_avatar'] ?? 'default.png';
                $avatar_path = 'uploads/avatars/' . $avatar;
                if ($avatar !== 'default.png' && file_exists($avatar_path)): 
                ?>
                    <img src="<?php echo $avatar_path; ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
                <?php else: ?>
                    <span style="font-size: 1rem;">👤</span>
                <?php endif; ?>
            </div>
        </div>
    </header>