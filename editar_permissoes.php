<?php
require 'config.php';
include 'includes/header.php';

// Garante que o usuário está logado
if (empty($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 🔒 Verifica se é administrador
$stmt = $pdo->prepare("
    SELECT p.nome AS perfil_nome
    FROM usuarios u
    JOIN perfis p ON p.id = u.perfil_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['usuario_id']]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$perfil || strtolower($perfil['perfil_nome']) !== 'administrador') {
    echo "<div class='alert alert-danger m-4'>❌ Acesso negado. Somente administradores podem gerenciar permissões.</div>";
    include 'includes/footer.php';
    exit;
}

// Verifica se ID foi informado
if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-warning m-4'>⚠️ ID do usuário não informado ou inválido.</div>";
    include 'includes/footer.php';
    exit;
}

$usuario_id = (int)$_GET['id'];

// Busca informações do usuário
$stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo "<div class='alert alert-danger m-4'>❌ Usuário não encontrado.</div>";
    include 'includes/footer.php';
    exit;
}

// Busca permissões
$permissoes = $pdo->query("SELECT * FROM permissoes ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("SELECT permissao_id FROM usuario_permissoes WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$permissoes_usuario = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'permissao_id');
?>

<div class="container py-4">
    <h4>Permissões de <strong><?= htmlspecialchars($usuario['nome']) ?></strong></h4>
    <form method="POST" action="salvar_permissoes.php">
        <input type="hidden" name="usuario_id" value="<?= $usuario_id ?>">

        <div class="row">
            <?php foreach ($permissoes as $p): ?>
                <div class="col-md-4 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="permissoes[]" value="<?= $p['id'] ?>"
                               id="perm<?= $p['id'] ?>" <?= in_array($p['id'], $permissoes_usuario) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="perm<?= $p['id'] ?>">
                            <?= htmlspecialchars($p['nome']) ?>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn-success mt-3">💾 Salvar Permissões</button>
        <a href="permissoes_usuario.php" class="btn btn-secondary mt-3">⬅ Voltar</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
