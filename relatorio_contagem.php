<?php
date_default_timezone_set('America/Sao_Paulo');
require 'config.php';
require 'includes/verifica_permissao.php';
include 'includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica login
if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Verifica permissão
if (!verificaPermissao('contagem')) {
    echo "<div class='alert alert-danger m-4 text-center'>
            🚫 Você não tem permissão para acessar esta página.
          </div>";
    include 'includes/footer.php';
    exit;
}

// Verifica loja
$loja_id = $_SESSION['loja_id'] ?? null;
if (!$loja_id) {
    echo "<div class='alert alert-warning m-4 text-center'>
            ⚠️ Nenhuma loja selecionada.
          </div>";
    include 'includes/footer.php';
    exit;
}

// -------- FILTRO POR PRODUTO -------------
$filtro_produto = $_GET['produto'] ?? "";

// -------- LISTA DE PRODUTOS (APENAS DE TIPOS visualizar_movimentos = 0) --------
$produtos = $pdo->query("
    SELECT p.id, p.nome
    FROM produtos p
    JOIN tipos t ON t.id = p.tipo
    WHERE t.visualizar_movimentos = 0
    ORDER BY p.nome
")->fetchAll(PDO::FETCH_ASSOC);

// -------- CONSULTA ÚLTIMA E PENÚLTIMA CONTAGEM ----------
$sql = "
    SELECT 
        p.nome AS produto,
        v.ultima_qtd,
        v.penultima_qtd,
        (v.ultima_qtd - v.penultima_qtd) AS diferenca,
        DATE_FORMAT(v.ultima_data, '%d/%m/%Y %H:%i') AS ultima_data,
        DATE_FORMAT(v.penultima_data, '%d/%m/%Y %H:%i') AS penultima_data
    FROM view_duas_ultimas_contagens v
    JOIN produtos p ON p.id = v.produto_id
    JOIN tipos t ON t.id = p.tipo
    WHERE v.loja_id = ?
    AND t.visualizar_movimentos = 0
";

$params = [$loja_id];

if (!empty($filtro_produto)) {
    $sql .= " AND v.produto_id = ? ";
    $params[] = $filtro_produto;
}

$sql .= " ORDER BY p.nome";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <h3 class="mb-4">📊 Relatório de Contagem</h3>


    <!-- FILTRO -->
    <form method="GET" class="row g-3 mb-4">

        <div class="col-md-4">
            <label class="form-label fw-bold">Filtrar por produto:</label>
            <select name="produto" class="form-select">
                <option value="">-- Todos --</option>
                <?php foreach ($produtos as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($filtro_produto == $p['id'] ? 'selected' : '') ?>>
                        <?= htmlspecialchars($p['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-success w-100">Filtrar</button>
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <a href="relatorio_contagem.php" class="btn btn-secondary w-100">Limpar filtro</a>
        </div>

        <div class="col-md-2 d-flex justify-content-space-around gap-3 ">

            <!-- 🔍 VISUALIZAR RELATÓRIO -->
            <button type="button" class="btn btn-primary" id="btn-visualizar">
                🔍 Visualizar Relatório
            </button>


            <!-- 📄 EXPORTAR PDF -->
            <a href="exportar_contagem_pdf.php?produto=<?= $filtro_produto ?>"
                class="btn btn-danger" target="_blank">
                📄 Exportar PDF
            </a>

            <!-- 📊 EXPORTAR EXCEL (Se quiser depois) -->
            <!-- <a href="exportar_contagem_excel.php?produto=<?= $filtro_produto ?>" 
       class="btn btn-success" target="_blank">
        📊 Excel
    </a> -->

        </div>


    </form>




    <!-- TABELA -->
    <div class="card shadow-sm">
        <div class="card-body">

            <?php if (empty($dados)): ?>
                <div class="alert alert-warning text-center">Nenhum registro encontrado.</div>
            <?php else: ?>

                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Produto</th>

                            <th>Penúltima Qtd</th>
                            <th>Data</th>

                            <th>Última Qtd</th>
                            <th>Data</th>



                            <th>Diferença</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($dados as $d): ?>
                            <?php
                            $dif = $d['diferenca'];

                            if ($dif > 0) $classe = "text-success fw-bold";   // ↑ aumentou
                            elseif ($dif < 0) $classe = "text-danger fw-bold"; // ↓ caiu
                            else $classe = "text-secondary fw-bold";           // = igual
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($d['produto']) ?></td>

                                <td><?= $d['penultima_qtd'] ?></td>
                                <td><?= $d['penultima_data'] ?></td>

                                <td><?= $d['ultima_qtd'] ?></td>
                                <td><?= $d['ultima_data'] ?></td>



                                <td class="<?= $classe ?>"><?= $dif ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>

        </div>
    </div>

</div>

<!-- Modal: Relatório -->
<div class="modal fade" id="modalRelatorio" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">📊 Relatório de Contagem</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="conteudo-relatorio">
        <div class="text-center text-muted">
            Carregando relatório...
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>

    </div>
  </div>
</div>
<script>
    document.getElementById('btn-visualizar').addEventListener('click', async () => {
    const modal = new bootstrap.Modal(document.getElementById('modalRelatorio'));
    modal.show();

    document.getElementById('conteudo-relatorio').innerHTML = "<div class='text-center text-muted'>Carregando...</div>";

    let produto = document.getElementById('filtro_produto')?.value || "";

    const res = await fetch("ajax_relatorio_contagem.php?produto=" + produto);
    const html = await res.text();

    document.getElementById('conteudo-relatorio').innerHTML = html;
});
</script>

<?php include 'includes/footer.php'; ?>