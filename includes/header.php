<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se não estiver logado, redireciona
if (empty($_SESSION['usuario_id'])) {
    header('Location: sections/login.php');
    exit;
}

require_once 'config.php';

// Nome do usuário logado
$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Usuário';

// Verifica se o usuário logado é administrador
$stmt = $pdo->prepare("
    SELECT p.nome AS perfil_nome
    FROM usuarios u
    JOIN perfis p ON p.id = u.perfil_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['usuario_id']]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);
$isAdmin = ($perfil && strtolower($perfil['perfil_nome']) === 'administrador');

// Página atual para destacar
$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Controle de Estoque</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="form_quantidade.php">
            <i class="bi bi-box-seam"></i> Controle de Lojas
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <!-- Cadastro -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($pagina_atual, ['produto_cadastro.php','tipo_cadastro.php','loja_cadastro.php']) ? 'active' : '' ?>"
                       href="#" id="menuCadastro" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Cadastros
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="menuCadastro">
                        <li><a class="dropdown-item" href="produto_cadastro.php">Produtos</a></li>
                        <li><a class="dropdown-item" href="tipo_cadastro.php">Tipos</a></li>
                        <li><a class="dropdown-item" href="loja_cadastro.php">Lojas</a></li>
                    </ul>
                </li>

                <!-- Movimentações -->
                <li class="nav-item">
                    <a class="nav-link <?= $pagina_atual === 'form_quantidade.php' ? 'active' : '' ?>" href="form_quantidade.php">Movimentações</a>
                </li>

                <!-- Inventário -->
                <li class="nav-item">
                    <a class="nav-link <?= $pagina_atual === 'form_inventario.php' ? 'active' : '' ?>" href="form_inventario.php">Inventário</a>
                </li>

                <!-- Relatórios -->
                <li class="nav-item">
                    <a class="nav-link <?= $pagina_atual === 'relatorio_movimentacao.php' ? 'active' : '' ?>" href="relatorio_movimentacao.php">Relatórios</a>
                </li>

                <!-- Administração -->
                <?php if ($isAdmin): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($pagina_atual, ['usuarios_lista.php','permissoes_usuario.php']) ? 'active' : '' ?>"
                       href="#" id="menuUsuarios" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Administração
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="menuUsuarios">
                        <li><a class="dropdown-item" href="usuarios_lista.php">Usuários</a></li>
                        <li><a class="dropdown-item" href="permissoes_usuario.php">Permissões de Usuário</a></li>
                        <li><a class="dropdown-item" href="permissoes_cadastro.php">Permissões de Cadastro</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>

            <?php if (!empty($_SESSION['loja_nome'])): ?>
        <div class="dropdown me-3">
          <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-shop"></i> <?= htmlspecialchars($_SESSION['loja_nome']) ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="trocar_loja.php"><i class="bi bi-arrow-repeat"></i> Trocar Loja</a></li>
          </ul>
        </div>
      <?php endif; ?>

            <!-- Usuário logado -->
            <div class="d-flex align-items-center text-white">
                <i class="bi bi-person-circle fs-5 me-2"></i>
                <span class="me-3"><?= htmlspecialchars($nomeUsuario) ?></span>
                <a href="sections/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Sair
                </a>
            </div>
        </div>
    </div>
</nav>

<?php include 'includes/modal_lojas.php'; ?>



<!-- espaçamento para o topo fixo -->
<div style="height:10px;"></div>
