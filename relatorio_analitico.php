<?php
require 'config.php';
include 'includes/header.php';
date_default_timezone_set('America/Sao_Paulo');
require 'includes/verifica_permissao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Bloqueia se o usuário não tiver permissão "relatorio_analitico"
if (!verificaPermissao('analitico')) {
    echo "<div class='alert alert-danger m-4 text-center'>
            🚫 Você não tem permissão para acessar esta página.
          </div>";
    include 'includes/footer.php';
    exit;
}

// 🔒 Filtros
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$loja_id = $_GET['loja_id'] ?? '';
$tipo_id = $_GET['tipo_id'] ?? '';
$produto_id = $_GET['produto_id'] ?? '';

// 🔹 Carrega listas
$lojas = $pdo->query("SELECT id, nome FROM lojas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$tipos = $pdo->query("SELECT id, nome FROM tipos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$produtos = $pdo->query("SELECT id, nome FROM produtos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Monta filtro base
$filtroLoja = $loja_id ? "AND loja_id = $loja_id" : "";
$filtroData = "AND DATE(data) BETWEEN '$data_inicio' AND '$data_fim'";

// 🔹 Totais gerais
function somaTabela($pdo, $tabela, $filtroLoja, $filtroData) {
    $stmt = $pdo->query("SELECT SUM(quantidade) AS total FROM $tabela WHERE 1=1 $filtroLoja $filtroData");
    return (int) $stmt->fetchColumn();
}

$totalVendas = somaTabela($pdo, "controle_vendas", $filtroLoja, $filtroData);
$totalEnvios = somaTabela($pdo, "controle_envios", $filtroLoja, $filtroData);
$totalEstoque = somaTabela($pdo, "controle_estoque", $filtroLoja, $filtroData);
$totalInventario = $pdo->query("SELECT SUM(saldo_inventario) FROM inventario_log WHERE DATE(data_inventario) BETWEEN '$data_inicio' AND '$data_fim'")->fetchColumn() ?? 0;

$saldoAtual = $totalInventario + $totalEstoque + $totalEnvios - $totalVendas;

// 🔹 Dados diários
$sqlMovimentos = "
    SELECT DATE(data) AS dia,
           SUM(CASE WHEN 'controle_vendas' THEN 0 ELSE 0 END) AS dummy,
           (SELECT COALESCE(SUM(quantidade),0) FROM controle_vendas v WHERE DATE(v.data)=DATE(m.data) $filtroLoja) AS vendas,
           (SELECT COALESCE(SUM(quantidade),0) FROM controle_envios e WHERE DATE(e.data)=DATE(m.data) $filtroLoja) AS envios,
           (SELECT COALESCE(SUM(quantidade),0) FROM controle_estoque es WHERE DATE(es.data)=DATE(m.data) $filtroLoja) AS estoque
    FROM controle_vendas m
    WHERE DATE(m.data) BETWEEN '$data_inicio' AND '$data_fim'
    GROUP BY DATE(m.data)
    ORDER BY DATE(m.data)
";
$dados = $pdo->query($sqlMovimentos)->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Top produtos (por vendas)
$topProdutos = $pdo->query("
    SELECT p.nome, SUM(v.quantidade) AS total
    FROM controle_vendas v
    INNER JOIN produtos p ON p.id = v.produto_id
    WHERE DATE(v.data) BETWEEN '$data_inicio' AND '$data_fim' $filtroLoja
    GROUP BY p.id ORDER BY total DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Estoque por loja
$estoquePorLoja = $pdo->query("
    SELECT l.nome, SUM(e.quantidade) AS total
    FROM controle_estoque e
    INNER JOIN lojas l ON l.id = e.loja_id
    WHERE DATE(e.data) BETWEEN '$data_inicio' AND '$data_fim'
    GROUP BY l.id ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <h3 class="mb-4 text-center">📈 Análise de Resultados por Loja / Período</h3>

    <!-- FILTROS -->
    <form method="GET" class="row g-3 mb-4 border p-3 bg-light rounded shadow-sm">
        <div class="col-md-3">
            <label class="form-label">Data Início</label>
            <input type="date" name="data_inicio" value="<?= $data_inicio ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Data Fim</label>
            <input type="date" name="data_fim" value="<?= $data_fim ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Loja</label>
            <select name="loja_id" class="form-select">
                <option value="">Todas</option>
                <?php foreach ($lojas as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= ($l['id'] == $loja_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($l['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 align-self-end">
            <button type="submit" class="btn btn-primary w-100">Aplicar</button>
        </div>
    </form>

    <!-- KPIs -->
    <div class="row text-center mb-4">
        <?php
        $cards = [
            ['Vendas', $totalVendas, 'bg-primary'],
            ['Envios', $totalEnvios, 'bg-warning text-dark'],
            ['Estoque', $totalEstoque, 'bg-success'],
            ['Inventário', $totalInventario, 'bg-info text-dark'],
            ['Saldo Atual', $saldoAtual, 'bg-dark']
        ];
        foreach ($cards as [$titulo, $valor, $classe]):
        ?>
        <div class="col-md-2 mb-3">
            <div class="card <?= $classe ?> text-white shadow-sm">
                <div class="card-body">
                    <h6><?= $titulo ?></h6>
                    <h4><?= number_format($valor, 0, ',', '.') ?></h4>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- GRÁFICOS -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3 text-center">📊 Movimentações Diárias</h5>
                    <canvas id="graficoMovimentos" height="120"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="text-center mb-3">🏪 Estoque por Loja</h5>
                    <canvas id="graficoLoja"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="text-center mb-3">🔥 Top 10 Produtos Vendidos</h5>
                    <canvas id="graficoTopProdutos"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="text-center mb-3">📦 Distribuição de Movimentos</h5>
                    <canvas id="graficoPizza"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const movimentos = <?= json_encode($dados) ?>;
const topProdutos = <?= json_encode($topProdutos) ?>;
const estoqueLoja = <?= json_encode($estoquePorLoja) ?>;

// 1️⃣ Gráfico de linha de movimentações
new Chart(document.getElementById('graficoMovimentos'), {
    type: 'line',
    data: {
        labels: movimentos.map(i => i.dia),
        datasets: [
            { label: 'Vendas', data: movimentos.map(i => i.vendas), borderColor: '#0d6efd', fill: false },
            { label: 'Envios', data: movimentos.map(i => i.envios), borderColor: '#ffc107', fill: false },
            { label: 'Estoque', data: movimentos.map(i => i.estoque), borderColor: '#198754', fill: false }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

// 2️⃣ Gráfico de barras (estoque por loja)
new Chart(document.getElementById('graficoLoja'), {
    type: 'bar',
    data: {
        labels: estoqueLoja.map(i => i.nome),
        datasets: [{ label: 'Entradas de Estoque', data: estoqueLoja.map(i => i.total), backgroundColor: '#198754' }]
    }
});

// 3️⃣ Top produtos vendidos
new Chart(document.getElementById('graficoTopProdutos'), {
    type: 'bar',
    data: {
        labels: topProdutos.map(i => i.nome),
        datasets: [{ label: 'Vendas', data: topProdutos.map(i => i.total), backgroundColor: '#0d6efd' }]
    }
});

// 4️⃣ Pizza de proporções gerais
new Chart(document.getElementById('graficoPizza'), {
    type: 'doughnut',
    data: {
        labels: ['Vendas', 'Envios', 'Estoque'],
        datasets: [{
            data: [<?= $totalVendas ?>, <?= $totalEnvios ?>, <?= $totalEstoque ?>],
            backgroundColor: ['#0d6efd', '#ffc107', '#198754']
        }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php include 'includes/footer.php'; ?>
