<?php
// dashboard_empresa.php
require_once 'auth.php';

// Proteção: apenas administradores de empresas podem ver este painel
if ($_SESSION['tipo_usuario'] !== 'admin_empresa') {
    header("Location: dashboard_funcionario.php");
    exit();
}

require_once __DIR__ . '/app/autoload.php';

use App\Controllers\DashboardEmpresaController;

$empresa_nome = $_SESSION['empresa_nome'] ?? 'Sua Empresa';
$empresa_id = $_SESSION['empresa_id'] ?? null;
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Gestor';

$painel = $empresa_id
    ? (new DashboardEmpresaController())->montarPainel((int)$empresa_id)
    : [
        'total_funcionarios' => 0, 'taxa_conclusao' => 0, 'taxa_conclusao_label' => 'cursos obrigatórios',
        'ativos_semana' => null, 'inativos_semana' => null, 'ranking_certificados' => [],
        'total_ferramentas' => 0, 'top_ferramentas' => [], 'cursos_destaque' => [], 'max_acessos_curso' => 1,
        'vencimentos' => [], 'solicitacoes_pendentes' => 0,
    ];

// Extrai o array em variáveis (mantém a view exatamente igual à anterior)
extract($painel);

require_once 'includes/header.php';
?>

<!-- Container principal que mantém a Sidebar fixada e o Conteúdo alinhado -->
<div class="main-container">

    <?php require_once 'includes/sidebar.php'; ?>

    <!-- Conteúdo Dinâmico do Painel Corporativo -->
    <main class="main-content">

        <!-- Banner de Boas-vindas -->
        <section class="banner-admin">
            <h1 style="margin: 0; font-size: 2rem; font-weight: 800; color: var(--text-white);">
                Painel Administrativo: <?php echo htmlspecialchars($empresa_nome); ?>
            </h1>
            <p style="margin: 0.5rem 0 0 0; opacity: 0.9; color: var(--text-white); font-size: 1rem;">
                Olá, <strong><?php echo htmlspecialchars($usuario_nome); ?></strong>! Monitore o engajamento e gerencie os acessos corporativos da sua empresa.
            </p>
        </section>

        <!-- ============================================== -->
        <!-- 1. INDICADORES DE DESEMPENHO DA EQUIPE (KPIs)   -->
        <!-- ============================================== -->
        <h2 style="color: var(--text-white); font-size: 1.1rem; font-weight: 700; margin: 2rem 0 1rem 0;">📊 Desempenho da Equipe</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">

            <!-- Taxa de Conclusão -->
            <div class="stat-card">
                <h3 style="margin: 0 0 1rem 0; color: var(--text-gray); font-size: 0.85rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">
                    Taxa de Conclusão
                </h3>
                <p style="font-size: 2.5rem; font-weight: 800; margin: 0 0 0.75rem 0; color: var(--primary); line-height: 1;">
                    <?php echo $taxa_conclusao; ?>%
                </p>
                <div style="width: 100%; height: 10px; background-color: var(--bg-dark); border-radius: 20px; overflow: hidden;">
                    <div style="width: <?php echo min(100, $taxa_conclusao); ?>%; height: 100%; background-color: var(--primary); border-radius: 20px; transition: width 0.4s;"></div>
                </div>
                <p style="margin: 0.75rem 0 0 0; color: var(--text-gray); font-size: 0.8rem;">
                    Conclusão de <?php echo $taxa_conclusao_label; ?> pela equipe.
                </p>
            </div>

            <!-- Funcionários Ativos vs Inativos -->
            <div class="stat-card">
                <h3 style="margin: 0 0 1rem 0; color: var(--text-gray); font-size: 0.85rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">
                    Ativos na Última Semana
                </h3>
                <?php if ($ativos_semana === null): ?>
                    <p style="color: var(--text-gray); font-size: 0.85rem; margin: 0;">
                        Indisponível. Rode <strong>db/migracao_dashboard.sql</strong> para ativar este indicador.
                    </p>
                <?php else: ?>
                    <p style="font-size: 2.5rem; font-weight: 800; margin: 0; color: #10b981; line-height: 1;">
                        <?php echo $ativos_semana; ?> <span style="font-size: 1.1rem; color: var(--text-gray); font-weight: 600;">/ <?php echo $total_funcionarios; ?></span>
                    </p>
                    <p style="margin: 0.75rem 0 0 0; color: var(--text-gray); font-size: 0.85rem;">
                        <span style="color: #f59e0b; font-weight: 700;"><?php echo $inativos_semana; ?></span> colaborador(es) sem acesso há mais de 7 dias.
                    </p>
                <?php endif; ?>
            </div>

            <!-- Ranking de Certificação -->
            <div class="stat-card">
                <h3 style="margin: 0 0 1rem 0; color: var(--text-gray); font-size: 0.85rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">
                    🏆 Ranking de Certificação
                </h3>
                <?php if (empty($ranking_certificados)): ?>
                    <p style="color: var(--text-gray); font-size: 0.85rem; margin: 0;">Nenhum certificado emitido pela equipe ainda.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                        <?php foreach ($ranking_certificados as $i => $r): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span style="color: var(--text-white); font-size: 0.9rem;">
                                    <?php echo ($i + 1); ?>º <?php echo htmlspecialchars($r['nome']); ?>
                                </span>
                                <span style="color: var(--primary); font-weight: 700; font-size: 0.9rem;">
                                    <?php echo $r['total_certificados']; ?> 🎓
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- 2. GESTÃO DE CONTEÚDO E FERRAMENTAS             -->
        <!-- ============================================== -->
        <h2 style="color: var(--text-white); font-size: 1.1rem; font-weight: 700; margin: 2.5rem 0 1rem 0;">🛠️ Conteúdo e Ferramentas</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">

            <!-- Visão Geral de Ferramentas -->
            <div class="stat-card">
                <h3 style="margin: 0 0 1rem 0; color: var(--text-gray); font-size: 0.85rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">
                    Ferramentas Corporativas
                </h3>
                <p style="font-size: 2.5rem; font-weight: 800; margin: 0 0 0.75rem 0; color: var(--primary); line-height: 1;">
                    <?php echo $total_ferramentas; ?>
                </p>
                <?php if (empty($top_ferramentas)): ?>
                    <p style="color: var(--text-gray); font-size: 0.85rem; margin: 0;">Nenhuma ferramenta acessada ainda.</p>
                <?php else: ?>
                    <p style="margin: 0 0 0.4rem 0; color: var(--text-gray); font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Mais acessadas</p>
                    <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                        <?php foreach ($top_ferramentas as $tf): ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
                                <span style="color: var(--text-white);"><?php echo htmlspecialchars($tf['nome']); ?></span>
                                <span style="color: var(--text-gray);"><?php echo (int)$tf['cliques']; ?> acessos</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <a href="ferramentas.php" style="display: block; margin-top: 1rem; text-align: center; color: var(--primary); text-decoration: none; font-size: 0.85rem; font-weight: 600;">Gerenciar Ferramentas →</a>
            </div>

            <!-- Cursos em Destaque -->
            <div class="stat-card">
                <h3 style="margin: 0 0 1rem 0; color: var(--text-gray); font-size: 0.85rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">
                    Cursos em Destaque
                </h3>
                <?php if (empty($cursos_destaque) || $cursos_destaque[0]['total_acessos'] == 0): ?>
                    <p style="color: var(--text-gray); font-size: 0.85rem; margin: 0;">A equipe ainda não iniciou nenhum curso.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach ($cursos_destaque as $cd): ?>
                            <?php if ($cd['total_acessos'] == 0) continue; ?>
                            <div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 0.25rem;">
                                    <span style="color: var(--text-white);"><?php echo htmlspecialchars($cd['titulo']); ?></span>
                                    <span style="color: var(--text-gray);"><?php echo $cd['total_acessos']; ?></span>
                                </div>
                                <div style="width: 100%; height: 8px; background-color: var(--bg-dark); border-radius: 20px; overflow: hidden;">
                                    <div style="width: <?php echo round(($cd['total_acessos'] / $max_acessos_curso) * 100); ?>%; height: 100%; background-color: var(--primary); border-radius: 20px;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- ============================================== -->
        <!-- 3. ALERTAS E AÇÕES PENDENTES                    -->
        <!-- ============================================== -->
        <h2 style="color: var(--text-white); font-size: 1.1rem; font-weight: 700; margin: 2.5rem 0 1rem 0;">🔔 Alertas e Ações Pendentes</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">

            <!-- Próximos Vencimentos -->
            <div class="stat-card">
                <h3 style="margin: 0 0 1rem 0; color: var(--text-gray); font-size: 0.85rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">
                    Certificados a Expirar (30 dias)
                </h3>
                <?php if (empty($vencimentos)): ?>
                    <p style="color: var(--text-gray); font-size: 0.85rem; margin: 0;">Nenhum certificado vencendo nos próximos 30 dias. ✅</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                        <?php foreach ($vencimentos as $v): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; padding: 0.5rem 0.75rem; background-color: rgba(59, 130, 246, 0.08); border-left: 3px solid #3b82f6; border-radius: 4px;">
                                <span style="color: var(--text-white);"><?php echo htmlspecialchars($v['nome_funcionario']); ?> — <?php echo htmlspecialchars($v['curso_nome']); ?></span>
                                <span style="color: #60a5fa; font-weight: 700; white-space: nowrap; margin-left: 0.5rem;"><?php echo date('d/m/Y', strtotime($v['validade'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Solicitações Pendentes / Ações Rápidas -->
            <div class="stat-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h3 style="margin: 0 0 1rem 0; color: var(--text-gray); font-size: 0.85rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">
                        Solicitações Pendentes
                    </h3>
                    <p style="font-size: 2.5rem; font-weight: 800; margin: 0 0 0.5rem 0; color: <?php echo $solicitacoes_pendentes > 0 ? '#60a5fa' : '#10b981'; ?>; line-height: 1;">
                        <?php echo $solicitacoes_pendentes; ?>
                    </p>
                    <p style="color: var(--text-gray); font-size: 0.85rem; margin-bottom: 1.25rem;">
                        Sugestões de novos cursos enviadas pela equipe aguardando resposta.
                    </p>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <a href="solicitacoes.php" style="display: block; text-align: center; background-color: var(--primary); color: var(--text-white); text-decoration: none; padding: 0.75rem; border-radius: 6px; font-weight: 600; font-size: 0.9rem; transition: var(--transition);"
                       onmouseover="this.style.backgroundColor='#2563eb'"
                       onmouseout="this.style.backgroundColor=''">
                        Ver Solicitações
                    </a>
                    <a href="gerenciar_funcionarios.php" style="display: block; text-align: center; background-color: transparent; color: var(--primary); border: 1px solid var(--primary); text-decoration: none; padding: 0.75rem; border-radius: 6px; font-weight: 600; font-size: 0.9rem; transition: var(--transition);">
                        Ir para Gerenciar Equipe
                    </a>
                </div>
            </div>

        </div>

    </main>

</div> <!-- Fim da main-container -->

</body>
</html>
