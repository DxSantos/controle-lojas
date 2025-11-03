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

// Bloqueia se o usuário não tiver permissão "movimentacao"
if (!verificaPermissao('movimentacao')) {
    echo "<div class='alert alert-danger m-4 text-center'>
            🚫 Você não tem permissão para acessar esta página.
          </div>";
    include 'includes/footer.php';
    exit;
}

// ----- PERMISSÕES DE USUÁRIO BOTÕES -----
$canEnviar = verificaPermissao('envios'); // 🔹 checa se o usuário pode mexer em envios
$canEstoque = verificaPermissao('estoque'); // 🔹 checa se o usuário pode mexer em estoque
$canVendas = verificaPermissao('vendas'); // 🔹 checa se o usuário pode mexer em vendas


// ----- LISTAR TIPOS E PRODUTOS -----
$tipos = $pdo->query("SELECT * FROM tipos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Agrupa produtos por tipo
$produtos_por_tipo = [];
foreach ($tipos as $tipo) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE tipo = ? ORDER BY nome");
    $stmt->execute([$tipo['id']]);
    $produtos_por_tipo[$tipo['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar todos os saldos de uma vez (produto_id => saldo)
$saldos_stmt = $pdo->query("SELECT produto_id, saldo FROM saldo_produtos");
$saldos = [];
if ($saldos_stmt) {
    $rows = $saldos_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $saldos[$r['produto_id']] = $r['saldo'];
    }
}
?>

<?php require 'includes/header.php'; ?>


<body>
    <div class="container py-4">



        <form id="form_quantidade" method="POST" action="salvar_quantidade.php">

            <h3 class="mb-4">Movimentações de Estoque - Vendas / Envios / Estoque</h3>

            <!-- Botões principais no topo -->
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                <div id="modo-container" class="btn-group" role="group" aria-label="Modos">
                    <?php if ($canVendas): ?>
                        <button type="button" class="btn btn-outline-primary modo-btn active" data-modo="vendas">Vendas</button>
                    <?php endif; ?>

                    <?php if ($canEnviar): ?>
                        <button type="button" class="btn btn-outline-warning modo-btn" data-modo="envios">Envios</button>
                    <?php endif; ?>
                    
                    <?php if ($canEstoque): ?>
                        <button type="button" class="btn btn-outline-success modo-btn" data-modo="estoque">Estoque</button>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" id="btn-guardar" class="btn btn-outline-warning" style="font-weight: bold;">Guardar Valores</button>
                    <button type="submit" id="btn-salvar-banco" class="btn btn-outline-success" style="font-weight: bold;">Salvar no Banco</button>
                </div>
            </div>

            <!-- Alerta Bootstrap -->
            <div id="alerta-salvo" class="alert alert-success alert-dismissible fade" role="alert" style="display:none;">
                <strong id="alerta-texto"></strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <!-- Botão valores guardados -->
            <div class="mb-3">
                <button type="button" id="btn-ver-valores" class="btn btn-info" style="display:none;">
                    Valores Guardados
                </button>
            </div>


            <input type="hidden" name="tipo_registro" id="tipo_registro" value="vendas">

            <div class="row">
                <?php foreach ($tipos as $tipo): ?>
                    <div class="col-md-6">
                        <div class="produto-card card">
                            <div class="card-header text-white card-header-tipo" style="background-color: #0d6efd;">
                                <?= htmlspecialchars($tipo['nome']) ?>
                            </div>
                            <div class="card-body">
                                <?php
                                $produtos = $produtos_por_tipo[$tipo['id']] ?? [];
                                if ($produtos):
                                    foreach ($produtos as $produto):
                                        $produtoId = $produto['id'];
                                        $saldo_texto = isset($saldos[$produtoId]) ? $saldos[$produtoId] : '0';
                                ?>
                                        <div class="produto-item">
                                            <span class="produto-nome">
                                                <?= htmlspecialchars($produto['nome']) ?>
                                                <span class="badge bg-light text-dark">(Saldo Atual: <?= htmlspecialchars((string)$saldo_texto) ?>)</span>
                                            </span>

                                            <div class="quantidade-control">
                                                <button type="button" class="btn btn-outline-secondary btn-minus">-</button>
                                                <input type="number" name="quantidade[<?= $produtoId ?>]" value="0" min="0">
                                                <button type="button" class="btn btn-outline-secondary btn-plus">+</button>
                                            </div>
                                        </div>
                                    <?php endforeach;
                                else: ?>
                                    <p class="text-muted">Nenhum produto cadastrado neste tipo.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>



        </form>

        <!-- Resumo Flutuante -->
        <div id="resumo-flutuante">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 id="resumo-titulo" class="mb-0">Resumo Guardado - Vendas</h5>
                <button type="button" id="fechar-resumo" class="btn btn-sm btn-light">Fechar</button>
            </div>
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Produto</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody id="resumo-body"></tbody>
            </table>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const form = document.getElementById('form_quantidade');
        const btnGuardar = document.getElementById('btn-guardar');
        const resumo = document.getElementById('resumo-flutuante');
        const resumoBody = document.getElementById('resumo-body');
        const tipoRegistroInput = document.getElementById('tipo_registro');
        const cardHeaders = document.querySelectorAll('.card-header-tipo');
        const btnVerValores = document.getElementById('btn-ver-valores');
        const alertaSalvo = document.getElementById('alerta-salvo');
        const modoBtns = document.querySelectorAll('.modo-btn');
        const resumoTitulo = document.getElementById('resumo-titulo');
        const fecharResumoBtn = document.getElementById('fechar-resumo');
        const btnSalvarBanco = document.getElementById('btn-salvar-banco');

        // Inicializa em VENDAS
        let modoAtual = 'vendas';

        document.body.onload = async () => {
            atualizarCores();
            document.querySelector('.toggle-thumb')?.remove?.(); // se existir por acaso
            await carregarValoresGuardados(modoAtual);
            // definir texto do botão salvar como modo atual (opcional)
            tipoRegistroInput.value = modoAtual;
        };

        // Modo click handlers
        modoBtns.forEach(btn => {
            btn.addEventListener('click', async () => {
                modoBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                modoAtual = btn.dataset.modo;
                tipoRegistroInput.value = modoAtual;

                atualizarCores();
                resumoTitulo.textContent = 'Resumo Guardado - ' + capitalizar(modoAtual);
                await carregarValoresGuardados(modoAtual);
            });
        });

        function capitalizar(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function atualizarCores() {
            let cor = '#6c757d';
            if (modoAtual === 'vendas') cor = '#0d6efd'; // azul
            else if (modoAtual === 'envios') cor = '#ffc107'; // amarelo
            else if (modoAtual === 'estoque') cor = '#198754'; // verde

            cardHeaders.forEach(header => {
                header.style.backgroundColor = cor;
            });
            resumo.style.backgroundColor = cor;
            resumo.style.color = '#fff';

            // ajustar classes dos botões para refletir o modo
            modoBtns.forEach(btn => {
                btn.classList.remove('btn-primary', 'btn-warning', 'btn-success');
                if (btn.dataset.modo === 'vendas') {
                    btn.classList.add(modoAtual === 'vendas' ? 'btn-primary' : 'btn-outline-primary');
                } else if (btn.dataset.modo === 'envios') {
                    btn.classList.add(modoAtual === 'envios' ? 'btn-warning' : 'btn-outline-warning');
                } else if (btn.dataset.modo === 'estoque') {
                    btn.classList.add(modoAtual === 'estoque' ? 'btn-success' : 'btn-outline-success');
                }
            });
        }

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

        // --- Guardar valores via AJAX e carregar valores guardados ao alternar modo ---

        function coletarValoresDaTela() {
            const inputs = form.querySelectorAll('input[name^="quantidade"]');
            const valores = {};
            inputs.forEach(input => {
                const val = parseInt(input.value || 0);
                if (val > 0) {
                    const match = input.name.match(/\[(\d+)\]/);
                    if (match) {
                        valores[match[1]] = val;
                    }
                }
            });
            return valores;
        }

        async function enviarGuardar(valores, tipo) {
            try {
                const res = await fetch('guardar_valores.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        quantidades: valores,
                        tipo: tipo
                    })
                });
                return await res.json();
            } catch (err) {
                console.error('Erro ao salvar valores guardados:', err);
                return {
                    status: 'erro',
                    msg: err.message
                };
            }
        }

        async function carregarValoresGuardados(tipo) {
            try {
                const res = await fetch('get_valores_guardados.php?tipo=' + encodeURIComponent(tipo));
                const json = await res.json();
                if (json.status === 'ok') {
                    form.querySelectorAll('input[name^="quantidade"]').forEach(i => i.value = 0);
                    const map = json.valores || {};
                    for (const produto_id in map) {
                        const input = form.querySelector(`input[name="quantidade[${produto_id}]"]`);
                        if (input) input.value = map[produto_id];
                    }
                    const tem = Object.keys(map).length > 0;
                    btnVerValores.style.display = tem ? 'inline-block' : 'none';
                } else {
                    btnVerValores.style.display = 'none';
                }
            } catch (err) {
                console.error('Erro ao carregar valores guardados:', err);
                btnVerValores.style.display = 'none';
            }
        }

        // Guardar valores
        btnGuardar.addEventListener('click', async () => {
            const valores = coletarValoresDaTela();
            if (Object.keys(valores).length === 0) {
                resumoBody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">Nenhum valor a guardar</td></tr>';
                resumo.style.display = 'block';
                resumo.style.opacity = '1';
                setTimeout(() => {
                    resumo.style.opacity = '0';
                    setTimeout(() => resumo.style.display = 'none', 800)
                }, 1000);
                return;
            }

            const r = await enviarGuardar(valores, modoAtual);
            if (r.status === 'ok') {
                resumoBody.innerHTML = '';
                for (const pid in valores) {
                    const produtoEl = (form.querySelector(`input[name="quantidade[${pid}]"]`).closest('.produto-item').querySelector('.produto-nome').innerText) || 'Produto';
                    const row = document.createElement('tr');
                    // escape simples para evitar injeção via texto (produtoEl vem do DOM)
                    const tdProduto = document.createElement('td');
                    tdProduto.textContent = produtoEl;
                    const tdQuantidade = document.createElement('td');
                    tdQuantidade.textContent = valores[pid];
                    row.appendChild(tdProduto);
                    row.appendChild(tdQuantidade);
                    resumoBody.appendChild(row);
                }

                resumo.style.display = 'block';
                resumo.style.opacity = '1';
                resumo.classList.add('glow');
                btnVerValores.style.display = 'inline-block';

                setTimeout(() => {
                    resumo.style.opacity = '0';
                    resumo.classList.remove('glow');
                    setTimeout(() => resumo.style.display = 'none', 800);
                }, 8000);
            } else {
                alert('Erro ao guardar valores: ' + (r.msg || 'erro desconhecido'));
            }
        });

        // btnVerValores abre resumo
        btnVerValores.addEventListener('click', () => {
            resumo.style.display = 'block';
            resumo.style.opacity = '1';
        });

        // Fechar resumo
        fecharResumoBtn.addEventListener('click', () => {
            resumo.style.opacity = '0';
            setTimeout(() => resumo.style.display = 'none', 350);
        });

        // Ao salvar no banco
        form.addEventListener('submit', async (e) => {
            // desabilita botão para evitar cliques duplos
            btnSalvarBanco.disabled = true;

            // Limpa os valores guardados apenas do modo atual
            try {
                const res = await fetch('limpar_valores_guardados.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'tipo=' + encodeURIComponent(modoAtual)
                });
                const json = await res.json();
                if (json.status !== 'ok') {
                    console.error('Erro ao limpar valores guardados:', json.msg);
                }
            } catch (err) {
                console.error('Erro ao limpar valores guardados:', err);
            }

            // Mostra alerta de sucesso
            const alertaTexto = document.getElementById('alerta-texto');
            alertaTexto.textContent = `Sucesso! Valores de ${modoAtual.toUpperCase()} salvos no banco.`;

            alertaSalvo.classList.remove('alert-warning', 'alert-info', 'alert-success');
            if (modoAtual === 'vendas') alertaSalvo.classList.add('alert-primary');
            else if (modoAtual === 'envios') alertaSalvo.classList.add('alert-warning');
            else if (modoAtual === 'estoque') alertaSalvo.classList.add('alert-success');

            alertaSalvo.style.display = 'block';
            alertaSalvo.classList.add('show');

            // Recarrega a página após alguns segundos
            setTimeout(() => {
                location.reload();
            }, 2500);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/footer.php'; ?>

</body>

</html>