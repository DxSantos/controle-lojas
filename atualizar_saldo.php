<?php
require 'config.php';
date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Loja ativa da sessão
$loja_id = $_SESSION['loja_id'] ?? null;
if (!$loja_id) {
    die("Nenhuma loja selecionada.");
}

// ----- Pega a data do último inventário geral -----
$stmtUltimoInventario = $pdo->prepare("
    SELECT MAX(data_inventario)
    FROM inventario_log
    WHERE loja_id = ?
");
$stmtUltimoInventario->execute([$loja_id]);
$ultima_data = $stmtUltimoInventario->fetchColumn() ?: '1900-01-01 00:00:00';

// ----- Lista todos os produtos dessa loja -----
$produtos = $pdo->query("SELECT id FROM produtos")->fetchAll(PDO::FETCH_ASSOC);

foreach ($produtos as $produto) {
    $produto_id = $produto['id'];

    // 🔹 Soma movimentos após o último inventário (filtrando loja)
    $soma_vendas = (int)$pdo->prepare("
        SELECT COALESCE(SUM(quantidade), 0)
        FROM controle_vendas
        WHERE produto_id = ? AND loja_id = ? AND data > ?
    ")->execute([$produto_id, $loja_id, $ultima_data]) ?: 0;

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantidade), 0)
        FROM controle_vendas
        WHERE produto_id = ? AND loja_id = ? AND data > ?
    ");
    $stmt->execute([$produto_id, $loja_id, $ultima_data]);
    $soma_vendas = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantidade), 0)
        FROM controle_envios
        WHERE produto_id = ? AND loja_id = ? AND data > ?
    ");
    $stmt->execute([$produto_id, $loja_id, $ultima_data]);
    $soma_envios = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantidade), 0)
        FROM controle_estoque
        WHERE produto_id = ? AND loja_id = ? AND data > ?
    ");
    $stmt->execute([$produto_id, $loja_id, $ultima_data]);
    $soma_estoque = (int)$stmt->fetchColumn();

    // 🔹 Busca saldo do último inventário
    $stmt = $pdo->prepare("
        SELECT saldo_inventario
        FROM inventario_log
        WHERE produto_id = ? AND loja_id = ?
        ORDER BY data_inventario DESC
        LIMIT 1
    ");
    $stmt->execute([$produto_id, $loja_id]);
    $saldo_inventario = (int)$stmt->fetchColumn();

    // 🔹 Calcula saldo final corretamente
    $saldo_final = $saldo_inventario + $soma_estoque + $soma_envios - $soma_vendas;

    // 🔹 Atualiza saldo_produtos (somente da loja atual)
    $stmt = $pdo->prepare("
        UPDATE saldo_produtos
        SET estoque = ?, envios = ?, vendas = ?, saldo = ?, data_ultimo_inventario = ?
        WHERE produto_id = ? AND loja_id = ?
    ");
    $stmt->execute([$soma_estoque, $soma_envios, $soma_vendas, $saldo_final, $ultima_data, $produto_id, $loja_id]);
}

// 🔹 Redireciona com sucesso
$retorno = $_GET['retorno'] ?? 'form_quantidade.php';
header("Location: $retorno?msg=Saldo atualizado com sucesso!");
exit;
?>
