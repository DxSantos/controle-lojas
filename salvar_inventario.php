<?php
require 'config.php';
date_default_timezone_set('America/Sao_Paulo');

// Verifica sessão e usuário logado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuario_id = $_SESSION['usuario_id'] ?? null;
$loja_id = $_SESSION['loja_id'] ?? null;

if (!$usuario_id) {
    die(json_encode(['status' => 'erro', 'msg' => 'Usuário não autenticado.']));
}

if (!$loja_id) {
    die(json_encode(['status' => 'erro', 'msg' => 'Nenhuma loja selecionada.']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantidade'])) {

    $quantidades = $_POST['quantidade'];
    $data_inventario = date('Y-m-d H:i:s');
    $codigo_inventario = 'INV-' . date('Ymd-His');

    try {
        $pdo->beginTransaction();

        foreach ($quantidades as $produto_id => $novo_inventario) {
            $novo_inventario = (float)$novo_inventario;

            // Busca saldo atual
            $stmtSaldo = $pdo->prepare("
                SELECT inventario, estoque, envios, vendas, saldo 
                FROM saldo_produtos 
                WHERE produto_id = ? AND loja_id = ?
            ");
            $stmtSaldo->execute([$produto_id, $loja_id]);
            $linha = $stmtSaldo->fetch(PDO::FETCH_ASSOC);

            $saldo_anterior = $linha['saldo'] ?? 0;

            // Insere log de inventário
            $stmtLog = $pdo->prepare("
                INSERT INTO inventario_log (
                    produto_id, saldo_anterior, saldo_inventario, 
                    data_inventario, codigo_inventario, loja_id, usuario_id
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtLog->execute([
                $produto_id, $saldo_anterior, $novo_inventario,
                $data_inventario, $codigo_inventario, $loja_id, $usuario_id
            ]);

            // Atualiza saldo_produtos
            $stmtUpdate = $pdo->prepare("
                UPDATE saldo_produtos
                SET estoque = 0, envios = 0, vendas = 0, 
                    inventario = ?, saldo = ?, data_ultimo_inventario = ?, loja_id = ?
                WHERE produto_id = ? AND loja_id = ?
            ");
            $stmtUpdate->execute([
                $novo_inventario, $novo_inventario, $data_inventario, $loja_id,
                $produto_id, $loja_id
            ]);

            // Se não existir, cria novo registro
            if ($stmtUpdate->rowCount() === 0) {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO saldo_produtos (
                        produto_id, inventario, estoque, envios, vendas, 
                        saldo, data_ultimo_inventario, loja_id
                    )
                    VALUES (?, ?, 0, 0, 0, ?, ?, ?)
                ");
                $stmtInsert->execute([
                    $produto_id, $novo_inventario, $novo_inventario, $data_inventario, $loja_id
                ]);
            }
        }

        $pdo->commit();
        header("Location: form_inventario.php?msg=Inventário ($codigo_inventario) salvo com sucesso!");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div class='alert alert-danger m-3'>❌ Erro ao salvar inventário: " . $e->getMessage() . "</div>";
    }

} else {
    echo "<div class='alert alert-warning m-3'>Nenhum dado enviado.</div>";
}
?>
