<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


header('Content-Type: application/json');
date_default_timezone_set('America/Sao_Paulo');

// 🔒 Verifica se o usuário está logado
$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
    echo json_encode(['status' => 'erro', 'msg' => 'Usuário não autenticado.']);
    exit;
}

// 🔒 Verifica se a loja está selecionada
$loja_id = $_SESSION['loja_id'] ?? null;
if (!$loja_id) {
    echo json_encode(['status' => 'erro', 'msg' => 'Nenhuma loja selecionada. Selecione uma loja antes de registrar.']);
    exit;
}

// 🧩 Dados do formulário
$tipo = $_POST['tipo_registro'] ?? 'vendas';
$quantidades = $_POST['quantidade'] ?? [];
$tiposValidos = ['vendas', 'envios', 'estoque'];

// Validação do tipo
if (!in_array($tipo, $tiposValidos)) {
    echo json_encode(['status' => 'erro', 'msg' => 'Tipo inválido.']);
    exit;
}

try {
    $pdo->beginTransaction();

    foreach ($quantidades as $produto_id => $qtd) {
        $qtd = (int)$qtd;
        if ($qtd <= 0) continue;

        // 🔹 Garante que o produto exista em saldo_produtos para esta loja
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM saldo_produtos WHERE produto_id = ? AND loja_id = ?");
        $stmt->execute([$produto_id, $loja_id]);
        if ($stmt->fetchColumn() == 0) {
            $pdo->prepare("INSERT INTO saldo_produtos (produto_id, loja_id) VALUES (?, ?)")
                ->execute([$produto_id, $loja_id]);
        }

        // 🔹 Registra movimento conforme tipo
        switch ($tipo) {
            case 'vendas':
                $pdo->prepare("INSERT INTO controle_vendas (produto_id, quantidade, data, usuario_id, loja_id)
                               VALUES (?, ?, NOW(), ?, ?)")
                    ->execute([$produto_id, $qtd, $usuario_id, $loja_id]);
                break;

            case 'envios':
                $pdo->prepare("INSERT INTO controle_envios (produto_id, quantidade, data, usuario_id, loja_id)
                               VALUES (?, ?, NOW(), ?, ?)")
                    ->execute([$produto_id, $qtd, $usuario_id, $loja_id]);
                break;

            case 'estoque':
                $pdo->prepare("INSERT INTO controle_estoque (produto_id, quantidade, data, usuario_id, loja_id)
                               VALUES (?, ?, NOW(), ?, ?)")
                    ->execute([$produto_id, $qtd, $usuario_id, $loja_id]);
                break;
        }
    }

    $pdo->commit();

    // 🔄 Atualiza saldos após registrar movimentos
    include __DIR__ . '/atualizar_saldo.php';

    echo json_encode(['status' => 'ok', 'msg' => 'Movimentações registradas e saldo atualizado com sucesso!']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'erro', 'msg' => 'Erro ao salvar: ' . $e->getMessage()]);
}
