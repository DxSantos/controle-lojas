<?php
require 'config.php';
header('Content-Type: application/json');
date_default_timezone_set('America/Sao_Paulo');

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!$input) $input = $_POST;

$quantidades = $input['quantidades'] ?? [];
$tipo = $input['tipo'] ?? '';
$tiposValidos = ['vendas', 'envios', 'estoque'];

if (!in_array($tipo, $tiposValidos)) {
    echo json_encode(['status' => 'erro', 'msg' => 'Tipo inválido']);
    exit;
}

$codigo_guardado = 'VG-' . date('Ymd-His');

try {
    $pdo->beginTransaction();

    // Zera os valores guardados para o tipo atual
    $stmtDel = $pdo->prepare("DELETE FROM valores_guardados WHERE tipo = ?");
    $stmtDel->execute([$tipo]);
    
    foreach ($quantidades as $produto_id => $qtd) {
        $produto_id = (int)$produto_id;
        $qtd = (float)$qtd;
        if ($qtd <= 0) continue;

        $stmt = $pdo->prepare("
            INSERT INTO valores_guardados (produto_id, tipo, quantidade, data_guardado, codigo_guardado)
            VALUES (?, ?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE quantidade = VALUES(quantidade), data_guardado = NOW()
        ");
        $stmt->execute([$produto_id, $tipo, $qtd, $codigo_guardado]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'ok', 'codigo' => $codigo_guardado]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
