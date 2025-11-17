<?php
require 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$loja_id = $_SESSION['loja_id'] ?? null;
if (!$loja_id) {
    die("<p class='text-danger'>Nenhuma loja selecionada.</p>");
}

$filtro_produto = $_GET['produto'] ?? "";

// Consulta igual ao relatório
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

// Montar tabela
?>
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
                $classe = ($dif > 0 ? "text-success fw-bold" : ($dif < 0 ? "text-danger fw-bold" : "text-secondary fw-bold"));
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
