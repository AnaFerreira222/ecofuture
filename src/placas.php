<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoFuture - Strings de Placas</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="styles/styles.css">
    <link rel="stylesheet" href="styles/header.css">
</head>
<body>

<?php
session_start();
require_once 'config.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Atualiza a potência total do sistema
function atualizarPotenciaTotal($pdo, $id_usuario) {
    $sql = "SELECT potenciaNominal, Quantidade 
            FROM placasolar 
            WHERE fk_USUARIO_SISTEMA_ID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_usuario]);
    $strings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $potenciaTotal = 0;
    foreach ($strings as $s) {
        $potenciaTotal += $s['potenciaNominal'] * $s['Quantidade'];
    }

    $sqlUpdate = "UPDATE usuario_sistema 
                  SET POTENCIA_TOTAL = ? 
                  WHERE ID = ?";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([$potenciaTotal, $id_usuario]);
}

// Retorna potência total salva no banco
function obterPotenciaTotal($pdo, $id_usuario) {
    $sql = "SELECT POTENCIA_TOTAL FROM usuario_sistema WHERE ID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_usuario]);
    return $stmt->fetchColumn();
}

// Pega ID da sessão
$id_usuario = $_SESSION['ID'] ?? null;

if (!$id_usuario) {
    die("Erro: usuário não está logado.");
}

// --- CRIAÇÃO ---
if (isset($_POST['acao']) && $_POST['acao'] === 'adicionar') {
    $modelo = $_POST['modelo'];
    $potencia = $_POST['potencia'];
    $quantidade = $_POST['quantidade'];

    $sql = "INSERT INTO placasolar (Modelo, potenciaNominal, Quantidade, fk_USUARIO_SISTEMA_ID)
            VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$modelo, $potencia, $quantidade, $id_usuario]);

    atualizarPotenciaTotal($pdo, $id_usuario);
    header("Location: placas.php");
    exit;
}

// --- EDIÇÃO ---
if (isset($_POST['acao']) && $_POST['acao'] === 'editar') {
    $id_string = $_POST['id_string'];
    $modelo = $_POST['modelo'];
    $potencia = $_POST['potencia'];
    $quantidade = $_POST['quantidade'];

    $sql = "UPDATE placasolar SET Modelo = ?, potenciaNominal = ?, Quantidade = ? WHERE id_string = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$modelo, $potencia, $quantidade, $id_string]);

    atualizarPotenciaTotal($pdo, $id_usuario);
    header("Location: placas.php");
    exit;
}

// --- EXCLUSÃO ---
if (isset($_POST['acao']) && $_POST['acao'] === 'excluir') {
    $id_string = $_POST['id_string'];

    $sql = "DELETE FROM placasolar WHERE id_string = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_string]);

    atualizarPotenciaTotal($pdo, $id_usuario);
    header("Location: placas.php");
    exit;
}

// --- LISTAGEM ---
$sql = "SELECT * FROM placasolar WHERE fk_USUARIO_SISTEMA_ID = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_usuario]);
$placas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca potência total do banco
$potenciaTotalSistema = obterPotenciaTotal($pdo, $id_usuario);
?>

<!-- Cabeçalho -->
<header class="navbar navbar-expand-lg navbar-dark fixed-top bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.html">
            <i class="fas fa-solar-panel me-2"></i>EcoFuture
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="monitora.php">Voltar</a>
                </li>
            </ul>
        </div>
    </div>
</header>

<br><br><br>

<!-- Conteúdo principal -->
<section id="servicos">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Strings Instaladas</h2>
            <p class="section-subtitle">Painel de monitoramento</p>

            <h2 class="section-title">
                Potência Total do Sistema: 
                <?= number_format($potenciaTotalSistema / 1000, 2, ',', '.') ?> kW
            </h2>
        </div>

        <!-- Botão adicionar -->
        <div class="text-center mb-4">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAdicionar">
                <i class="fa-solid fa-plus me-2"></i>Adicionar nova string
            </button>
        </div>

        <!-- Cards -->
        <div class="row">
        <?php if (!empty($placas)): ?>
            <?php foreach ($placas as $p): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <a href="#" class="service-link text-decoration-none" data-bs-toggle="modal" data-bs-target="#modalString<?= $p['id_string'] ?>">
                        <div class="service-card h-100 text-center p-3 shadow-sm rounded" style="transition: transform 0.2s;">
                            <div class="service-icon mb-2">
                                <i class="fa-solid fa-solar-panel" style="color: #4a7c59; font-size: 40px;"></i>
                            </div>
                            <h4 class="fw-bold mb-2">String #<?= $p['id_string'] ?></h4>
                            <p class="text-muted mb-1">Modelo: <?= htmlspecialchars($p['Modelo']) ?></p>
                            <p class="text-muted mb-1">Placas: <?= htmlspecialchars($p['Quantidade']) ?></p>
                            <p class="text-muted mb-1">Potência total: 
                                <?= number_format(($p['potenciaNominal'] * $p['Quantidade']) / 1000, 2, ',', '.') ?> kW
                            </p>
                        </div>
                    </a>
                </div>

                <!-- Modal Detalhes -->
                <div class="modal fade" id="modalString<?= $p['id_string'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-success shadow-lg">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title">
                                    <i class="fa-solid fa-solar-panel me-2"></i>
                                    String #<?= $p['id_string'] ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body text-center">
                                <p><strong>Modelo:</strong> <?= htmlspecialchars($p['Modelo']) ?></p>
                                <p><strong>Potência nominal:</strong> <?= $p['potenciaNominal'] ?> W</p>
                                <p><strong>Quantidade:</strong> <?= $p['Quantidade'] ?></p>
                                <p><strong>Potência Total:</strong> 
                                    <?= number_format(($p['potenciaNominal'] * $p['Quantidade']) / 1000, 2, ',', '.') ?> kW
                                </p>
                            </div>

                            <div class="modal-footer justify-content-center">
                                <button class="btn btn-light" data-bs-target="#modalEditar<?= $p['id_string'] ?>" data-bs-toggle="modal">
                                    Editar
                                </button>

                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id_string" value="<?= $p['id_string'] ?>">
                                    <button class="btn btn-danger" type="submit">Excluir</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Editar -->
                <div class="modal fade" id="modalEditar<?= $p['id_string'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-warning shadow-lg">
                            <div class="modal-header bg-warning">
                                <h5 class="modal-title">Editar String #<?= $p['id_string'] ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="acao" value="editar">
                                    <input type="hidden" name="id_string" value="<?= $p['id_string'] ?>">

                                    <div class="mb-3">
                                        <label class="form-label">Modelo:</label>
                                        <input type="text" name="modelo" class="form-control" value="<?= htmlspecialchars($p['Modelo']) ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Potência nominal (W):</label>
                                        <input type="number" name="potencia" class="form-control" value="<?= $p['potenciaNominal'] ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Quantidade:</label>
                                        <input type="number" name="quantidade" class="form-control" value="<?= $p['Quantidade'] ?>" required>
                                    </div>
                                </div>

                                <div class="modal-footer justify-content-center">
                                    <button type="submit" class="btn btn-success">Salvar alterações</button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

        <?php else: ?>
            <div class="text-center">
                <p class="text-muted">Nenhuma string cadastrada ainda.</p>
            </div>
        <?php endif; ?>
        </div>
    </div>
</section>


<!-- Modal Adicionar -->
<div class="modal fade" id="modalAdicionar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-success shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Adicionar Nova String</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="adicionar">

                    <div class="mb-3">
                        <label class="form-label">Modelo:</label>
                        <input type="text" name="modelo" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Potência nominal (W):</label>
                        <input type="number" name="potencia" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantidade:</label>
                        <input type="number" name="quantidade" class="form-control" required>
                    </div>
                </div>

                <div class="modal-footer justify-content-center">
                    <button type="submit" class="btn btn-success">Adicionar</button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
