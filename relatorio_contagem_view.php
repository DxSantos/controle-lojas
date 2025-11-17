<?php
require 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$loja_id = $_SESSION['loja_id'] ?? null;
if (!$loja_id) {
    die("Nenhuma loja selecionada.");
}

$filtro_produto = $_GET['produto'] ?? "";

// Consulta igual a do relatório
$sql = "
    SELECT 
        p.nome AS produto,
        v.ultima_qtd,
        v.penultima_qtd,
        (v.ultima_qtd - v.penultima_qtd) AS diferenca,
        DATE_FORMAT(v.ultima_data, '%d/%m/%Y %H:%i') AS ultima_data,
        DATE_FORMAT(v.penultima_data, '%d/%m/%Y %H:%i') AS penultima_data
    FROM view_duas_ultimas_contagens v
    JOIN produtos p ON p.id = v.produto_id
    JOIN tipos t ON t.id = p.tipo
    WHERE v.loja_id = ?
    AND t.visualizar_movimentos = 0
";

$params = [$loja_id];

if (!empty($filtro_produto)) {
    $sql .= " AND v.produto_id = ? ";
    $params[] = $filtro_produto;
}

$sql .= " ORDER BY p.nome ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Relatório de Contagem</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    body {
        padding: 25px;
        background: #f8f9fa;
    }
    .verde { color: green; font-weight: bold; }
    .vermelho { color: red; font-weight: bold; }
    .cinza { color: #666; font-weight: bold; }
</style>
</head>

<body>

<div class="container">

    <h2 class="mb-4 text-center">📊 Relatório de Contagem</h2>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Produto</th>
                <th>Penúltima</th>
                <th>Data</th>
                <th>Última</th>
                <th>Data</th>
                
                <th>Diferença</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dados as $d): ?>
                <?php
                    $dif = $d['diferenca'];
                    $classe = ($dif > 0 ? "verde" : ($dif < 0 ? "vermelho" : "cinza"));
                ?>
                <tr>
                    <td><?= htmlspecialchars($d['produto']) ?></td>
                    <td><?= $d['penultima_qtd'] ?></td>
                    <td><?= $d['penultima_data'] ?></td>
                    <td><?= $d['ultima_qtd'] ?></td>
                    <td><?= $d['ultima_data'] ?></td>
                    
                    <td class="<?= $classe ?>"><?= $dif ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>

</body>
</html>
