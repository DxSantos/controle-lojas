<?php
require 'config.php';
if (empty($_SESSION['usuario_id'])) {
    header("Location: sections/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - Controle de Lojas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<section class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Bem-vindo, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</h3>
        <a href="sections/logout.php" class="btn btn-outline-danger">Sair</a>
    </div>

    <div class="card p-4 shadow-sm">
        <h5>Sistema de Controle de Lojas</h5>
        <p class="text-muted">Escolha uma opção no menu superior para continuar.</p>
    </div>
</section>
</body>
</html>
