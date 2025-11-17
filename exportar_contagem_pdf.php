<?php
require 'config.php';
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$loja_id = $_SESSION['loja_id'] ?? null;
if (!$loja_id) {
    die("Loja não selecionada.");
}

$filtro_produto = $_GET['produto'] ?? "";

// Consulta igual do relatório
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

// --- MONTA HTML PARA O PDF ---
$html = "
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #000; padding: 6px; }
    th { background: #f0f0f0; font-weight: bold; }
    .verde { color: green; font-weight: bold; }
    .vermelho { color: red; font-weight: bold; }
    .cinza { color: #666; font-weight: bold; }
</style>

<h2 style='text-align:center;'>Relatório de Contagem</h2>
<p><strong>Loja:</strong> {$loja_id}</p>
";

$html .= "
<table>
    <thead>
        <tr>
            <th>Produto</th>
            <th>Última</th>
            <th>Data</th>
            <th>Penúltima</th>
            <th>Data</th>
            <th>Diferença</th>
        </tr>
    </thead>
    <tbody>
";

foreach ($dados as $d) {
    $dif = $d['diferenca'];
    $classe = "cinza";
    if ($dif > 0) $classe = "verde";
    elseif ($dif < 0) $classe = "vermelho";

    $html .= "
        <tr>
            <td>{$d['produto']}</td>
            <td>{$d['ultima_qtd']}</td>
            <td>{$d['ultima_data']}</td>
            <td>{$d['penultima_qtd']}</td>
            <td>{$d['penultima_data']}</td>
            <td class='$classe'>{$dif}</td>
        </tr>
    ";
}

$html .= "</tbody></table>";

// --- GERAR PDF ---
$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape'); // horizontal (melhor para relatórios)
$dompdf->render();

// Baixar PDF
$dompdf->stream("relatorio_contagem.pdf", ["Attachment" => true]);
exit;
