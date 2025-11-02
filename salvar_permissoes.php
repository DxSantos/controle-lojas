<?php
require 'config.php';
session_start();

// Apenas administradores podem alterar permissões
$stmt = $pdo->prepare("
    SELECT p.nome AS perfil_nome
    FROM usuarios u
    JOIN perfis p ON p.id = u.perfil_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['usuario_id']]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$perfil || strtolower($perfil['perfil_nome']) !== 'administrador') {
    die("<div class='alert alert-danger m-4'>Acesso negado!</div>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = (int)$_POST['usuario_id'];
    $permissoes = $_POST['permissoes'] ?? [];

    // Remove todas as permissões antigas
    $pdo->prepare("DELETE FROM usuario_permissoes WHERE usuario_id = ?")->execute([$usuario_id]);

    // Adiciona as novas
    if (!empty($permissoes)) {
        $stmt = $pdo->prepare("INSERT INTO usuario_permissoes (usuario_id, permissao_id) VALUES (?, ?)");
        foreach ($permissoes as $perm_id) {
            $stmt->execute([$usuario_id, $perm_id]);
        }
    }

    header("Location: permissoes_usuario.php?msg=Permissões atualizadas com sucesso!");
    exit;
}
?>
