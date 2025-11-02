<?php
require 'config.php';
include 'includes/header.php';

$stmt = $pdo->query("
    SELECT u.id, u.nome, u.email, u.ativo, p.nome AS perfil_nome
    FROM usuarios u
    LEFT JOIN perfis p ON p.id = u.perfil_id
    ORDER BY u.id DESC
");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <h3 class="mb-4">Usuários do Sistema</h3>

    <a href="usuario_novo.php" class="btn btn-success mb-3">➕ Novo Usuário</a>

    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Perfil</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['nome']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['perfil_nome'] ?? '—') ?></td>
                    <td>
                        <?php if ($u['ativo']): ?>
                            <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="usuario_editar.php?id=<?= $u['id'] ?>" class="btn btn-warning btn-sm">✏️</a>
                        <?php if ($u['ativo']): ?>
                            <a href="usuario_excluir.php?id=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                                onclick="return confirm('Deseja desativar este usuário?')">🚫</a>
                        <?php else: ?>
                            <a href="usuario_ativar.php?id=<?= $u['id'] ?>" class="btn btn-success btn-sm">✅ Ativar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php require 'includes/footer.php'; ?>
