<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE){
    session_start();
}

header('Content-Type: application/json');

$usuario_id = $_SESSION['usuario_id'] ?? null;
$loja_id = $_SESSION['loja_id'] ?? null;

if (!$usuario_id || !$loja_id){
    echo json_encode(['status'=>'erro']);
    exit;
}

$pdo->prepare("
    DELETE FROM valores_guardados_contagem 
    WHERE usuario_id = ? AND loja_id = ?
")->execute([$usuario_id, $loja_id]);

echo json_encode(['status'=>'ok']);
