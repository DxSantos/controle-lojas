<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

/**
 * Verifica se o usuário logado possui uma permissão específica
 * Exemplo: verificaPermissao('produtos')
 */
function verificaPermissao($chavePermissao) {
    global $pdo;

    if (empty($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit;
    }

    // Se for admin, libera tudo
    $stmt = $pdo->prepare("
        SELECT p.nome AS perfil_nome
        FROM usuarios u
        JOIN perfis p ON p.id = u.perfil_id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $perfil = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($perfil && strtolower($perfil['perfil_nome']) === 'administrador') {
        return true;
    }

    // Verifica se o usuário tem a permissão solicitada
    $sql = "
        SELECT COUNT(*) FROM usuario_permissoes up
        JOIN permissoes per ON per.id = up.permissao_id
        WHERE up.usuario_id = ? AND per.chave = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['usuario_id'], $chavePermissao]);
    return $stmt->fetchColumn() > 0;
}
