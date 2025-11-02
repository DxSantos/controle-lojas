<?php
require 'config.php';

$id = $_POST['id'] ?? '';
$nome = strtoupper(trim($_POST['nome'] ?? ''));

if ($id) {
    $stmt = $pdo->prepare("UPDATE lojas SET nome = ? WHERE id = ?");
    $stmt->execute([$nome, $id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO lojas (nome) VALUES (?)");
    $stmt->execute([$nome]);
}

header("Location: loja_cadastro.php");
exit;
