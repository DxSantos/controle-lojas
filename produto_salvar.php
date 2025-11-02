<?php
require 'config.php';
session_start();

$id = $_POST['id'] ?? '';
$nome = strtoupper(trim($_POST['nome'] ?? ''));
$tipo = $_POST['tipo'] ?? '';
$ativo = isset($_POST['ativo']) ? 1 : 0; // <-- NOVO: define ativo ou inativo

if ($id) {
    // Atualiza produto existente
    $stmt = $pdo->prepare("UPDATE produtos SET nome = ?, tipo = ?, ativo = ? WHERE id = ?");
    $stmt->execute([$nome, $tipo, $ativo, $id]);

    $_SESSION['msg'] = 'Produto atualizado com sucesso!';
    $_SESSION['msg_tipo'] = 'success';
} else {
    // Insere novo produto (sempre ativo por padrão)
    $stmt = $pdo->prepare("INSERT INTO produtos (nome, tipo, ativo) VALUES (?, ?, 1)");
    $stmt->execute([$nome, $tipo]);

    $_SESSION['msg'] = 'Produto cadastrado com sucesso!';
    $_SESSION['msg_tipo'] = 'success';
}

header("Location: produto_cadastro.php");
exit;
?>
