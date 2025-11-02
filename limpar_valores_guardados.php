<?php
require 'config.php';
header('Content-Type: application/json');

$tipo = $_POST['tipo'] ?? '';
$tiposValidos = ['vendas', 'envios', 'estoque'];

if (!in_array($tipo, $tiposValidos)) {
    echo json_encode(['status' => 'erro', 'msg' => 'Tipo inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM valores_guardados WHERE tipo = ?");
    $stmt->execute([$tipo]);
    echo json_encode(['status' => 'ok']);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
