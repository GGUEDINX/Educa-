<?php
// auth.php
require_once __DIR__ . '/app/autoload.php';

use App\Core\Security;

Security::iniciarSessaoSegura();
Security::enviarCabecalhosSeguranca();

// Se não há sessão ativa, expulsa para a tela de login
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_usuario'])) {
    header("Location: login.php");
    exit();
}