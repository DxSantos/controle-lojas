<?php
require 'config.php';
include 'includes/header.php';

// Verifica se o usuário está logado
if (empty($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 🔒 Verifica se o usuário logado é administrador
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

// =====================
// 1️⃣ LISTAGEM DE USUÁRIOS
// =====================
if (!isset($_GET['gerenciar'])) {
    $usuarios = $pdo->query("
        SELECT u.id, u.nome, u.email, u.ativo, p.nome AS perfil
        FROM usuarios u
        LEFT JOIN perfis p ON p.id = u.perfil_id
        ORDER BY u.nome
    ")->fetchAll(PDO::FETCH_ASSOC);
?>

    <div class="container py-4">
        <h3 class="mb-4"><i class="bi bi-shield-lock"></i> Gerenciar Permissões</h3>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
        <?php endif; ?>

        <div class="table-responsive shadow-sm">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Status</th>
                        <th style="width: 160px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['nome']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['perfil'] ?? '-') ?></td>
                            <td>
                                <?php if ($u['ativo']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?gerenciar=<?= $u['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-key"></i> Permissões
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php
    include 'includes/footer.php';
    exit;
}

// =====================
// 2️⃣ EDIÇÃO DAS PERMISSÕES DE UM USUÁRIO
// =====================
$usuario_id = (int)$_GET['gerenciar'];

// Busca informações do usuário
$stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo "<div class='alert alert-danger m-4'>❌ Usuário não encontrado.</div>";
}


// Busca todas as permissões e as atuais do usuário
$permissoes = $pdo->query("SELECT * FROM permissoes ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("SELECT permissao_id FROM usuario_permissoes WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$permissoes_usuario = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'permissao_id');
?>

<div class="container py-4">
    <h4><i class="bi bi-person-lock"></i> Permissões de <strong><?= htmlspecialchars($usuario['nome']) ?></strong></h4>

    <form method="POST" action="salvar_permissoes.php" class="border rounded p-3 bg-light shadow-sm">
        <input type="hidden" name="usuario_id" value="<?= $usuario_id ?>">

        <div class="row">
            <?php foreach ($permissoes as $p): ?>
                <div class="col-md-4 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="permissoes[]" value="<?= $p['id'] ?>"
                            id="perm<?= $p['id'] ?>" <?= in_array($p['id'], $permissoes_usuario) ? 'checked' : '' ?>>
                        <label class="form-check-label text-capitalize" for="perm<?= $p['id'] ?>">
                            <?= htmlspecialchars($p['nome']) ?>
                            <small class="text-muted">(<?= htmlspecialchars($p['chave']) ?>)</small>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Salvar</button>
            <a href="permissoes_usuario.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
        </div>
    </form>
</div>


<?php include 'includes/footer.php'; ?>