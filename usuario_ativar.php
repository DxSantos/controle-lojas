<?php
require 'config.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: usuarios_lista.php?msg=Usuário inválido");
    exit;
}

$stmt = $pdo->prepare("UPDATE usuarios SET ativo = 1 WHERE id = ?");
$stmt->execute([$id]);

header("Location: usuarios_lista.php?msg=Usuário reativado com sucesso!");
exit;
?>
