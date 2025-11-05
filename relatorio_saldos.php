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

// Bloqueia se o usuário não tiver permissão "relatorio_saldos"
if (!verificaPermissao('saldos')) {
    echo "<div class='alert alert-danger m-4 text-center'>
            🚫 Você não tem permissão para acessar esta página.
          </div>";
    include 'includes/footer.php';
    exit;
}

// ----- MENSAGEM DE RETORNO -----
$msg = '';
if (!empty($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    $msg_tipo = $_SESSION['msg_tipo'] ?? 'info';
    unset($_SESSION['msg'], $_SESSION['msg_tipo']);
}



// ====== FILTROS ======
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$loja_id = $_GET['loja_id'] ?? '';
$produto_id = $_GET['produto_id'] ?? '';
$tipo_id = $_GET['tipo_id'] ?? '';

// ====== LISTAS DE FILTRO ======
$lojas = $pdo->query("SELECT id, nome FROM lojas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$tipos = $pdo->query("SELECT id, nome FROM tipos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$produtos = $pdo->query("SELECT id, nome FROM produtos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// ====== CONDIÇÕES DE FILTRO ======
$where = "WHERE 1=1";
$params = [];

if ($loja_id) {
    $where .= " AND l.id = :loja_id";
    $params[':loja_id'] = $loja_id;
}
if ($produto_id) {
    $where .= " AND p.id = :produto_id";
    $params[':produto_id'] = $produto_id;
}
if ($tipo_id) {
    $where .= " AND t.id = :tipo_id";
    $params[':tipo_id'] = $tipo_id;
}
if ($data_inicio && $data_fim) {
    $where .= " AND DATE(m.data) BETWEEN :data_inicio AND :data_fim";
    $params[':data_inicio'] = $data_inicio;
    $params[':data_fim'] = $data_fim;
}

// ====== QUERY UNIFICADA (somando por data e loja) ======
$sql = "
SELECT 
    DATE(m.data) AS data,
    l.nome AS loja_nome,
    p.nome AS produto_nome,
    t.nome AS tipo_nome,
    COALESCE(SUM(CASE WHEN m.tipo = 'inventario' THEN m.quantidade END), 0) AS inventario,
    COALESCE(SUM(CASE WHEN m.tipo = 'estoque' THEN m.quantidade END), 0) AS estoque,
    COALESCE(SUM(CASE WHEN m.tipo = 'envios' THEN m.quantidade END), 0) AS envios,
    COALESCE(SUM(CASE WHEN m.tipo = 'vendas' THEN m.quantidade END), 0) AS vendas,
    (
        COALESCE(SUM(CASE WHEN m.tipo = 'inventario' THEN m.quantidade END), 0)
        + COALESCE(SUM(CASE WHEN m.tipo = 'estoque' THEN m.quantidade END), 0)
        + COALESCE(SUM(CASE WHEN m.tipo = 'envios' THEN m.quantidade END), 0)
        - COALESCE(SUM(CASE WHEN m.tipo = 'vendas' THEN m.quantidade END), 0)
    ) AS saldo
FROM (
    SELECT 'inventario' AS tipo, il.produto_id, il.loja_id, il.saldo_inventario AS quantidade, il.data_inventario AS data FROM inventario_log il
    UNION ALL
    SELECT 'estoque', ce.produto_id, ce.loja_id, ce.quantidade, ce.data FROM controle_estoque ce
    UNION ALL
    SELECT 'envios', ce2.produto_id, ce2.loja_id, ce2.quantidade, ce2.data FROM controle_envios ce2
    UNION ALL
    SELECT 'vendas', cv.produto_id, cv.loja_id, cv.quantidade, cv.data FROM controle_vendas cv
) m
INNER JOIN produtos p ON m.produto_id = p.id
INNER JOIN tipos t ON p.tipo = t.id
INNER JOIN lojas l ON m.loja_id = l.id
$where
GROUP BY DATE(m.data), l.id, p.id
ORDER BY DATE(m.data) ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <h3 class="mb-4">📋 Relatório de Movimentações (Inventário, Envios, Estoque, Vendas e Saldo)</h3>

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
                    <option value="<?= $l['id'] ?>" <?= ($l['id'] == $loja_id) ? 'selected' : '' ?>>
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
                    <option value="<?= $t['id'] ?>" <?= ($t['id'] == $tipo_id) ? 'selected' : '' ?>>
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
                    <option value="<?= $p['id'] ?>" <?= ($p['id'] == $produto_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
        <div class="col-md-2 align-self-end">
            <a href="relatorio_movimentos.php" class="btn btn-secondary w-100">Limpar</a>
        </div>
    </form>

    <!-- RESULTADOS -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <?php if ($dados): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>Data</th>
                                <th>Loja</th>
                                <th>Produto</th>
                                <th>Tipo</th>
                                <th>Inventário</th>
                                <th>Estoque</th>
                                <th>Envios</th>
                                <th>Vendas</th>
                                <th class="text-success">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dados as $row): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($row['data'])) ?></td>
                                    <td><?= htmlspecialchars($row['loja_nome']) ?></td>
                                    <td><?= htmlspecialchars($row['produto_nome']) ?></td>
                                    <td><?= htmlspecialchars($row['tipo_nome']) ?></td>
                                    <td><?= (int)$row['inventario'] ?></td>
                                    <td><?= (int)$row['estoque'] ?></td>
                                    <td><?= (int)$row['envios'] ?></td>
                                    <td><?= (int)$row['vendas'] ?></td>
                                    <td><strong class="text-success"><?= (int)$row['saldo'] ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">Nenhum registro encontrado com os filtros aplicados.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
