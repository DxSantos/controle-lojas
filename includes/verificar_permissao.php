<?php
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

function usuario_tem_permissao($chave) {
    global $pdo;
    $usuario_id = $_SESSION['usuario_id'] ?? 0;

    $stmt = $pdo->prepare("
        SELECT 1
        FROM usuario_permissoes up
        JOIN permissoes p ON p.id = up.permissao_id
        WHERE up.usuario_id = ? AND p.chave = ?
        LIMIT 1
    ");
    $stmt->execute([$usuario_id, $chave]);
    return $stmt->fetchColumn() ? true : false;
}
