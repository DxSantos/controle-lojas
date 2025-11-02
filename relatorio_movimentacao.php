<?php
require 'config.php';
date_default_timezone_set('America/Sao_Paulo');

// Filtro opcional por tipo
$tipo_id = !empty($_GET['tipo_id']) ? (int)$_GET['tipo_id'] : null;

// Busca tipos
$tipos = $pdo->query("SELECT * FROM tipos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Busca produtos com saldo atual
$sql = "SELECT p.id, p.nome, p.tipo, COALESCE(sp.saldo,0) AS saldo
        FROM produtos p
        LEFT JOIN saldo_produtos sp ON sp.produto_id = p.id";
if ($tipo_id) $sql .= " WHERE p.tipo = :tipo_id";
$sql .= " ORDER BY p.tipo, p.nome";

$stmt = $pdo->prepare($sql);
if ($tipo_id) $stmt->bindValue(':tipo_id', $tipo_id, PDO::PARAM_INT);
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupa produtos por tipo
$produtos_por_tipo = [];
foreach ($produtos as $p) {
    $produtos_por_tipo[$p['tipo']][] = $p;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Relatório por Tipo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-tipo {
            margin-bottom: 20px;
        }

        .saldo {
            font-weight: bold;
        }

        .card-header {
            font-weight: bold;
            color: #fff;
        }

        .saldo-positivo {
            color: #28a745;
        }

        .saldo-zero {
            color: #6c757d;
        }

        .saldo-negativo {
            color: #dc3545;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <h3 class="mb-4">Relatório por Tipo</h3>

        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <label>Tipo</label>
                <select name="tipo_id" id="tipoFiltro" class="form-control">

                    <option value="">Todos</option>
                    <?php foreach ($tipos as $tipo): ?>
                        <option value="<?= $tipo['id'] ?>" <?= ($tipo_id == $tipo['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tipo['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="export_excel.php?tipo_id=<?= $tipo_id ?>" class="btn btn-success">Exportar Excel</a>
                <!-- Botão que abre o modal -->
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#pdfModal">
                    Exportar PDF
                </button>


            </div>
        </form>

        <div class="row">
            <?php foreach ($tipos as $tipo):
                $produtos_tipo = $produtos_por_tipo[$tipo['id']] ?? [];
                if (!$produtos_tipo) continue;
            ?>
                <div class="col-md-6">
                    <div class="card card-tipo">
                        <div class="card-header" style="background-color:#007bff;">
                            <?= htmlspecialchars($tipo['nome']) ?>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($produtos_tipo as $p):
                                        $saldoClass = $p['saldo'] > 0 ? 'saldo-positivo' : ($p['saldo'] == 0 ? 'saldo-zero' : 'saldo-negativo');
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['nome']) ?></td>
                                            <td class="<?= $saldoClass ?>"><?= $p['saldo'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
  <!-- Modal de exportação PDF -->
<div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="pdfModalLabel">Gerar Relatório em PDF</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body text-center">
        <!-- Conteúdo inicial -->
        <div id="pdfModalContent">
          <p>Deseja exportar o relatório atual filtrado como PDF?</p>
          <p class="text-muted small">Os dados serão gerados conforme o filtro selecionado.</p>
        </div>

        <!-- Área de carregamento oculta -->
        <div id="loadingArea" class="d-none">
          <div class="spinner-border text-danger mb-3" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Gerando...</span>
          </div>
          <p class="fw-bold text-danger">Gerando PDF, aguarde...</p>
        </div>
      </div>

      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button id="confirmExportBtn" type="button" class="btn btn-danger">
          Confirmar Exportação
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Script de controle do modal -->
<script>
document.getElementById('confirmExportBtn').addEventListener('click', function () {
  const modalContent = document.getElementById('pdfModalContent');
  const loadingArea = document.getElementById('loadingArea');
  
  // Mostra o spinner
  modalContent.classList.add('d-none');
  loadingArea.classList.remove('d-none');

  // Captura o valor do filtro selecionado
  const tipoSelect = document.getElementById('tipoFiltro'); // <-- ID do seu <select>
  const tipoSelecionado = tipoSelect ? tipoSelect.value : '';

  // Espera 1.5 segundos e abre o PDF
  setTimeout(() => {
    // Fecha o modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('pdfModal'));
    modal.hide();

    // Abre o PDF com base no filtro
    const url = `export_pdf.php?tipo_id=${encodeURIComponent(tipoSelecionado)}`;
    window.open(url, '_blank');

    // Restaura o modal
    modalContent.classList.remove('d-none');
    loadingArea.classList.add('d-none');
  }, 1500);
});
</script>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>