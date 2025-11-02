<?php
require 'config.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=relatorio_saldo_" . date('Ymd_His') . ".xls");

$tipo_id = !empty($_GET['tipo_id']) ? (int)$_GET['tipo_id'] : null;

$sql = "
SELECT 
    t.nome AS tipo_nome,
    p.nome AS nome_produto,
    sp.saldo
FROM saldo_produtos sp
LEFT JOIN produtos p ON p.id = sp.produto_id
LEFT JOIN tipos t ON t.id = p.tipo
";

if ($tipo_id) {
    $sql .= " WHERE p.tipo = :tipo_id";
}

$sql .= " ORDER BY t.nome, p.nome";

$stmt = $pdo->prepare($sql);
if ($tipo_id) $stmt->bindValue(':tipo_id', $tipo_id, PDO::PARAM_INT);
$stmt->execute();
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr style='background-color:#007bff;color:#fff;'>
        <th>Tipo</th>
        <th>Produto</th>
        <th>Saldo</th>
      </tr>";

foreach ($dados as $d) {
    $cor = ($d['saldo'] > 0) ? '#28a745' : (($d['saldo'] < 0) ? '#dc3545' : '#6c757d');
    echo "<tr>
            <td>{$d['tipo_nome']}</td>
            <td>{$d['nome_produto']}</td>
            <td style='color:$cor;font-weight:bold;text-align:right;'>" . number_format($d['saldo'], 2, ',', '.') . "</td>
          </tr>";
}

echo "</table>";
?>
