<?php
require 'config.php';
date_default_timezone_set('America/Sao_Paulo');

// ----- Pega a data do último inventário geral -----
$stmtUltimoInventario = $pdo->query("SELECT MAX(data_inventario) AS ultima_data FROM inventario_log");
$ultima_data = $stmtUltimoInventario->fetchColumn();
if (!$ultima_data) {
    // Se não houver inventário ainda, considera uma data antiga
    $ultima_data = '1900-01-01 00:00:00';
}

// ----- Lista todos os produtos -----
$produtos = $pdo->query("SELECT id FROM produtos")->fetchAll(PDO::FETCH_ASSOC);

foreach ($produtos as $produto) {
    $produto_id = $produto['id'];

    // 🔹 Soma os movimentos APÓS o último inventário

    // VENDAS
    $stmtVendas = $pdo->prepare("
        SELECT COALESCE(SUM(quantidade),0)
        FROM controle_vendas
        WHERE produto_id = ? AND data > ?
    ");
    $stmtVendas->execute([$produto_id, $ultima_data]);
    $soma_vendas = (int)$stmtVendas->fetchColumn();

    // ENVIOS
    $stmtEnvios = $pdo->prepare("
        SELECT COALESCE(SUM(quantidade),0)
        FROM controle_envios
        WHERE produto_id = ? AND data > ?
    ");
    $stmtEnvios->execute([$produto_id, $ultima_data]);
    $soma_envios = (int)$stmtEnvios->fetchColumn();

    // ESTOQUE (entradas)
    $stmtEstoque = $pdo->prepare("
        SELECT COALESCE(SUM(quantidade),0)
        FROM controle_estoque
        WHERE produto_id = ? AND data > ?
    ");
    $stmtEstoque->execute([$produto_id, $ultima_data]);
    $soma_estoque = (int)$stmtEstoque->fetchColumn();

    // 🔹 Saldo do último inventário do produto
    $stmtSaldoInv = $pdo->prepare("
        SELECT saldo_inventario
        FROM inventario_log
        WHERE produto_id = ?
        ORDER BY data_inventario DESC
        LIMIT 1
    ");
    $stmtSaldoInv->execute([$produto_id]);
    $saldo_inventario = (int)$stmtSaldoInv->fetchColumn();

    // 🔹 Calcula saldo final
    $saldo_final = $saldo_inventario + $soma_estoque + $soma_envios - $soma_vendas;

    // 🔹 Atualiza a tabela saldo_produtos
    $stmtUpdate = $pdo->prepare("
        UPDATE saldo_produtos
        SET estoque = ?, envios = ?, vendas = ?, saldo = ?, data_ultimo_inventario = ?
        WHERE produto_id = ?
    ");
    $stmtUpdate->execute([$soma_estoque, $soma_envios, $soma_vendas, $saldo_final, $ultima_data, $produto_id]);
}

// 🔹 Redireciona com mensagem de sucesso
$retorno = $_GET['retorno'] ?? 'form_quantidade.php';
header("Location: $retorno?msg=Saldo atualizado com sucesso!");
exit;
?>
