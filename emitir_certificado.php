<?php
// emitir_certificado.php
require_once 'auth.php';
require_once __DIR__ . '/app/autoload.php';

use App\Controllers\CertificadoController;

$cert_id = (int)($_GET['id'] ?? 0);
$cert = (new CertificadoController())->buscarParaEmissao($cert_id, (int)$_SESSION['usuario_id']);

if (!$cert) die("Certificado não encontrado.");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Visualização do Certificado</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Inter:wght@400;600&display=swap');
        
        body { background-color: #f1f5f9; margin: 0; padding: 20px; font-family: 'Inter', sans-serif; }
        
        /* Layout do Certificado */
        .cert-container {
            width: 900px;
            background: #ffffff;
            margin: 0 auto;
            padding: 60px;
            border: 10px solid #1e293b; /* Borda externa escura */
            outline: 5px solid #10b981; /* Borda interna verde */
            outline-offset: -20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            text-align: center;
        }

        h1 { font-family: 'Playfair Display', serif; font-size: 3rem; color: #1e293b; margin-bottom: 0.5rem; }
        .logo-text { color: #3b82f6; font-weight: 800; letter-spacing: 2px; font-size: 1.2rem; }
        .sub { color: #64748b; text-transform: uppercase; letter-spacing: 2px; font-size: 0.8rem; margin-bottom: 3rem; }
        
        .nome-usuario { font-family: 'Playfair Display', serif; font-size: 2.2rem; color: #000; margin: 1rem 0; border-bottom: 2px solid #e2e8f0; display: inline-block; padding: 0 20px; }
        .texto-corpo { font-size: 1.1rem; color: #475569; line-height: 1.8; max-width: 700px; margin: 0 auto; }
        
        .footer { margin-top: 80px; display: flex; justify-content: space-around; align-items: center; }
        .assinatura { border-top: 1px solid #000; padding-top: 10px; width: 250px; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="cert-container">
    <div class="logo-text">EDUCA+</div>
    <div class="sub">CERTIFICADO DE CONCLUSÃO</div>
    
    <h1>Certificado de Treinamento</h1>
    
    <p class="texto-corpo">Certificamos para os devidos fins que o colaborador(a)</p>
    <div class="nome-usuario"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></div>
    
    <p class="texto-corpo">
        concluiu com êxito o treinamento corporativo de<br>
        <strong style="font-size: 1.4rem; color: #1e293b;">"<?php echo htmlspecialchars($cert['curso_nome']); ?>"</strong><br>
        ministrado pela plataforma educacional interna da empresa <strong>Tech Solutions</strong>.
    </p>

    <div class="footer">
        <div class="assinatura">
            <p>Data: <?php echo date('d/m/Y', strtotime($cert['data_conclusao'])); ?></p>
        </div>
        <div class="assinatura">
            <p>Autenticação: <?php echo $cert['codigo_validacao']; ?></p>
        </div>
    </div>
</div>

<script>
    // Opcional: abre o print automaticamente
    window.print();
</script>

</body>
</html>