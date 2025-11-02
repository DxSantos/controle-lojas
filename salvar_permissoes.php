<?php
require 'config.php';
session_start();

// Garante que o usuário está logado
if (empty($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 🔒 Verifica se é administrador
$stmt = $pdo->prepare("
    SELECT p.nome AS perfil_nome
    FROM usuarios u
    JOIN perfis p ON p.id = u.perfil_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['usuario_id']]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$perfil || strtolower($perfil['perfil_nome']) !== 'administrador') {
    die("<div class='alert alert-danger m-4'>❌ Acesso negado. Somente administradores podem salvar permissões.</div>");
}

// Valida usuário
if (empty($_POST['usuario_id']) || !is_numeric($_POST['usuario_id'])) {
    die("<div class='alert alert-warning m-4'>⚠️ ID do usuário não informado ou inválido.</div>");
}

$usuario_id = (int)$_POST['usuario_id'];
$permissoes = $_POST['permissoes'] ?? [];

try {
    $pdo->beginTransaction();

    // Apaga permissões antigas
    $stmt = $pdo->prepare("DELETE FROM usuario_permissoes WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);

    // Insere as novas
    if (!empty($permissoes)) {
        $stmt = $pdo->prepare("INSERT INTO usuario_permissoes (usuario_id, permissao_id) VALUES (?, ?)");
        foreach ($permissoes as $perm_id) {
            $stmt->execute([$usuario_id, $perm_id]);
        }
    }

    $pdo->commit();

    header("Location: permissoes_usuario.php?msg=Permissões atualizadas com sucesso!");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("<div class='alert alert-danger m-4'>Erro ao salvar permissões: " . htmlspecialchars($e->getMessage()) . "</div>");
}
