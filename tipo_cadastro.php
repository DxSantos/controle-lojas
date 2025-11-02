<?php
require 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Carrega perfil e permissões do usuário logado
$stmt = $pdo->prepare("
    SELECT p.* FROM usuarios u 
    JOIN perfis p ON p.id = u.perfil_id 
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['usuario_id']]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$perfil['pode_cadastrar']) {
    die("<div class='alert alert-danger m-3'>Você não tem permissão para cadastrar itens.</div>");
}

include 'includes/header.php'; // 🔹 header padronizado

// EDITAR REGISTRO
$edit = false;
$nome_edit = '';

if (isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM tipos WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
        $edit = true;
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        $nome_edit = $dados['nome'];
    }
}

// EXCLUIR REGISTRO
if (isset($_GET['excluir'])) {
    $id = (int) $_GET['excluir'];
    $pdo->prepare("DELETE FROM tipos WHERE id = ?")->execute([$id]);
    header("Location: tipo_cadastro.php");
    exit;
}

// PESQUISA
$busca = isset($_GET['busca']) ? strtoupper(trim($_GET['busca'])) : '';

$sql = "SELECT t.id, t.nome
        FROM tipos t
        WHERE UPPER(t.nome) LIKE :busca
        ORDER BY t.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':busca', "%$busca%", PDO::PARAM_STR);
$stmt->execute();
$tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4 main-container">
    <form method="POST" action="tipo_salvar.php" class="mb-4">
        <h4 class="mb-4"><?= $edit ? 'Editar Tipo de Produto' : 'Cadastro de Tipo de Produto' ?></h4>
        <input type="hidden" name="id" value="<?= $edit ? $dados['id'] : '' ?>">

        <div class="mb-3">
            <label class="form-label">Nome do Tipo:</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($nome_edit) ?>" class="form-control text-uppercase" required>
        </div>

        <button type="submit" class="btn btn-success"><?= $edit ? 'Atualizar' : 'Salvar' ?></button>
        <?php if ($edit): ?>
            <a href="tipo_cadastro.php" class="btn btn-secondary">Cancelar</a>
        <?php endif; ?>
    </form>

    <div class="listagem">
        <h4>Tipos de Produtos</h4>

        <form method="GET" class="d-flex gap-2 mb-3">
            <input type="text" name="busca" class="form-control" placeholder="Pesquisar por tipo..." value="<?= htmlspecialchars($busca) ?>">
            <button class="btn btn-primary" type="submit">Buscar</button>
            <a href="tipo_cadastro.php" class="btn btn-secondary">Limpar</a>
        </form>

        <div class="scrollable-table" style="max-height: 300px; overflow-y: auto;">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th style="width: 150px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tipos as $row): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['nome']) ?></td>
                            <td>
                                <a href="?editar=<?= $row['id'] ?>" class="btn btn-sm btn-warning">✏️</a>
                                <a href="?excluir=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Deseja realmente excluir este tipo?')">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php require 'includes/footer.php'; ?>
