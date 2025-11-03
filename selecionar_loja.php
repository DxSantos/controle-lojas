<?php
require 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loja_id'])) {
    $loja_id = (int) $_POST['loja_id'];

    $stmt = $pdo->prepare("SELECT id, nome FROM lojas WHERE id = ?");
    $stmt->execute([$loja_id]);
    $loja = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($loja) {
        $_SESSION['loja_id'] = $loja['id'];
        $_SESSION['loja_nome'] = $loja['nome'];
        header("Location: form_quantidade.php");
        exit;
    }
}

header("Location: form_quantidade.php");
exit;
