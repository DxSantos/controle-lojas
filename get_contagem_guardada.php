<?php
require 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$usuario_id = $_SESSION['usuario_id'] ?? null;
$loja_id    = $_SESSION['loja_id'] ?? null;

// 🔒 Segurança
if (!$usuario_id || !$loja_id) {
    echo json_encode([
        'status' => 'erro',
        'valores' => []
    ]);
    exit;
}

// 🔍 Busca valores guardados
$stmt = $pdo->prepare("
    SELECT produto_id, quantidade
    FROM valores_guardados_contagem
    WHERE usuario_id = ? AND loja_id = ?
");
$stmt->execute([$usuario_id, $loja_id]);

// Converte para array no formato: pid => quantidade
$valores = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $valores[$row['produto_id']] = $row['quantidade'];
}

// Retorno para o JS
echo json_encode([
    'status'  => 'ok',
    'valores' => $valores
]);
