<?php
session_start();
require_once "config.php"; // Garante que $pdo está disponível

// 1. Verificação de Login
if (!isset($_SESSION["ID"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["ID"];
$limiteSegundos = 86400; // 24 horas (valor padrão)

// --- Variáveis de Exibição (Início) ---
$bloqueado = false;
$tempoRestante = 0;
$irradiacaoAtual = "—";
$valorFormatadoPR = "—";
$produtividadeAtual = "4.23 kWh/kWp"; // Valor mock
// --- Variáveis de Exibição (Fim) ---


try {
    // 2. LÓGICA DE STATUS: Calcula o tempo restante para exibir o TIMER, mas NÃO REDIRECIONA.
    $stmt = $pdo->prepare("
        SELECT UNIX_TIMESTAMP(DATA_MEDICAO) AS ultima_medicao_ts
        FROM medicoes
        WHERE FK_USUARIO_SISTEMA_ID = :uid AND Irrad_solar IS NOT NULL
        ORDER BY ID DESC
        LIMIT 1
    ");
    $stmt->execute([':uid' => $userId]);
    $ultimaMedicaoRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ultimaMedicaoRow) {
        $ultima = (int) $ultimaMedicaoRow['ultima_medicao_ts'];
        $agora = time();
        $diferenca = $agora - $ultima;

        // Se a diferença for menor que o limite (24h), o botão fica bloqueado
        if ($diferenca < $limiteSegundos) {
            $bloqueado = true;
            $tempoRestante = $limiteSegundos - $diferenca; // segundos faltando
        }
    }

    // 3. Último Valor de Irradiação Salvo (para exibir no card)
    $stmtIrrad = $pdo->prepare("
        SELECT Irrad_solar
        FROM medicoes
        WHERE FK_USUARIO_SISTEMA_ID = :uid AND Irrad_solar IS NOT NULL
        ORDER BY ID DESC
        LIMIT 1
    ");
    $stmtIrrad->execute([':uid' => $userId]);
    $irradValue = $stmtIrrad->fetchColumn();

    if ($irradValue !== false) {
        $irradiacaoAtual = number_format($irradValue, 1, ',', '.');
    }

    // 4. Performance Ratio (PR)
    $stmtPR = $pdo->query("SELECT PERFORMANCE_RATIO FROM indices LIMIT 1");
    if ($stmtPR && $stmtPR->rowCount() > 0) {
        $rowPR = $stmtPR->fetch(PDO::FETCH_ASSOC);
        $valorPR = $rowPR["PERFORMANCE_RATIO"];
        $valorFormatadoPR = number_format($valorPR * 100, 2, ',', '.')  . "%";
    }

} catch (Exception $e) {
    error_log("Erro ao buscar dados no desempenho.php: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoFuture - Desempenho e Irradiação Solar</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="styles/styles.css">
    <link rel="stylesheet" href="styles/header.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

    <style>
        /* Estilos adicionais para o card de estatísticas */
        .stats-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .stats-value {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 5px;
        }
        .stats-label {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        .stats-sublabel {
            font-size: 0.85rem;
            color: #495057;
        }
    </style>
</head>

<body class="performance-page">

<header class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.html">
            <i class="fas fa-solar-panel me-2"></i>EcoFuture
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="monitora.php">Voltar</a></li>
            </ul>
        </div>
    </div>
</header>

<section id="servicos" class="pt-5">
    <div class="container pt-5">
        <div class="row">
            <div class="col-lg-12 text-center mb-4"><br>
                <h2 class="section-title">Desempenho e Irradiação Solar</h2>
                <p class="section-subtitle">Visualize gráficos por dia, mês e ano e compare períodos para entender a performance.</p>
            </div>
        </div>

        <!-- Bloco de Exibição de Mensagens (Sucesso/Erro) -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="row justify-content-center">

            <!-- Card: Irradiação Solar -->
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stats-card text-center">
                    <i class="fas fa-sun stats-icon irradiacao" style="color:#f6b93b;"></i>

                    <div class="stats-value irradiacao" id="irradiacaoAtual">
                        <?= $irradiacaoAtual ?> kWh/m²
                    </div>

                    <div class="stats-label">Irradiação Solar</div>

                    <?php if (!$bloqueado): ?>
                        <div class="stats-sublabel text-success fw-bold">Pronto para atualizar!</div>

                        <!-- O form aponta para o gerador de irradiação -->
                        <form action="gerarIrradiacao.php" method="POST">
                            <button type="submit" class="btn btn-warning mt-3 w-100">
                                Gerar Nova Irradiação
                            </button>
                        </form>

                    <?php else: ?>

                        <div class="stats-sublabel text-danger fw-bold mt-2">
                            Disponível novamente em:
                        </div>

                        <div id="timer" class="fw-bold fs-4 text-warning mb-3">00:00:00</div>

                        <button class="btn btn-secondary mt-3 w-100" disabled>
                            Aguardando Tempo
                        </button>

                        <script>
                            let tempoRestante = <?= $tempoRestante ?>;

                            function atualizarTimer() {
                                if (tempoRestante <= 0) {
                                    document.getElementById("timer").innerHTML = "Liberado!";
                                    location.reload(); // atualiza automaticamente quando acabar
                                    return;
                                }

                                // Converte segundos → h:m:s
                                let horas = Math.floor(tempoRestante / 3600);
                                let minutos = Math.floor((tempoRestante % 3600) / 60);
                                let segundos = tempoRestante % 60;

                                horas = horas.toString().padStart(2, '0');
                                minutos = minutos.toString().padStart(2, '0');
                                segundos = segundos.toString().padStart(2, '0');

                                document.getElementById("timer").innerHTML = `${horas}:${minutos}:${segundos}`;

                                tempoRestante--;
                            }

                            atualizarTimer();
                            setInterval(atualizarTimer, 1000); 
                        </script>

                    <?php endif; ?>
                </div>
            </div>

            <!-- Card: Performance Ratio -->
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stats-card">
                    <i class="fas fa-tachometer-alt stats-icon performance" style="color:#1abc9c;"></i>
                    
                    <div class="stats-value performance">
                        <?= $valorFormatadoPR ?>
                    </div>

                    <div class="stats-label">Performance Ratio</div>
                    <div class="stats-sublabel">Eficiência do sistema</div>
                </div>
            </div>


            <!-- Card: Produtividade -->
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stats-card">
                    <i class="fas fa-chart-bar stats-icon produtividade" style="color:#3498db;"></i>
                    <div class="stats-value produtividade"><?= $produtividadeAtual ?></div>
                    <div class="stats-label">Produtividade</div>
                    <div class="stats-sublabel">Geração específica</div>
                </div>
            </div>

        </div>
    </div>
</section>

<footer class="bg-dark text-white py-4">
    <div class="container text-center">
        <p>&copy; 2025 EcoFuture. Energia limpa para um futuro sustentável.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>