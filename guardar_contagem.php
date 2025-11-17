<?php
require 'config.php';

if (session_status() === PHP_SESSION_NONE){
    session_start();
}

header('Content-Type: application/json');

$usuario_id = $_SESSION['usuario_id'] ?? null;
$loja_id = $_SESSION['loja_id'] ?? null;

if (!$usuario_id || !$loja_id){
    echo json_encode(['status'=>'erro','msg'=>'Usuário ou loja não definidos']);
    exit;
}

$dados = json_decode(file_get_contents("php://input"), true);

$quantidades = $dados['quantidades'] ?? [];

if (empty($quantidades)){
    echo json_encode(['status'=>'vazio']);
    exit;
}

try{
    $pdo->prepare("DELETE FROM valores_guardados_contagem WHERE usuario_id=? AND loja_id=?")
        ->execute([$usuario_id, $loja_id]);

    $stmt = $pdo->prepare("
        INSERT INTO valores_guardados_contagem (usuario_id, loja_id, produto_id, quantidade, data_registro)
        VALUES (?, ?, ?, ?, NOW())
    ");

    foreach($quantidades as $produto_id => $qtd){
        if ($qtd > 0){
            $stmt->execute([$usuario_id, $loja_id, $produto_id, $qtd]);
        }
    }

    echo json_encode(['status'=>'ok']);
}
catch(Exception $e){
    echo json_encode(['status'=>'erro','msg'=>$e->getMessage()]);
}
