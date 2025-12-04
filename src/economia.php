<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["ID"])) {
    die("Usuário não autenticado.");
}

$userId = (int) $_SESSION["ID"];

/* ------------------------------------------------------
   1) DATA DE INÍCIO DO SISTEMA (indices)
------------------------------------------------------ */
$stmt = $pdo->prepare("
    SELECT DATA_INICIO 
    FROM indices 
    WHERE FK_USUARIO_SISTEMA_ID = :uid
    ORDER BY ID ASC
    LIMIT 1
");
$stmt->execute([':uid' => $userId]);
$dataInicio = $stmt->fetchColumn();

if (!$dataInicio) {
    $dataInicio = date('Y-m-d');
}

$dataInicioFormatada = date('d/m/Y', strtotime($dataInicio));

/* ------------------------------------------------------
   2) MEDIÇÕES DESDE DATA DE INÍCIO
------------------------------------------------------ */
$stmt = $pdo->prepare("
    SELECT DATA_MEDICAO, ENERGIA_GERADA, CONSUMO, TARIFA
    FROM medicoes
    WHERE FK_USUARIO_SISTEMA_ID = :uid
    AND DATA_MEDICAO >= :dataIni
    ORDER BY DATA_MEDICAO ASC
");
$stmt->execute([
    ':uid' => $userId,
    ':dataIni' => $dataInicio
]);

$medicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ------------------------------------------------------
   3) CÁLCULO DA ECONOMIA TOTAL
------------------------------------------------------ */
$totalEconomia_kWh = 0;
$totalEconomia_Reais = 0;

$graficoLabels = [];
$graficoEconomia = [];

$acumulado = 0;

foreach ($medicoes as $m) {

    $gerado = floatval($m['ENERGIA_GERADA']);
    $consumo = floatval($m['CONSUMO']);

    // tarifa corrigida (se vier 0 usa padrão)
    $tarifa = floatval($m['TARIFA']);
    if ($tarifa <= 0) { 
        $tarifa = 0.92; 
    }

    $economiaHoje = $gerado - $consumo;
    // Garantir que economia negativa vire zero
if ($economiaHoje < 0) $economiaHoje = 0;

// SALVAR NO BANCO O VALOR DA ECONOMIA DO DIA
try {
    $stmtSave = $pdo->prepare("
        UPDATE medicoes 
        SET VALOR_ECONOMIA = :economia
        WHERE FK_USUARIO_SISTEMA_ID = :uid
        AND DATE(DATA_MEDICAO) = DATE(:data)
        LIMIT 1
    ");

    $stmtSave->execute([
        ':economia' => $economiaHoje,
        ':uid' => $userId,
        ':data' => $m["DATA_MEDICAO"]
    ]);

} catch (Exception $e) {
    error_log("Erro ao salvar economia do dia: " . $e->getMessage());
}

    if ($economiaHoje < 0) $economiaHoje = 0;

    $totalEconomia_kWh += $economiaHoje;
    $totalEconomia_Reais += ($economiaHoje * $tarifa);

    

    // Gráfico
    $acumulado += $economiaHoje;
    $graficoLabels[] = date("d/m", strtotime($m["DATA_MEDICAO"]));
    $graficoEconomia[] = round($acumulado, 2);
}

$exibir_kWh = number_format($totalEconomia_kWh, 2, ',', '.');
$exibir_reais = number_format($totalEconomia_Reais, 2, ',', '.');

$dadosGraficoJson = json_encode([
    "labels" => $graficoLabels,
    "economia" => $graficoEconomia
]);

/* -----------------------------------------
   EQUIVALENTES DE IMPACTO AMBIENTAL
------------------------------------------ */

// 1 árvore = 20 kg CO₂/ano
// 1 kWh solar = reduz 0.084 kg CO₂

$co2_evitado = $totalEconomia_kWh * 0.084; // kg CO2
$arvores = $co2_evitado / 20;

$co2_exibir = number_format($co2_evitado, 2, ',', '.');
$arvores_exibir = number_format($arvores, 2, ',', '.');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>EcoFuture - Economia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="styles/styles.css">
    <link rel="stylesheet" href="styles/header.css">

    <style>
        body { background:#f8f9fa; }

        .stats-card {
            background:white;
            border-radius:12px;
            padding:20px;
            box-shadow:0 6px 18px rgba(0,0,0,0.06);
            transition: .3s;
        }
        .stats-card:hover { transform:translateY(-5px); }

        .stats-value {
            font-size:32px;
            font-weight:700;
            color:#2e5e35;
        }
        .chart-card {
            background:white;
            border-radius:12px;
            padding:25px;
            box-shadow:0 6px 18px rgba(0,0,0,0.06);
        }
    </style>
</head>

<body>

<header class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.html">
            <i class="fas fa-solar-panel me-2"></i>EcoFuture
        </a>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link" href="monitora.php">Voltar</a></li>
        </ul>
    </div>
</header>

<br><br><br>

<div class="container">

    <h2 class="text-center mb-2" style="color:#2e5e35; font-weight:700;">
        Sua Economia Total
    </h2>

    <p class="text-center text-muted mb-5">
        Desde <?= $dataInicioFormatada ?>
    </p>

    <div class="row justify-content-center mb-4">

        <!-- ECONOMIA EM KWH -->
        <div class="col-md-4 mb-3">
            <div class="stats-card text-center">
                <div class="stats-value"><?= $exibir_kWh ?> kWh</div>
                <div class="text-muted mt-2">Economia Total em Energia</div>
            </div>
        </div>

        <!-- ECONOMIA EM REAIS -->
        <div class="col-md-4 mb-3">
            <div class="stats-card text-center">
                <div class="stats-value">R$ <?= $exibir_reais ?></div>
                <div class="text-muted mt-2">Economia Total em Reais</div>
            </div>
        </div>
    </div>

    <!-- Impacto Ambiental -->
    <div class="row justify-content-center mb-5">
        <div class="col-md-4 mb-3">
            <div class="stats-card text-center">
                <div class="stats-value"><?= $co2_exibir ?> kg</div>
                <div class="text-muted mt-2">CO₂ evitado</div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="stats-card text-center">
                <div class="stats-value"><?= $arvores_exibir ?></div>
                <div class="text-muted mt-2">Árvores equivalentes</div>
            </div>
        </div>
    </div>

    <!-- GRÁFICO -->
    <div class="row mb-5">
        <div class="col-lg-8 offset-lg-2">
            <div class="chart-card">
                <h5 class="text-center text-muted mb-3">Economia Acumulada (kWh)</h5>
                <canvas id="graficoEconomia"></canvas>
            </div>
        </div>
    </div>

</div>

<footer class="bg-dark text-white text-center py-4 mt-5">
    © 2025 EcoFuture
</footer>

<script>
const dados = <?= $dadosGraficoJson ?>;

const ctx = document.getElementById('graficoEconomia').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: dados.labels,
        datasets: [{
            label: 'Economia Acumulada (kWh)',
            data: dados.economia,
            borderColor: '#2e5e35',
            backgroundColor: 'rgba(46, 94, 53, 0.2)',
            fill: true,
            tension: 0.3,
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

</body>
</html>
