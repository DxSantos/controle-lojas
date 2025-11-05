<?php
require 'config.php';
include 'includes/header.php';
require 'includes/verifica_permissao.php';
date_default_timezone_set('America/Sao_Paulo');


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Bloqueia se o usuário não tiver permissão "Relatório de Dashboard"
if (!verificaPermissao('dashboard')) {
    echo "<div class='alert alert-danger m-4 text-center'>
            🚫 Você não tem permissão para acessar esta página.
          </div>";
    include 'includes/footer.php';
    exit;
}

// ====== FILTROS ======
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$loja_id = $_GET['loja_id'] ?? '';
$tipo_id = $_GET['tipo_id'] ?? '';
$produto_id = $_GET['produto_id'] ?? '';

// ====== LISTAS ======
$lojas = $pdo->query("SELECT id, nome FROM lojas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$tipos = $pdo->query("SELECT id, nome FROM tipos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$produtos = $pdo->query("SELECT id, nome FROM produtos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// ====== CONDIÇÕES ======
$where = "WHERE 1=1";
$params = [];
if ($data_inicio && $data_fim) { $where .= " AND DATE(c.data) BETWEEN :inicio AND :fim"; $params[':inicio']=$data_inicio; $params[':fim']=$data_fim; }
if ($loja_id) { $where .= " AND c.loja_id = :loja"; $params[':loja']=$loja_id; }
if ($tipo_id) { $where .= " AND p.tipo = :tipo"; $params[':tipo']=$tipo_id; }
if ($produto_id) { $where .= " AND p.id = :produto"; $params[':produto']=$produto_id; }

// ====== BUSCA DADOS AGRUPADOS ======
$sql = "
    SELECT DATE(c.data) AS data_ref,
           SUM(CASE WHEN src='vendas' THEN c.quantidade ELSE 0 END) AS vendas,
           SUM(CASE WHEN src='envios' THEN c.quantidade ELSE 0 END) AS envios,
           SUM(CASE WHEN src='estoque' THEN c.quantidade ELSE 0 END) AS estoque,
           (SUM(CASE WHEN src='estoque' THEN c.quantidade ELSE 0 END)
           + SUM(CASE WHEN src='envios' THEN c.quantidade ELSE 0 END)
           - SUM(CASE WHEN src='vendas' THEN c.quantidade ELSE 0 END)) AS saldo
    FROM (
        SELECT 'vendas' AS src, data, quantidade, loja_id, produto_id FROM controle_vendas
        UNION ALL
        SELECT 'envios', data, quantidade, loja_id, produto_id FROM controle_envios
        UNION ALL
        SELECT 'estoque', data, quantidade, loja_id, produto_id FROM controle_estoque
    ) AS c
    INNER JOIN produtos p ON p.id = c.produto_id
    $where
    GROUP BY DATE(c.data)
    ORDER BY DATE(c.data)
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ====== TOP PRODUTOS ======
$sqlTop = "
    SELECT p.nome AS produto, l.nome AS loja, SUM(cv.quantidade) AS total_vendas
    FROM controle_vendas cv
    INNER JOIN produtos p ON cv.produto_id = p.id
    INNER JOIN lojas l ON cv.loja_id = l.id
    INNER JOIN tipos t ON p.tipo = t.id
    WHERE 1=1
";
if ($data_inicio && $data_fim) $sqlTop .= " AND DATE(cv.data) BETWEEN :inicio AND :fim";
if ($loja_id) $sqlTop .= " AND cv.loja_id = :loja";
if ($tipo_id) $sqlTop .= " AND p.tipo = :tipo";
if ($produto_id) $sqlTop .= " AND p.id = :produto";
$sqlTop .= " GROUP BY p.id, l.id ORDER BY total_vendas DESC LIMIT 10";

$stmtTop = $pdo->prepare($sqlTop);
$stmtTop->execute($params);
$topProdutos = $stmtTop->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <h3 class="mb-4">📊 Painel Analítico de Movimentações</h3>

    <!-- FILTROS -->
    <form method="GET" class="row g-3 mb-4 border p-3 bg-light rounded shadow-sm">
        <div class="col-md-3">
            <label class="form-label">Data Início</label>
            <input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Data Fim</label>
            <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Loja</label>
            <select name="loja_id" class="form-select">
                <option value="">Todas</option>
                <?php foreach ($lojas as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= ($loja_id==$l['id'])?'selected':'' ?>>
                        <?= htmlspecialchars($l['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select name="tipo_id" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($tipos as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= ($tipo_id==$t['id'])?'selected':'' ?>>
                        <?= htmlspecialchars($t['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Produto</label>
            <select name="produto_id" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($produtos as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($produto_id==$p['id'])?'selected':'' ?>>
                        <?= htmlspecialchars($p['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
    </form>

    <!-- 1️⃣ BARRAS COM SCROLL E VALORES -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5 class="text-center mb-3">📦 Envios / Estoque / Vendas / Saldo por Data</h5>
            <div style="overflow-x:auto; white-space:nowrap;">
                <canvas id="graficoBarras" height="160" style="min-width:1000px;"></canvas>
            </div>
        </div>
    </div>

    <!-- 2️⃣ LINHA DE TENDÊNCIA -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5 class="text-center mb-3">📈 Tendência de Vendas</h5>
            <canvas id="graficoLinha" height="120" style="width:300px; padding: 10px;"></canvas>
        </div>
    </div>

    <!-- 3️⃣ TOP PRODUTOS -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5 class="text-center mb-3">🏆 Top 10 Produtos por Vendas (por Loja)</h5>
            <canvas id="graficoTop" height="60"></canvas>
        </div>
    </div>
</div>

<!-- Chart.js + DataLabels -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<script>
Chart.register(ChartDataLabels);

const movimentos = <?= json_encode($movimentos) ?>;
const topProdutos = <?= json_encode($topProdutos) ?>;

// === 1️⃣ BARRAS ===
new Chart(document.getElementById('graficoBarras').getContext('2d'), {
    type: 'bar',
    data: {
        labels: movimentos.map(m => m.data_ref),
        datasets: [
            { label: 'Envios', data: movimentos.map(m => m.envios), backgroundColor: '#ffc107' },
            { label: 'Estoque', data: movimentos.map(m => m.estoque), backgroundColor: '#198754' },
            { label: 'Vendas', data: movimentos.map(m => m.vendas), backgroundColor: '#0d6efd' },
            { label: 'Saldo', data: movimentos.map(m => m.saldo), backgroundColor: '#6f42c1' }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' },
            datalabels: {
                color: '#fff',
                font: { weight: 'bold', size: 11 },
                anchor: 'end',
                align: 'start',
                formatter: v => v > 0 ? v : ''
            }
        },
        scales: {
            x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 } },
            y: { beginAtZero: true }
        }
    }
});

// === 2️⃣ LINHA ===
new Chart(document.getElementById('graficoLinha').getContext('2d'), {
    type: 'line',
    data: {
        labels: movimentos.map(m => m.data_ref),
        datasets: [{
            label: 'Vendas',
            data: movimentos.map(m => m.vendas),
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.3)',
            fill: true,
            tension: 0.3,
            pointBackgroundColor: '#0d6efd'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            datalabels: {
                align: 'top',
                anchor: 'end',
                color: '#000',
                font: { size: 10 },
                formatter: v => v > 0 ? v : ''
            }
        },
        scales: { y: { beginAtZero: true } }
    }
});

// === 3️⃣ TOP PRODUTOS ===
new Chart(document.getElementById('graficoTop').getContext('2d'), {
    type: 'bar',
    data: {
        labels: topProdutos.map(t => t.produto + ' (' + t.loja + ')'),
        datasets: [{
            label: 'Total de Vendas',
            data: topProdutos.map(t => t.total_vendas),
            backgroundColor: '#0d6efd'
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
            legend: { display: false },
            datalabels: {
                color: '#fff',
                anchor: 'end',
                align: 'right',
                font: { size: 11, weight: 'bold' },
                formatter: v => v > 0 ? v : ''
            }
        },
        scales: { x: { beginAtZero: true } }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
