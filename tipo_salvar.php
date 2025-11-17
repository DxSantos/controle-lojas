<?php
require 'config.php';
session_start();

// 🔒 Verifica se o usuário está logado
if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Dados do formulário
$id = $_POST['id'] ?? '';
$nome = strtoupper(trim($_POST['nome'] ?? ''));
$visualizar = isset($_POST['visualizar_movimentos']) ? 1 : 0;

$mensagem = '';
$tipo_alerta = ''; // success | danger | warning

if (empty($nome)) {
    $mensagem = "⚠️ O campo Nome é obrigatório!";
    $tipo_alerta = "warning";
} else {
    try {
        // Verifica se já existe outro tipo com o mesmo nome
        $stmtCheck = $pdo->prepare("SELECT id FROM tipos WHERE nome = ? AND id != ?");
        $stmtCheck->execute([$nome, $id ?: 0]);

        if ($stmtCheck->fetch()) {
            $mensagem = "⚠️ Já existe um tipo com este nome!";
            $tipo_alerta = "warning";
        } else {
            if ($id) {
                // Atualiza tipo existente
                $stmt = $pdo->prepare("
                    UPDATE tipos
                    SET nome = ?, visualizar_movimentos = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nome, $visualizar, $id]);
                $mensagem = "✅ Tipo atualizado com sucesso!";
                $tipo_alerta = "success";
            } else {
                // Insere novo tipo
                $stmt = $pdo->prepare("
                    INSERT INTO tipos (nome, visualizar_movimentos)
                    VALUES (?, ?)
                ");
                $stmt->execute([$nome, $visualizar]);
                $mensagem = "✅ Tipo cadastrado com sucesso!";
                $tipo_alerta = "success";
            }
        }
    } catch (Exception $e) {
        $mensagem = "❌ Erro ao salvar tipo: " . htmlspecialchars($e->getMessage());
        $tipo_alerta = "danger";
    }
}

// 🔹 Armazena a mensagem em sessão para exibir após o redirecionamento
$_SESSION['alerta_tipo'] = $tipo_alerta;
$_SESSION['alerta_msg'] = $mensagem;

// 🔹 Redireciona de volta para o cadastro
header("Location: tipo_cadastro.php");
exit;
