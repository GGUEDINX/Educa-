<?php
// abrir_ferramenta.php
// Registra o clique da ferramenta (para o KPI "Ferramentas mais acessadas") e redireciona.
require_once 'auth.php';
require_once __DIR__ . '/app/autoload.php';

use App\Controllers\FerramentaController;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$empresa_id = $_SESSION['empresa_id'] ?? null;

if (!$id || !$empresa_id) {
    header("Location: ferramentas.php");
    exit();
}

$url = (new FerramentaController())->abrirERegistrarClique($id, (int)$empresa_id);

header("Location: " . ($url ?? 'ferramentas.php'));
exit();
