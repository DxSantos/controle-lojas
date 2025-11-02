<?php
require 'config.php';
include 'includes/header.php';

// Garante que o usuário está logado
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

// Busca todos os usuários cadastrados
$usuarios = $pdo->query("
    SELECT u.id, u.nome, u.email, p.nome AS perfil, u.ativo
    FROM usuarios u
    LEFT JOIN perfis p ON p.id = u.perfil_id
    ORDER BY u.nome
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <h3 class="mb-4">Gerenciamento de Permissões de Usuários</h3>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle shadow-sm">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 160px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['nome']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['perfil'] ?? '—') ?></td>
                        <td>
                            <?php if ($u['ativo']): ?>
                                <span class="badge bg-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="editar_permissoes.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-primary">
                                🔐 Gerenciar Permissões
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($usuarios)): ?>
                    <tr><td colspan="6" class="text-center text-muted">Nenhum usuário encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="usuarios_lista.php" class="btn btn-secondary mt-3">⬅ Voltar</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php require 'includes/footer.php'; ?>
