<?php
session_start();

// Se já estiver logado, vai direto para o painel principal
if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
    header("Location: form_quantidade.php"); // ou a página inicial do sistema
    exit;
}

// Se não estiver logado, redireciona para o login
header("Location: ./sections/login.php");
exit;
?>
