<?php
require 'config.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
    die('Usuário não autenticado.');
}

header('Content-Type: application/json');
date_default_timezone_set('America/Sao_Paulo');

$tipo = $_POST['tipo_registro'] ?? 'vendas';
$quantidades = $_POST['quantidade'] ?? [];
$tiposValidos = ['vendas', 'envios', 'estoque'];

if (!in_array($tipo, $tiposValidos)) {
    echo json_encode(['status' => 'erro', 'msg' => 'Tipo inválido']);
    exit;
}

try {
    $pdo->beginTransaction();

    foreach ($quantidades as $produto_id => $qtd) {
        $qtd = (int)$qtd;
        if ($qtd <= 0) continue;

        // 🔹 Garante que o produto exista em saldo_produtos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM saldo_produtos WHERE produto_id = ?");
        $stmt->execute([$produto_id]);
        if ($stmt->fetchColumn() == 0) {
            $pdo->prepare("INSERT INTO saldo_produtos (produto_id) VALUES (?)")
                ->execute([$produto_id]);
        }

        // 🔹 Registra movimento conforme tipo
        switch ($tipo) {
            case 'vendas':
                $pdo->prepare("INSERT INTO controle_vendas (produto_id, quantidade, data, usuario_id)
                               VALUES (?, ?, NOW(), ?)")
                    ->execute([$produto_id, $qtd, $usuario_id]);
                break;

            case 'envios':
                $pdo->prepare("INSERT INTO controle_envios (produto_id, quantidade, data, usuario_id)
                               VALUES (?, ?, NOW(), ?)")
                    ->execute([$produto_id, $qtd, $usuario_id]);
                break;

            case 'estoque':
                $pdo->prepare("INSERT INTO controle_estoque (produto_id, quantidade, data, usuario_id)
                               VALUES (?, ?, NOW(), ?)")
                    ->execute([$produto_id, $qtd, $usuario_id]);
                break;
        }
    }

    $pdo->commit();

    // 🔹 Atualiza saldos após registrar movimentos
    include __DIR__ . '/atualizar_saldo.php';

    echo json_encode(['status' => 'ok', 'msg' => 'Movimentações registradas e saldo atualizado.']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
