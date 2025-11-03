<?php
require 'config.php';
require 'includes/verifica_permissao.php';
include 'includes/header.php';
date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Bloqueia se o usuário não tiver permissão "inventario"
if (!verificaPermissao('inventario')) {
    echo "<div class='alert alert-danger m-4 text-center'>
            🚫 Você não tem permissão para acessar esta página.
          </div>";
    include 'includes/footer.php';
    exit;
}

// ----- LISTAR TIPOS -----
$tipos = $pdo->query("SELECT * FROM tipos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// ----- FILTRO OPCIONAL -----
$tipo_id = !empty($_GET['tipo_id']) ? (int)$_GET['tipo_id'] : null;

// ----- AGRUPAR PRODUTOS POR TIPO -----
$produtos_por_tipo = [];
foreach ($tipos as $tipo) {
    if ($tipo_id && (int)$tipo_id !== (int)$tipo['id']) continue;

    $sql = "
        SELECT p.id, p.nome,
               sp.saldo AS saldo_atual,
               sp.data_ultimo_inventario
        FROM produtos p
        LEFT JOIN saldo_produtos sp ON sp.produto_id = p.id
        WHERE p.tipo = :tipo_id
        ORDER BY p.nome
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':tipo_id' => $tipo['id']]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($produtos) $produtos_por_tipo[$tipo['nome']] = $produtos;
}
?>

<div class="container py-4">
    <h3 class="mb-3">Inventário de Produtos</h3>

    <!-- ===== FORMULÁRIO DE FILTRO (GET) ===== -->
    <form method="GET" class="mb-1 d-flex align-items-center" style="display: flex; position: relative; gap: 15px; padding: 10px;">
    <label class="form-label mb-1">Filtrar por Tipo</label>    
    <div style="min-width: 230px; ">
            
            <select name="tipo_id" class="form-select" id="tipoFiltro" onchange="this.form.submit()">
                <option value="">Todos</option>
                <?php foreach ($tipos as $tipo): ?>
                    <option value="<?= $tipo['id'] ?>" <?= ($tipo_id == $tipo['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tipo['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <!-- ===== FORMULÁRIO DE INVENTÁRIO (POST) ===== -->
    <form id="form-inventario" method="POST" action="salvar_inventario.php" class="border rounded p-3 bg-light shadow-sm">

        <div class="d-flex align-items-center justify-content-end mb-3 gap-2">
            <button type="button" id="btn-guardar" class="btn btn-outline-warning fw-bold">Guardar Valores</button>
            <button type="submit" class="btn btn-outline-success fw-bold">Salvar Inventário</button>
        </div>

        <div class="row">
            <?php if (empty($produtos_por_tipo)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">Nenhum produto encontrado para este filtro.</div>
                </div>
            <?php endif; ?>

            <?php foreach ($produtos_por_tipo as $tipo_nome => $produtos): ?>
                <div class="col-md-6">
                    <div class="produto-card card mb-3 shadow-sm">
                        <div class="card-header bg-primary text-white fw-bold">
                            <?= htmlspecialchars($tipo_nome) ?>
                        </div>
                        <div class="card-body">
                            <?php foreach ($produtos as $produto): ?>
                                <div class="produto-item d-flex justify-content-between align-items-center border-bottom py-2">
                                    <span>
                                        <?= htmlspecialchars($produto['nome']) ?>
                                        <?php if (!empty($produto['saldo_atual'])): ?>
                                            <small class="text-muted">(Saldo atual: <?= (float)$produto['saldo_atual'] ?>)</small>
                                        <?php endif; ?>
                                    </span>
                                    <div class="quantidade-control d-flex align-items-center">
                                        <button type="button" class="btn btn-outline-secondary btn-minus">-</button>
                                        <input type="number" name="quantidade[<?= $produto['id'] ?>]" value="0" min="0" class="form-control text-center mx-1" style="width:70px;">
                                        <button type="button" class="btn btn-outline-secondary btn-plus">+</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </form>

    <!-- ===== RESUMO FLUTUANTE ===== -->
    <div id="resumo-flutuante" class="card position-fixed bg-secondary text-white shadow-lg p-3"
         style="top: 90px; right: 25px; width: 350px; display:none; opacity:0; z-index:1000;">
        <h5 class="text-center mb-2">Resumo Guardado</h5>
        <table class="table table-sm table-bordered table-light text-dark mb-0">
            <thead class="table-light">
                <tr>
                    <th>Produto</th>
                    <th>Qtd</th>
                </tr>
            </thead>
            <tbody id="resumo-body"></tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Botões + e -
    document.querySelectorAll('.btn-plus').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.previousElementSibling;
            input.value = parseInt(input.value || 0) + 1;
        });
    });
    document.querySelectorAll('.btn-minus').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.nextElementSibling;
            let val = parseInt(input.value || 0);
            if (val > 0) input.value = val - 1;
        });
    });

    // Guardar valores e mostrar resumo flutuante
    const form = document.getElementById('form-inventario');
    const btnGuardar = document.getElementById('btn-guardar');
    const resumo = document.getElementById('resumo-flutuante');
    const resumoBody = document.getElementById('resumo-body');

    btnGuardar.addEventListener('click', () => {
        resumoBody.innerHTML = '';
        let valores = {};
        form.querySelectorAll('input[name^="quantidade"]').forEach(input => {
            const val = parseInt(input.value || 0);
            if (val > 0) {
                const produto = input.closest('.produto-item').querySelector('span').innerText;
                valores[input.name] = val;
                const row = document.createElement('tr');
                row.innerHTML = `<td>${produto}</td><td>${val}</td>`;
                resumoBody.appendChild(row);
            }
        });
        if (Object.keys(valores).length > 0) {
            localStorage.setItem('inventario_guardado', JSON.stringify(valores));
            resumo.style.display = 'block';
            resumo.style.opacity = '1';
            setTimeout(() => {
                resumo.style.opacity = '0';
                setTimeout(() => resumo.style.display = 'none', 800)
            }, 8000);
        } else {
            resumo.style.display = 'none';
            resumo.style.opacity = '0';
            localStorage.removeItem('inventario_guardado');
        }
    });

    // Carrega valores guardados
    window.addEventListener('load', () => {
        const guardado = localStorage.getItem('inventario_guardado');
        if (guardado) {
            const valores = JSON.parse(guardado);
            for (let key in valores) {
                const input = form.querySelector(`input[name="${key}"]`);
                if (input) input.value = valores[key];
            }
            btnGuardar.click();
        }
    });

    // Ao salvar, limpa armazenamento local
    form.addEventListener('submit', () => {
        localStorage.removeItem('inventario_guardado');
        resumoBody.innerHTML = '';
        resumo.style.display = 'none';
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'includes/footer.php'; ?>
