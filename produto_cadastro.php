<?php

require 'config.php';

// ----- MENSAGEM DE RETORNO -----
$msg = '';
if (!empty($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    $msg_tipo = $_SESSION['msg_tipo'] ?? 'info';
    unset($_SESSION['msg'], $_SESSION['msg_tipo']);
}

// ----- EDITAR REGISTRO -----
$edit = false;
$produto = ['id' => '', 'nome' => '', 'tipo' => '', 'ativo' => 1];

if (isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
        $edit = true;
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ----- DESATIVAR (em vez de excluir) -----
if (isset($_GET['excluir'])) {
    $id = (int) $_GET['excluir'];
    $pdo->prepare("UPDATE produtos SET ativo = 0 WHERE id = ?")->execute([$id]);
    $_SESSION['msg'] = 'Produto desativado com sucesso!';
    $_SESSION['msg_tipo'] = 'warning';
    header("Location: produto_cadastro.php");
    exit;
}

// ----- LISTAR TIPOS -----
$tipos = $pdo->query("SELECT * FROM tipos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// ----- FILTRO DE ATIVOS -----
$filtroAtivo = $_GET['filtro'] ?? 'ativos';
$condicaoAtivo = "p.ativo = 1";
if ($filtroAtivo === 'desativados') $condicaoAtivo = "p.ativo = 0";
elseif ($filtroAtivo === 'todos') $condicaoAtivo = "1=1"; // sem filtro

// ----- PESQUISA -----
$busca = isset($_GET['busca']) ? strtoupper(trim($_GET['busca'])) : '';

// ----- LISTAGEM -----
$sql = "SELECT p.id, p.nome, t.nome AS tipo_nome, p.ativo
        FROM produtos p 
        LEFT JOIN tipos t ON p.tipo = t.id
        WHERE $condicaoAtivo AND (UPPER(p.nome) LIKE :busca OR UPPER(t.nome) LIKE :busca)
        ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['busca' => "%$busca%"]);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require 'includes/header.php'; ?>

<div class="main-container">

    <!-- ===== FORMULÁRIO ===== -->
    <form method="POST" action="produto_salvar.php">
        <h4 class="mb-3"><?= $edit ? 'Editar Produto' : 'Cadastro de Produto' ?></h4>

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_tipo ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <input type="hidden" name="id" value="<?= $produto['id'] ?>">

        <div class="mb-3">
            <label class="form-label">Nome do Produto:</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($produto['nome']) ?>" required class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Tipo:</label>
            <select name="tipo" class="form-select" required>
                <option value="">Selecione</option>
                <?php foreach ($tipos as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= ($t['id'] == $produto['tipo']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="d-flex gap-3 align-items-center">
            <button type="submit" class="btn btn-outline-success fw-bold">
                <?= $edit ? 'Atualizar' : 'Salvar' ?>
            </button>

            <?php if ($edit): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="ativo" id="ativo" value="1" <?= $produto['ativo'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ativo">Ativo</label>
                </div>
                <a href="produto_cadastro.php" class="btn btn-secondary">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- ===== LISTAGEM ===== -->
    <div class="listagem mt-4">
        <h4>Produtos Cadastrados</h4>

        <!-- Barra de Pesquisa e Filtro -->
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center mb-3" role="search">
            <input type="text" name="busca" class="form-control" style="max-width:250px"
                placeholder="Pesquisar por nome ou tipo..." value="<?= htmlspecialchars($busca) ?>">

            <select name="filtro" class="form-select" style="max-width:200px;">
                <option value="ativos" <?= $filtroAtivo === 'ativos' ? 'selected' : '' ?>>Ativos</option>
                <option value="desativados" <?= $filtroAtivo === 'desativados' ? 'selected' : '' ?>>Desativados</option>
                <option value="todos" <?= $filtroAtivo === 'todos' ? 'selected' : '' ?>>Todos</option>
            </select>

            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a href="produto_cadastro.php" class="btn btn-secondary">Limpar</a>
        </form>

        <!-- Lista com Scroll -->
        <div class="scrollable-table" style="max-height: 300px; overflow-y: auto;">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th style="width:150px">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($produtos) > 0): ?>
                        <?php foreach ($produtos as $row): ?>
                            <tr class="<?= $row['ativo'] ? '' : 'table-secondary' ?>">
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['nome']) ?></td>
                                <td><?= htmlspecialchars($row['tipo_nome']) ?></td>
                                <td>
                                    <?php if ($row['ativo']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Desativado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?editar=<?= $row['id'] ?>" class="btn btn-sm btn-warning">✏️</a>
                                    <?php if ($row['ativo']): ?>
                                        <a href="?excluir=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                                           onclick="return confirm('Deseja realmente desativar este produto?')">🚫</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Nenhum produto encontrado</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<?php require_once 'includes/footer.php'; ?>


