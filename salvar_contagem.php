<?php
require 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');

// 🔒 Verifica login
$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
    header("Location: login.php");
    exit;
}

// 🔒 Verifica loja
$loja_id = $_SESSION['loja_id'] ?? null;
if (!$loja_id) {
    header("Location: contagem.php?erro=sem_loja");
    exit;
}

// 📌 Recebe quantidades
$quantidades = $_POST['quantidade'] ?? [];

if (empty($quantidades)) {
    header("Location: contagem.php?erro=nenhuma_quantidade");
    exit;
}

try {
    $pdo->beginTransaction();

    $sql = $pdo->prepare("
        INSERT INTO controle_contagem (produto_id, loja_id, quantidade, data, usuario_id)
        VALUES (?, ?, ?, NOW(), ?)
    ");

    foreach ($quantidades as $produto_id => $qtd) {
        $qtd = (int)$qtd;
        if ($qtd <= 0) continue;

        $sql->execute([$produto_id, $loja_id, $qtd, $usuario_id]);
    }

    $pdo->commit();

    // 🔥 Apaga valores guardados ao salvar de vez
    $pdo->prepare("DELETE FROM valores_guardados_contagem WHERE usuario_id = ? AND loja_id = ?")
        ->execute([$usuario_id, $loja_id]);

    header("Location: form_contagem.php?sucesso=1");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: form_contagem.php?erro=exception&msg=" . urlencode($e->getMessage()));
    exit;
}
