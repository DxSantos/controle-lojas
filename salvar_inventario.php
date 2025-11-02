<?php
require 'config.php';
date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantidade'])) {

    $quantidades = $_POST['quantidade'];
    $data_inventario = date('Y-m-d H:i:s');

    // Gera um código único para este inventário
    $codigo_inventario = 'INV-' . date('Ymd-His');

    foreach ($quantidades as $produto_id => $novo_inventario) {
        $novo_inventario = (float)$novo_inventario;

        // Busca saldo atual
        $stmtSaldo = $pdo->prepare("SELECT inventario, estoque, envios, vendas, saldo FROM saldo_produtos WHERE produto_id = ?");
        $stmtSaldo->execute([$produto_id]);
        $linha = $stmtSaldo->fetch(PDO::FETCH_ASSOC);

        $saldo_anterior = $linha['saldo'] ?? 0;

        // Cria log do inventário com código único para todos os produtos
        $stmtLog = $pdo->prepare("
            INSERT INTO inventario_log (produto_id, saldo_anterior, saldo_inventario, data_inventario, codigo_inventario)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtLog->execute([$produto_id, $saldo_anterior, $novo_inventario, $data_inventario, $codigo_inventario]);

        // Atualiza ou cria o saldo atual na tabela saldo_produtos
        $novo_saldo = $novo_inventario; // entradas e saidas zeradas
        $stmtUpdate = $pdo->prepare("
            UPDATE saldo_produtos
            SET estoque = 0, envios = 0, vendas = 0, inventario = ?, saldo = ?
            WHERE produto_id = ?
        ");
        $stmtUpdate->execute([$novo_inventario, $novo_saldo, $produto_id]);

        // Caso produto ainda não exista em saldo_produtos
        if ($stmtUpdate->rowCount() === 0) {
            $stmt = $pdo->prepare("
    INSERT INTO saldo_produtos (produto_id, estoque, envios, vendas, inventario, saldo)
    VALUES (?, 0, 0, 0, ?, ?)
    ON DUPLICATE KEY UPDATE estoque = 0, envios = 0, vendas = 0, inventario = VALUES(inventario), saldo = VALUES(saldo)
");
$stmt->execute([$produto_id, $novo_inventario, $novo_saldo]);

        }
    }

    header("Location: form_inventario.php?msg=Inventário ($codigo_inventario) salvo com sucesso!");
    exit;

} else {
    echo "Nenhum dado enviado.";
}
?>
