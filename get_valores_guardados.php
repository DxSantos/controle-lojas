<?php
require 'config.php';
header('Content-Type: application/json; charset=utf-8');

$tipo = $_GET['tipo'] ?? '';
$tiposValidos = ['vendas', 'envios', 'estoque'];

if (!in_array($tipo, $tiposValidos)) {
    echo json_encode(['status' => 'erro', 'msg' => 'Tipo inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT produto_id, COALESCE(SUM(quantidade),0) AS total
        FROM valores_guardados
        WHERE tipo = ?
        GROUP BY produto_id
    ");
    $stmt->execute([$tipo]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [];
    foreach ($rows as $r) {
        $result[(int)$r['produto_id']] = (float)$r['total'];
    }

    echo json_encode(['status' => 'ok', 'tipo' => $tipo, 'valores' => $result]);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
