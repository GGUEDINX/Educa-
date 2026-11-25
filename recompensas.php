<?php
// recompensas.php (Loja de Vouchers - Protótipo Front-end integrado ao layout educa+)
require_once 'auth.php';
require_once __DIR__ . '/app/autoload.php';

use App\Controllers\RecompensaController;

$empresa_id = $_SESSION['empresa_id'] ?? null;
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$tipo_usuario = $_SESSION['tipo_usuario'] ?? 'funcionario';

if (!$empresa_id) {
    die("Erro: Empresa não identificada na sessão.");
}

$recompensaController = new RecompensaController();

// Mantém apenas leitura do saldo real (se existir) como ponto de partida da simulação visual
$saldo_inicial = 50;
if ($tipo_usuario === 'funcionario') {
    try {
        $saldo_banco = (int)$recompensaController->saldoDoFuncionario($usuario_id);
        if ($saldo_banco > 0) {
            $saldo_inicial = $saldo_banco;
        }
    } catch (\Throwable $e) {
        $saldo_inicial = 50;
    }
}

require_once 'includes/header.php';
?>

<style>
    .loja-header-wrap {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.75rem;
    }
    .badge-demo {
        display: inline-block;
        font-size: 0.72rem;
        background: rgba(245, 158, 11, 0.16);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.4);
        padding: 0.2rem 0.65rem;
        border-radius: 999px;
        font-weight: 700;
        vertical-align: middle;
        margin-left: 0.5rem;
    }
    .points-pill {
        background: rgba(59, 130, 246, 0.14);
        border: 1px solid rgba(59, 130, 246, 0.4);
        color: var(--text-white);
        padding: 0.65rem 1rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.92rem;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
    }
    .loja-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        margin-bottom: 1.5rem;
    }
    .loja-tab-btn {
        background: var(--bg-card);
        color: var(--text-gray);
        border: 1px solid var(--border-color);
        padding: 0.6rem 1.1rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        transition: 0.2s;
    }
    .loja-tab-btn.active {
        background: var(--primary);
        color: #ffffff;
        border-color: var(--primary);
    }
    .loja-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    .loja-input, .loja-select {
        background: var(--bg-card);
        color: var(--text-white);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 0.7rem 0.9rem;
        font-size: 0.9rem;
        box-sizing: border-box;
    }
    .loja-input {
        flex: 1;
        min-width: 220px;
    }
    .loja-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
        gap: 1.25rem;
    }
    .loja-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.35rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 1rem;
    }
    .loja-card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .loja-cat-tag {
        font-size: 0.74rem;
        padding: 0.22rem 0.65rem;
        border-radius: 999px;
        background: rgba(59, 130, 246, 0.12);
        color: var(--primary);
        font-weight: 700;
    }
    .loja-stock-tag {
        font-size: 0.75rem;
        font-weight: 700;
        color: #10b981;
    }
    .loja-stock-tag.empty {
        color: #ef4444;
    }
    .loja-voucher-box {
        font-family: monospace;
        font-size: 0.78rem;
        background: var(--bg-dark);
        border: 1px dashed var(--border-color);
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        color: var(--text-gray);
        margin-top: 0.75rem;
    }
    .loja-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 0.85rem;
        border-top: 1px solid var(--border-color);
    }
    .loja-cost {
        font-weight: 800;
        color: var(--primary);
        font-size: 0.98rem;
    }
    .loja-form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.9rem;
        margin-top: 1rem;
    }
    .loja-field {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }
    .loja-field label {
        font-size: 0.82rem;
        color: var(--text-gray);
        font-weight: 600;
    }
    .loja-table-wrap {
        width: 100%;
        overflow-x: auto;
        margin-top: 1.25rem;
    }
    .loja-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
    }
    .loja-table th, .loja-table td {
        text-align: left;
        padding: 0.75rem;
        border-bottom: 1px solid var(--border-color);
    }
    .loja-table th {
        color: var(--text-gray);
        font-weight: 700;
    }
    .loja-hidden {
        display: none !important;
    }
    .loja-toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: var(--bg-card);
        border: 1px solid var(--primary);
        color: var(--text-white);
        padding: 0.85rem 1.15rem;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
        font-size: 0.88rem;
        font-weight: 600;
        z-index: 9999;
    }
</style>

<div class="main-container">
    <main class="main-content">
        <div class="loja-header-wrap">
            <div>
                <h1 style="margin: 0; font-size: 1.8rem; font-weight: 700; color: var(--text-white);">
                    <?php echo $tipo_usuario === 'admin_empresa' ? 'Loja & Vouchers' : 'Loja'; ?>
                    <span class="badge-demo">PROTÓTIPO FRONT-END</span>
                </h1>
                <p style="margin: 0.35rem 0 0 0; color: var(--text-gray); font-size: 0.94rem;">
                    <?php echo $tipo_usuario === 'admin_empresa'
                        ? 'Cadastre vouchers apenas para as opções de produtos disponíveis (simulação visual sem integração com lojas parceiras).'
                        : 'Explore os produtos disponíveis na loja e simule o resgate dos vouchers cadastrados pela empresa.'; ?>
                </p>
            </div>

            <?php if ($tipo_usuario !== 'admin_empresa'): ?>
                <div class="points-pill" id="userPointsDisplay">
                    <i class="bi bi-coin" style="color: #f59e0b;"></i>
                    Saldo simulado: <?php echo (int)$saldo_inicial; ?> pts
                </div>
            <?php endif; ?>
        </div>

        <!-- Abas internas -->
        <div class="loja-tabs">
            <?php if ($tipo_usuario === 'admin_empresa'): ?>
                <button class="loja-tab-btn active" data-view="company" type="button">
                    <i class="bi bi-ticket-perforated-fill"></i> Inserir Vouchers nos Produtos
                </button>
                <button class="loja-tab-btn" data-view="store" type="button">
                    <i class="bi bi-eye-fill"></i> Prévia da Loja (Visão Usuário)
                </button>
            <?php else: ?>
                <button class="loja-tab-btn active" data-view="store" type="button">
                    <i class="bi bi-shop"></i> Produtos Disponíveis
                </button>
                <button class="loja-tab-btn" data-view="history" type="button">
                    <i class="bi bi-ticket-detailed"></i> Meus Vouchers Simulados
                </button>
            <?php endif; ?>
        </div>

        <!-- ==================== VISÃO LOJA (USUÁRIO / PRÉVIA EMPRESA) ==================== -->
        <section id="view-store" class="<?php echo $tipo_usuario === 'admin_empresa' ? 'loja-hidden' : ''; ?>">
            <div class="loja-filters">
                <input type="text" id="searchInput" class="loja-input" placeholder="Buscar produto ou voucher na loja..." />
                <select id="categoryFilter" class="loja-select">
                    <option value="all">Todas as categorias</option>
                    <option value="Streaming & Música">Streaming & Música</option>
                    <option value="Alimentação">Alimentação</option>
                    <option value="Livros & Cursos">Livros & Cursos</option>
                    <option value="Bem-estar & Lazer">Bem-estar & Lazer</option>
                </select>
            </div>

            <div class="loja-grid" id="storeGrid"></div>
        </section>

        <!-- ==================== VISÃO EMPRESA: INSERIR VOUCHERS ==================== -->
        <section id="view-company" class="stat-card <?php echo $tipo_usuario === 'admin_empresa' ? '' : 'loja-hidden'; ?>">
            <h2 style="margin: 0; font-size: 1.2rem; color: var(--text-white);">
                Disponibilizar Vouchers para Produtos da Loja
            </h2>
            <p style="margin: 0.35rem 0 0 0; color: var(--text-gray); font-size: 0.88rem;">
                A empresa insere somente vouchers vinculados às opções de produtos pré-definidas abaixo (funcionamento exclusivamente no front-end).
            </p>

            <form id="voucherForm" class="loja-form-grid">
                <div class="loja-field">
                    <label for="productSelect">Produto Disponível na Loja</label>
                    <select id="productSelect" class="loja-select" required></select>
                </div>

                <div class="loja-field">
                    <label for="voucherPoints">Custo em Pontos</label>
                    <input id="voucherPoints" class="loja-input" type="number" min="1" value="20" required style="min-width: 0;" />
                </div>

                <div class="loja-field">
                    <label for="voucherQty">Qtd. de Vouchers</label>
                    <input id="voucherQty" class="loja-input" type="number" min="1" value="3" required style="min-width: 0;" />
                </div>

                <div class="loja-field">
                    <label for="voucherCode">Código do Voucher (exemplo)</label>
                    <input id="voucherCode" class="loja-input" type="text" placeholder="Ex.: EDUCA-VCH-2026" style="min-width: 0;" />
                </div>

                <div class="loja-field" style="justify-content: flex-end;">
                    <button type="submit" class="btn-personalizado" style="font-weight: 700;">
                        + Adicionar Vouchers
                    </button>
                </div>
            </form>

            <div class="loja-table-wrap">
                <table class="loja-table">
                    <thead>
                        <tr>
                            <th>Produto da Loja</th>
                            <th>Categoria</th>
                            <th>Custo</th>
                            <th>Vouchers Inseridos</th>
                            <th>Código Exemplo</th>
                        </tr>
                    </thead>
                    <tbody id="companyTableBody"></tbody>
                </table>
            </div>
        </section>

        <!-- ==================== VISÃO USUÁRIO: HISTÓRICO SIMULADO ==================== -->
        <section id="view-history" class="stat-card loja-hidden">
            <h2 style="margin: 0; font-size: 1.2rem; color: var(--text-white);">
                Meus Vouchers Simulados
            </h2>
            <p style="margin: 0.35rem 0 0 0; color: var(--text-gray); font-size: 0.88rem;">
                Registro visual gerado apenas nesta sessão para demonstração da interface.
            </p>

            <div class="loja-table-wrap">
                <table class="loja-table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Pontos</th>
                            <th>Código Simulado</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody"></tbody>
                </table>
            </div>
        </section>
    </main>
</div>

</div><!-- fecha wrapper flex aberto em includes/header.php -->

<div class="loja-toast loja-hidden" id="lojaToast"></div>

<script>
    const isAdminEmpresa = <?php echo $tipo_usuario === 'admin_empresa' ? 'true' : 'false'; ?>;
    let userPoints = <?php echo (int)$saldo_inicial; ?>;

    // Catálogo fixo de opções de produtos disponíveis na loja (a empresa insere vouchers apenas para estas opções)
    let catalogoProdutos = [
        {
            id: 1,
            name: "Assinatura Spotify Premium (1 Mês)",
            category: "Streaming & Música",
            description: "Acesso individual sem anúncios por 30 dias via voucher digital.",
            points: 20,
            qty: 3,
            code: "SPOT-EDUCA-01"
        },
        {
            id: 2,
            name: "Vale-Café & Lanche Corporativo",
            category: "Alimentação",
            description: "Voucher para consumo em cafeterias e lanchonetes conveniadas.",
            points: 15,
            qty: 5,
            code: "CAFE-EDUCA-15"
        },
        {
            id: 3,
            name: "Vale-Livro / E-book Técnico",
            category: "Livros & Cursos",
            description: "Crédito para aquisição de livros físicos ou digitais de tecnologia e gestão.",
            points: 30,
            qty: 2,
            code: "BOOK-EDUCA-30"
        },
        {
            id: 4,
            name: "Ingresso Cinema 2D",
            category: "Bem-estar & Lazer",
            description: "Voucher individual válido de segunda a domingo em salas padrão.",
            points: 25,
            qty: 0,
            code: "CINE-EDUCA-25"
        },
        {
            id: 5,
            name: "Vale-Delivery Refeição",
            category: "Alimentação",
            description: "Crédito digital para pedidos de almoço ou jantar.",
            points: 40,
            qty: 1,
            code: "FOOD-EDUCA-40"
        }
    ];

    let claimedVouchers = [];

    function showToast(msg) {
        const toast = document.getElementById("lojaToast");
        toast.textContent = msg;
        toast.classList.remove("loja-hidden");
        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => toast.classList.add("loja-hidden"), 2800);
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function renderPoints() {
        const el = document.getElementById("userPointsDisplay");
        if (el) {
            el.innerHTML = `<i class="bi bi-coin" style="color: #f59e0b;"></i> Saldo simulado: ${userPoints} pts`;
        }
    }

    function populateProductSelect() {
        const select = document.getElementById("productSelect");
        if (!select) return;
        select.innerHTML = catalogoProdutos
            .map(p => `<option value="${p.id}">${escapeHtml(p.name)} (${escapeHtml(p.category)})</option>`)
            .join("");
    }

    function renderStore() {
        const grid = document.getElementById("storeGrid");
        const query = (document.getElementById("searchInput")?.value || "").trim().toLowerCase();
        const cat = document.getElementById("categoryFilter")?.value || "all";

        const filtrados = catalogoProdutos.filter(item => {
            const matchCat = cat === "all" || item.category === cat;
            const matchText =
                item.name.toLowerCase().includes(query) ||
                item.description.toLowerCase().includes(query) ||
                item.category.toLowerCase().includes(query);
            return matchCat && matchText;
        });

        if (filtrados.length === 0) {
            grid.innerHTML = `<div class="stat-card" style="grid-column: 1 / -1; color: var(--text-gray);">Nenhum produto encontrado para o filtro selecionado.</div>`;
            return;
        }

        grid.innerHTML = filtrados.map(item => {
            const disponivel = item.qty > 0;
            const temSaldo = userPoints >= item.points;

            let btnLabel = "Simular Resgate";
            let btnDisabled = false;

            if (isAdminEmpresa) {
                btnLabel = "Prévia (Modo Empresa)";
                btnDisabled = true;
            } else if (!disponivel) {
                btnLabel = "Sem Vouchers";
                btnDisabled = true;
            } else if (!temSaldo) {
                btnLabel = "Pontos Insuficientes";
                btnDisabled = true;
            }

            return `
                <article class="loja-card">
                    <div>
                        <div class="loja-card-top">
                            <span class="loja-cat-tag">${escapeHtml(item.category)}</span>
                            <span class="loja-stock-tag ${disponivel ? "" : "empty"}">
                                ${disponivel ? `${item.qty} voucher(s) disponível(is)` : "Indisponível"}
                            </span>
                        </div>
                        <h3 style="margin: 0.35rem 0; color: var(--text-white); font-size: 1.05rem;">${escapeHtml(item.name)}</h3>
                        <p style="margin: 0; color: var(--text-gray); font-size: 0.85rem; line-height: 1.45;">
                            ${escapeHtml(item.description)}
                        </p>
                        <div class="loja-voucher-box">
                            ${disponivel ? "Voucher liberado após simulação de troca" : "Aguardando empresa inserir vouchers"}
                        </div>
                    </div>
                    <div class="loja-card-footer">
                        <span class="loja-cost">${item.points} pts</span>
                        <button
                            type="button"
                            class="btn-personalizado"
                            style="padding: 0.55rem 0.95rem; font-size: 0.84rem; font-weight: 700; ${btnDisabled ? "opacity: 0.45; cursor: not-allowed;" : ""}"
                            ${btnDisabled ? "disabled" : ""}
                            onclick="simularResgate(${item.id})"
                        >
                            ${btnLabel}
                        </button>
                    </div>
                </article>
            `;
        }).join("");
    }

    function renderCompanyTable() {
        const tbody = document.getElementById("companyTableBody");
        if (!tbody) return;
        tbody.innerHTML = catalogoProdutos.map(p => `
            <tr>
                <td style="color: var(--text-white); font-weight: 600;">${escapeHtml(p.name)}</td>
                <td style="color: var(--text-gray);">${escapeHtml(p.category)}</td>
                <td style="color: var(--primary); font-weight: 700;">${p.points} pts</td>
                <td style="color: ${p.qty > 0 ? '#10b981' : '#ef4444'}; font-weight: 700;">${p.qty}</td>
                <td><code style="color: var(--text-gray);">${escapeHtml(p.code)}</code></td>
            </tr>
        `).join("");
    }

    function renderHistory() {
        const tbody = document.getElementById("historyTableBody");
        if (!tbody) return;
        if (claimedVouchers.length === 0) {
            tbody.innerHTML = `<tr><td colspan="3" style="color: var(--text-gray);">Nenhum voucher resgatado na simulação ainda.</td></tr>`;
            return;
        }
        tbody.innerHTML = claimedVouchers.map(v => `
            <tr>
                <td style="color: var(--text-white); font-weight: 600;">${escapeHtml(v.name)}</td>
                <td style="color: var(--primary); font-weight: 700;">${v.points} pts</td>
                <td><code style="color: #10b981; font-weight: 700;">${escapeHtml(v.code)}</code></td>
            </tr>
        `).join("");
    }

    window.simularResgate = function(id) {
        const item = catalogoProdutos.find(p => p.id === id);
        if (!item || item.qty <= 0 || userPoints < item.points) return;

        item.qty -= 1;
        userPoints -= item.points;
        const codigoGerado = `${item.code}-${Math.floor(100 + Math.random() * 900)}`;

        claimedVouchers.unshift({
            name: item.name,
            points: item.points,
            code: codigoGerado
        });

        renderPoints();
        renderStore();
        renderCompanyTable();
        renderHistory();
        showToast(`Resgate simulado! Código: ${codigoGerado}`);
    };

    const voucherForm = document.getElementById("voucherForm");
    if (voucherForm) {
        voucherForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const prodId = parseInt(document.getElementById("productSelect").value, 10);
            const points = parseInt(document.getElementById("voucherPoints").value, 10) || 20;
            const qty = parseInt(document.getElementById("voucherQty").value, 10) || 1;
            const codeInput = document.getElementById("voucherCode").value.trim();

            const produto = catalogoProdutos.find(p => p.id === prodId);
            if (!produto) return;

            produto.points = points;
            produto.qty += qty;
            if (codeInput) {
                produto.code = codeInput;
            }

            renderStore();
            renderCompanyTable();
            showToast(`${qty} voucher(s) adicionado(s) em "${produto.name}" (simulação).`);
        });
    }

    document.querySelectorAll(".loja-tab-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelectorAll(".loja-tab-btn").forEach(b => b.classList.remove("active"));
            btn.classList.add("active");

            const view = btn.dataset.view;
            document.getElementById("view-store")?.classList.toggle("loja-hidden", view !== "store");
            document.getElementById("view-company")?.classList.toggle("loja-hidden", view !== "company");
            document.getElementById("view-history")?.classList.toggle("loja-hidden", view !== "history");
        });
    });

    document.getElementById("searchInput")?.addEventListener("input", renderStore);
    document.getElementById("categoryFilter")?.addEventListener("change", renderStore);

    populateProductSelect();
    renderPoints();
    renderStore();
    renderCompanyTable();
    renderHistory();
</script>

</body>
</html>
