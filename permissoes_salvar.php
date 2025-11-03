<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
    $nome = trim($_POST['nome']);
    $chave = trim($_POST['chave']);

    if ($id) {
        $stmt = $pdo->prepare("UPDATE permissoes SET nome = ?, chave = ? WHERE id = ?");
        $stmt->execute([$nome, $chave, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO permissoes (nome, chave) VALUES (?, ?)");
        $stmt->execute([$nome, $chave]);
    }

    header("Location: permissoes_cadastro.php");
    exit;
}
