<?php
require 'config.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: usuarios_lista.php");
    exit;
}

// Buscar dados do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: usuarios_lista.php?msg=Usuário não encontrado");
    exit;
}

// Buscar perfis disponíveis
$perfis = $pdo->query("SELECT id, nome FROM perfis ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Atualizar usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = strtoupper(trim($_POST['nome']));
    $email = strtolower(trim($_POST['email']));
    $perfil_id = (int)$_POST['perfil_id'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, perfil_id = ?, ativo = ? WHERE id = ?");
    $stmt->execute([$nome, $email, $perfil_id, $ativo, $id]);

    header("Location: usuarios_lista.php?msg=Usuário atualizado com sucesso!");
    exit;
}
?>

<div class="container py-4">
    <h3 class="mb-4">Editar Usuário</h3>

    <form method="POST" class="border rounded p-3 bg-light shadow-sm">
        <div class="mb-3">
            <label class="form-label">Nome:</label>
            <input type="text" name="nome" class="form-control text-uppercase"
                value="<?= htmlspecialchars($usuario['nome']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">E-mail:</label>
            <input type="email" name="email" class="form-control"
                value="<?= htmlspecialchars($usuario['email']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Perfil de Acesso:</label>
            <select name="perfil_id" class="form-select" required>
                <?php foreach ($perfis as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($usuario['perfil_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="ativo" id="ativo"
                <?= $usuario['ativo'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="ativo">Usuário Ativo</label>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">💾 Atualizar</button>
            <a href="usuarios_lista.php" class="btn btn-secondary">Voltar</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
