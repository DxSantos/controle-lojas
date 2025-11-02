<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Configuração do Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Filtro de tipo
$tipo_id = !empty($_GET['tipo_id']) ? (int)$_GET['tipo_id'] : null;

// Query com filtro
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

// Agrupar por tipo
$tipos = [];
foreach ($dados as $d) {
    $tipos[$d['tipo_nome'] ?: 'Sem Tipo'][] = $d;
}

// HTML para PDF
$html = '
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<style>
    body {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        background-color: #f8f9fa;
        color: #333;
        margin: 25px;
    }
    h1 {
        text-align: center;
        color: #333;
        background-color: #e9ecef;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 25px;
    }
    .container {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }
    .card-tipo {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        width: 48%;
        min-width: 300px;
        box-sizing: border-box;
        margin-bottom: 15px;
    }
    .card-header {
        background: #007bff;
        color: white;
        font-weight: bold;
        font-size: 16px;
        padding: 10px 12px;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }
    .card-body {
        padding: 10px 15px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }
    th, td {
        padding: 6px 4px;
        text-align: left;
    }
    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .saldo {
        font-weight: bold;
        text-align: right;
    }
    .verde { color: #28a745; }
    .vermelho { color: #dc3545; }
    .cinza { color: #6c757d; }
    footer {
        text-align: center;
        font-size: 12px;
        color: #777;
        margin-top: 30px;
    }
</style>
</head>
<body>

<h1>Relatório de Saldo por Tipo</h1>
<div class="container">
';

foreach ($tipos as $tipo_nome => $produtos) {
    $html .= '
    <div class="card-tipo">
        <div class="card-header">' . htmlspecialchars($tipo_nome) . '</div>
        <div class="card-body">
            <table>';
    foreach ($produtos as $p) {
        $classeSaldo = ($p['saldo'] > 0) ? 'verde' : (($p['saldo'] < 0) ? 'vermelho' : 'cinza');
        $html .= '
                <tr>
                    <td>' . htmlspecialchars($p['nome_produto'] ?? 'Produto não identificado') . '</td>
                    <td class="saldo ' . $classeSaldo . '">' . number_format($p['saldo'], 0, ',', '.') . '</td>
                </tr>';
    }
    $html .= '
            </table>
        </div>
    </div>';
}

$html .= '
</div>
<footer>
    Gerado em ' . date('d/m/Y H:i') . ' — Sistema de Controle de Estoque
</footer>
</body>
</html>
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('relatorio_saldo_por_tipo.pdf', ['Attachment' => false]);
exit;
?>
