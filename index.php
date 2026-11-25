<?php
// index.php
// Ponto de entrada do site: manda o visitante para o lugar certo.
require_once __DIR__ . '/app/autoload.php';

use App\Core\Security;

Security::iniciarSessaoSegura();

if (isset($_SESSION['usuario_id']) && isset($_SESSION['tipo_usuario'])) {
    $destino = match ($_SESSION['tipo_usuario']) {
        'admin_empresa' => 'dashboard_empresa.php',
        'pedagogia' => 'dashboard_pedagogico.php',
        default => 'dashboard_funcionario.php',
    };
    header("Location: $destino");
} else {
    header("Location: login.php");
}
exit();
