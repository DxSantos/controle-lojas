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

// Verifica permissão "contagem"
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
            ⚠️ Nenhuma loja selecionada. Selecione uma loja para continuar.
          </div>";
    include 'includes/footer.php';
    exit;
}

// ===== TIPOS QUE APARECEM NA CONTAGEM =====
// visualizar_movimentos = 0 (apenas contagem)
$tipos = $pdo->query("
    SELECT * 
    FROM tipos 
    WHERE visualizar_movimentos = 0 
    ORDER BY nome
")->fetchAll(PDO::FETCH_ASSOC);

// ===== PRODUTOS POR TIPO =====
$produtos_por_tipo = [];
foreach ($tipos as $tipo) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE tipo = ? ORDER BY nome");
    $stmt->execute([$tipo['id']]);
    $produtos_por_tipo[$tipo['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ===== ÚLTIMA CONTAGEM =====
$ultima_stmt = $pdo->prepare("
    SELECT produto_id, quantidade 
    FROM view_ultima_contagem
    WHERE loja_id = ?
");
$ultima_stmt->execute([$loja_id]);

$ultima_contagem = [];
foreach ($ultima_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $ultima_contagem[$row['produto_id']] = $row['quantidade'];
}


// ===== SALDOS REAIS =====
$saldos_stmt = $pdo->prepare("SELECT produto_id, saldo FROM saldo_produtos WHERE loja_id = ?");
$saldos_stmt->execute([$loja_id]);

$saldos = [];
foreach ($saldos_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $saldos[$row['produto_id']] = $row['saldo'];
}
?>

<div class="container py-4">

    <h3 class="mb-4">🔢 Contagem de Produtos</h3>

    <!-- ALERTA DE SUCESSO -->
    <div id="alerta-salvo" class="alert alert-success alert-dismissible fade" role="alert" style="display:none;">
        <strong id="alerta-texto"></strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>



    <form id="form_contagem" method="POST" action="salvar_contagem.php">

        <input type="hidden" name="tipo_registro" value="contagem">
        <input type="hidden" name="loja_id" value="<?= $loja_id ?>">


        <!-- Botões -->
        <div class="d-flex justify-content-between flex-wrap mb-3">
            <button type="button" id="btn-guardar" class="btn btn-outline-warning fw-bold">
                Guardar Valores
            </button>

            <button type="submit" id="btn-salvar" class="btn btn-outline-success fw-bold">
                Salvar Contagem
            </button>
        </div>

        <!-- Mostrar guardados -->
        <div class="mb-2">
            <button type="button" id="btn-ver-valores" class="btn btn-info" style="display:none;">
                Valores Guardados
            </button>
        </div>

        <!-- Produtos -->
        <div class="row">
            <?php foreach ($tipos as $tipo): ?>
                <div class="col-md-6">
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-success text-white fw-bold">
                            <?= htmlspecialchars($tipo['nome']) ?>
                        </div>

                        <div class="card-body">
                            <?php
                            $produtos = $produtos_por_tipo[$tipo['id']] ?? [];
                            if ($produtos):
                                foreach ($produtos as $produto):
                                    $pid = $produto['id'];
                                    $saldo = $saldos[$pid] ?? 0;
                            ?>
                                    <div class="produto-item d-flex justify-content-between align-items-center border-bottom py-2">

                                        <span class="produto-nome">
                                            <?= htmlspecialchars($produto['nome']) ?>
                                            <?php
                                            $ultimo = $ultima_contagem[$pid] ?? 0;
                                            ?>
                                            <span class="badge bg-light text-dark">
                                                Última Contagem: <?= $ultimo ?>
                                            </span>

                                        </span>

                                        <div class="quantidade-control d-flex align-items-center">
                                            <button type="button" class="btn btn-outline-secondary btn-minus">-</button>

                                            <input type="number"
                                                name="quantidade[<?= $pid ?>]"
                                                value="0" min="0"
                                                class="form-control text-center mx-1" style="width:70px;">

                                            <button type="button" class="btn btn-outline-secondary btn-plus">+</button>
                                        </div>

                                    </div>
                                <?php endforeach;
                            else: ?>
                                <p class="text-muted">Nenhum produto neste tipo.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </form>


    <!-- Resumo -->
    <div id="resumo-flutuante" class="card position-fixed bg-success text-white shadow-lg p-3"
        style="top: 90px; right: 25px; width: 350px; display:none; opacity:0; z-index:1000;">

        <h5 class="text-center mb-2">Resumo Guardado</h5>

        <table class="table table-sm table-bordered table-light text-dark mb-0">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Qtd</th>
                </tr>
            </thead>
            <tbody id="resumo-body"></tbody>
        </table>

        <button class="btn btn-sm btn-dark mt-2 w-100" id="fechar-resumo">Fechar</button>
    </div>

</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // BOTÕES + E -
    document.querySelectorAll('.btn-plus').forEach(btn => {
        btn.addEventListener('click', () => {
            let input = btn.previousElementSibling;
            input.value = parseInt(input.value || 0) + 1;
        });
    });

    document.querySelectorAll('.btn-minus').forEach(btn => {
        btn.addEventListener('click', () => {
            let input = btn.nextElementSibling;
            let v = parseInt(input.value || 0);
            if (v > 0) input.value = v - 1;
        });
    });

    // ========================================================================
    // CARREGAR VALORES GUARDADOS
    // ========================================================================
    async function carregarValoresGuardadosContagem() {
        try {
            const res = await fetch("get_contagem_guardada.php");
            const json = await res.json();

            if (json.status === "ok") {
                let valores = json.valores || {};

                document.querySelectorAll('input[name^="quantidade"]').forEach(i => i.value = 0);

                for (const pid in valores) {
                    let input = document.querySelector(`input[name="quantidade[${pid}]"]`);
                    if (input) {
                        input.value = valores[pid];
                    }
                }

                if (Object.keys(valores).length > 0) {
                    document.getElementById('btn-ver-valores').style.display = "inline-block";
                }
            }

        } catch (e) {
            console.log("Erro:", e);
        }
    }

    document.body.onload = carregarValoresGuardadosContagem;

    // ========================================================================
    // GUARDAR VALORES
    // ========================================================================
    document.getElementById('btn-guardar').addEventListener('click', async () => {

        let valores = {};
        document.querySelectorAll('input[name^="quantidade"]').forEach(input => {
            let v = parseInt(input.value || 0);
            if (v > 0) {
                const match = input.name.match(/\[(\d+)\]/);
                if (match) {
                    valores[match[1]] = v;
                }
            }
        });

        const res = await fetch("guardar_contagem.php", {
            method: "POST",
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                quantidades: valores
            })
        });

        montarResumo(valores);
    });

    // ========================================================================
    // RESUMO
    // ========================================================================
    function montarResumo(valores) {

        const resumo = document.getElementById('resumo-flutuante');
        const corpo = document.getElementById('resumo-body');
        const btnVer = document.getElementById('btn-ver-valores');

        corpo.innerHTML = "";

        let count = 0;

        for (const pid in valores) {
            count++;
            let input = document.querySelector(`input[name="quantidade[${pid}]"]`);
            let nome = input.closest('.produto-item').querySelector('.produto-nome').innerText;

            let tr = document.createElement('tr');
            tr.innerHTML = `<td>${nome}</td><td>${valores[pid]}</td>`;
            corpo.appendChild(tr);
        }

        if (count > 0) {
            resumo.style.display = 'block';
            resumo.style.opacity = '1';
            btnVer.style.display = "inline-block";

            setTimeout(() => {
                resumo.style.opacity = '0';
                setTimeout(() => resumo.style.display = 'none', 500);
            }, 8000);
        }
    }

    document.getElementById('btn-ver-valores').addEventListener('click', () => {
        const r = document.getElementById('resumo-flutuante');
        r.style.display = 'block';
        r.style.opacity = '1';
    });

    document.getElementById('fechar-resumo').addEventListener('click', () => {
        const r = document.getElementById('resumo-flutuante');
        r.style.opacity = '0';
        setTimeout(() => r.style.display = 'none', 500);
    });

    // ========================================================================
    // LIMPAR AO SALVAR DEFINITIVO
    // ========================================================================
    // ALERTA AO SALVAR CONTAGEM
    document.getElementById('form_contagem').addEventListener('submit', async (e) => {
        e.preventDefault(); // impede envio imediato

        const alerta = document.getElementById('alerta-salvo');
        const alertaTexto = document.getElementById('alerta-texto');

        // Texto do alerta
        alertaTexto.textContent = "✔ Contagem salva com sucesso!";

        // Exibe alerta
        alerta.style.display = "block";
        alerta.classList.add("show");

        // Limpa valores guardados
        await fetch("limpar_contagem_guardada.php", {
            method: 'POST'
        });

        // Envia o formulário de verdade após 2 segundos
        setTimeout(() => {
            e.target.submit();
        }, 8000);
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>