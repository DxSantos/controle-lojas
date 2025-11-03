<?php
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// // Se não estiver logado, redireciona
// if (empty($_SESSION['usuario_id'])) {
//     header('Location: ../sections/login.php');
//     exit;
// }

require_once 'config.php';

if (empty($_SESSION['loja_id'])):
    // Busca todas as lojas
    $lojas = $pdo->query("SELECT id, nome FROM lojas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- Modal de Seleção de Loja -->
<div class="modal fade" id="modalLoja" tabindex="-1" aria-labelledby="modalLojaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalLojaLabel"><i class="bi bi-shop"></i> Selecionar Loja</h5>
      </div>
      <div class="modal-body">
        <form method="POST" action="selecionar_loja.php">
          <div class="mb-3">
            <label class="form-label fw-bold">Escolha a loja com a qual deseja trabalhar:</label>
            <select name="loja_id" class="form-select" required>
              <option value="">-- Selecione --</option>
              <?php foreach ($lojas as $loja): ?>
                <option value="<?= $loja['id'] ?>"><?= htmlspecialchars($loja['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-success">
              <i class="bi bi-check2-circle"></i> Confirmar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    const lojaModal = new bootstrap.Modal(document.getElementById('modalLoja'));
    lojaModal.show();
  });
</script>
<?php endif; ?>
