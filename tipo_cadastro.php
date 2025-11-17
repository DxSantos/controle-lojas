<?php
require 'config.php';
require 'includes/verifica_permissao.php';
include 'includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Bloqueia se o usuário não tiver permissão "tipos"
if (!verificaPermissao('tipos')) {
    echo "<div class='alert alert-danger m-4 text-center'>
            🚫 Você não tem permissão para acessar esta página.
          </div>";
    include 'includes/footer.php';
    exit;
}

// 🔹 Garante que o campo exista no banco
try {
    $pdo->query("ALTER TABLE tipos ADD COLUMN visualizar_movimentos TINYINT(1) DEFAULT 0");
} catch (Exception $e) {
    // Se já existir, ignora o erro
}

// EDITAR REGISTRO
$edit = false;
$nome_edit = '';
$visualizar_edit = 0;

if (isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM tipos WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
        $edit = true;
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        $nome_edit = $dados['nome'];
        $visualizar_edit = $dados['visualizar_movimentos'] ?? 0;
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

$sql = "SELECT t.id, t.nome, t.visualizar_movimentos
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
        <?php if (!empty($_SESSION['alerta_msg'])): ?>
    <div class="alert alert-<?= $_SESSION['alerta_tipo'] ?> alert-dismissible fade show" role="alert">
        <?= $_SESSION['alerta_msg'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
    <?php 
    unset($_SESSION['alerta_msg'], $_SESSION['alerta_tipo']); 
    ?>
<?php endif; ?>
        <h4 class="mb-4"><?= $edit ? 'Editar Tipo de Produto' : 'Cadastro de Tipo de Produto' ?></h4>
        <input type="hidden" name="id" value="<?= $edit ? $dados['id'] : '' ?>">

        <div class="mb-3">
            <label class="form-label">Nome do Tipo:</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($nome_edit) ?>" class="form-control text-uppercase" required>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="visualizar_movimentos" id="visualizar_movimentos"
                   value="1" <?= $visualizar_edit ? 'checked' : '' ?>>
            <label class="form-check-label" for="visualizar_movimentos">
                Visualizar este tipo em Movimentações
            </label>
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

        <div class="scrollable-table" style="max-height: 350px; overflow-y: auto;">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Visualizar em Movimentos</th>
                        <th style="width: 150px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tipos as $row): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['nome']) ?></td>
                            <td>
                                <?php if ($row['visualizar_movimentos']): ?>
                                    <span class="badge bg-success">Sim</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Não</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?editar=<?= $row['id'] ?>" class="btn btn-sm btn-warning">✏️</a>
                                <a href="?excluir=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Deseja realmente excluir este tipo?')">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
