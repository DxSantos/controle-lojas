<?php
require '../config.php';

$token = $_GET['token'] ?? '';
$msg = '';
$valido = false;

// Verifica se token é válido e não expirou
if ($token) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios_tokens WHERE token = ? AND usado = 0 AND expira_em > NOW()");
    $stmt->execute([$token]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($registro) {
        $valido = true;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $novaSenha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            $pdo->beginTransaction();

            // Atualiza senha
            $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")
                ->execute([$novaSenha, $registro['usuario_id']]);
            // Marca token como usado
            $pdo->prepare("UPDATE usuarios_tokens SET usado = 1 WHERE id = ?")
                ->execute([$registro['id']]);

            $pdo->commit();
            $msg = 'Senha redefinida com sucesso! Você já pode fazer login.';
            $valido = false;
        }
    } else {
        $msg = 'Link inválido ou expirado.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Redefinir Senha</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background: linear-gradient(135deg, #20c997, #0d6efd);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.card {
    background: #fff;
    padding: 30px;
    border-radius: 15px;
    width: 400px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
</style>
</head>
<body>
<section class="card">
    <h4 class="text-center text-success mb-3">Criar Nova Senha</h4>

    <?php if ($msg): ?>
        <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if ($valido): ?>
        <form method="POST">
            <div class="mb-3">
                <label>Nova Senha:</label>
                <input type="password" name="senha" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-success w-100">Salvar nova senha</button>
        </form>
    <?php else: ?>
        <div class="text-center mt-3">
            <a href="login.php" class="btn btn-primary">Voltar ao Login</a>
        </div>
    <?php endif; ?>
</section>
</body>
</html>
