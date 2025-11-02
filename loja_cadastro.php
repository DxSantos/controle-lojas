<?php
require 'config.php';
include 'includes/header.php';

// EDITAR REGISTRO
$edit = false;
$nome_edit = '';

if (isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM lojas WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
        $edit = true;
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        $nome_edit = $dados['nome'];
    }
}

// ATIVAR / DESATIVAR LOJA
if (isset($_GET['desativar'])) {
    $id = (int) $_GET['desativar'];
    $pdo->prepare("UPDATE lojas SET ativo = 0 WHERE id = ?")->execute([$id]);
    header("Location: loja_cadastro.php");
    exit;
}
if (isset($_GET['ativar'])) {
    $id = (int) $_GET['ativar'];
    $pdo->prepare("UPDATE lojas SET ativo = 1 WHERE id = ?")->execute([$id]);
    header("Location: loja_cadastro.php");
    exit;
}

// PESQUISA
$busca = isset($_GET['busca']) ? strtoupper(trim($_GET['busca'])) : '';
$mostrarInativas = isset($_GET['mostrar_inativas']) ? true : false;

// LISTAGEM
$sql = "SELECT * FROM lojas WHERE UPPER(nome) LIKE :busca";
if (!$mostrarInativas) $sql .= " AND ativo = 1";
$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':busca', "%$busca%", PDO::PARAM_STR);
$stmt->execute();
$lojas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-container">

    <!-- ===== FORMULÁRIO ===== -->
    <form method="POST" action="loja_salvar.php" class="mb-4">
        <h4 class="mb-4"><?= $edit ? 'Editar Loja' : 'Cadastro de Loja' ?></h4>
        <input type="hidden" name="id" value="<?= $edit ? $dados['id'] : '' ?>">

        <div class="mb-3">
            <label class="form-label">Nome da Loja:</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($nome_edit) ?>" 
                   class="form-control text-uppercase" required>
        </div>

        <button type="submit" class="btn btn-success"><?= $edit ? 'Atualizar' : 'Salvar' ?></button>
        <?php if ($edit): ?>
            <a href="loja_cadastro.php" class="btn btn-secondary">Cancelar</a>
        <?php endif; ?>
    </form>

    <!-- ===== LISTAGEM ===== -->
    <div class="listagem">
        <h4>Lojas Cadastradas</h4>

        <!-- Barra de Pesquisa -->
        <form method="GET" class="search-bar d-flex align-items-center gap-2 mb-2" role="search">
            <input type="text" name="busca" class="form-control" 
                   placeholder="Pesquisar por nome..." value="<?= htmlspecialchars($busca) ?>" 
                   style="max-width:250px;">
            <div class="form-check">
                <input type="checkbox" name="mostrar_inativas" id="mostrar_inativas" 
                       class="form-check-input" <?= $mostrarInativas ? 'checked' : '' ?>>
                <label for="mostrar_inativas" class="form-check-label">Mostrar Inativas</label>
            </div>
            <button class="btn btn-primary" type="submit">Buscar</button>
            <a href="loja_cadastro.php" class="btn btn-secondary">Limpar</a>
        </form>

        <!-- Lista com Scroll -->
        <div class="scrollable-table" style="max-height: 300px; overflow-y: auto;">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Status</th>
                        <th style="width: 180px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lojas as $row): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['nome']) ?></td>
                            <td>
                                <?= $row['ativo'] ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-danger">Inativa</span>' ?>
                            </td>
                            <td>
                                <a href="?editar=<?= $row['id'] ?>" class="btn btn-sm btn-warning">✏️</a>
                                <?php if ($row['ativo']): ?>
                                    <a href="?desativar=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Deseja desativar esta loja?')">🚫</a>
                                <?php else: ?>
                                    <a href="?ativar=<?= $row['id'] ?>" class="btn btn-sm btn-success"
                                        onclick="return confirm('Deseja reativar esta loja?')">✅</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($lojas)): ?>
                        <tr><td colspan="4" class="text-center text-muted">Nenhuma loja encontrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
