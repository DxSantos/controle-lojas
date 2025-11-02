<?php
// includes/funcoes_permissoes.php

function temPermissao($pdo, $usuario_id, $chavePermissao) {
    $stmt = $pdo->prepare("
        SELECT 1
        FROM usuario_permissoes up
        JOIN permissoes p ON p.id = up.permissao_id
        WHERE up.usuario_id = ? AND p.chave = ?
    ");
    $stmt->execute([$usuario_id, $chavePermissao]);
    return $stmt->fetchColumn() !== false;
}
?>
