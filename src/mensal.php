<?php
// relatorio_mensal.php (Corrigido com JOIN para Saldo)

session_start();
require_once "config.php"; // Certifique-se de que a conexão PDO está incluída

// 1. Verifica se o usuário está logado
if (!isset($_SESSION["ID"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["ID"];
$registros = [];
$totais = [
    'irradiacao_total' => 0,
    'consumo_total' => 0,
    'energia_gerada_total' => 0,
    'saldo_energetico_recente' => 0 // Saldo é o último valor
];

try {
    // 2. Consulta para obter os últimos 30 registros usando JOIN
    // Juntamos MEDICOES (Irrad/Consumo/Geração) com INDICES (Saldo)
    $sql = "
        SELECT 
            DATE_FORMAT(m.DATA_MEDICAO, '%d/%m/%Y') AS data_formatada,
            m.Irrad_solar,
            m.Consumo,
            m.Energia_Gerada,
            i.SALDO_ENERGETICO
        FROM medicoes m
        LEFT JOIN indices i ON m.FK_USUARIO_SISTEMA_ID = i.FK_USUARIO_SISTEMA_ID 
                           AND DATE(m.DATA_MEDICAO) = i.DATA_INICIO
        WHERE m.FK_USUARIO_SISTEMA_ID = :uid
        ORDER BY m.DATA_MEDICAO DESC, m.ID DESC 
        LIMIT 30
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':uid', $userId);
    $stmt->execute();
    
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Cálculo dos Totais Mensais (últimos 30 registros)
    foreach ($registros as $index => $registro) {
        $totais['irradiacao_total']      += (float) $registro['Irrad_solar'];
        $totais['consumo_total']         += (float) $registro['Consumo'];
        $totais['energia_gerada_total']  += (float) $registro['Energia_Gerada'];
        
        // O Saldo deve ser o valor do registro MAIS RECENTE
        if ($index === 0) {
            $totais['saldo_energetico_recente'] = (float) $registro['SALDO_ENERGETICO'];
        }
    }

} catch (PDOException $e) {
    $registros = [];
    $erro = "Erro ao carregar dados: " . $e->getMessage();
}

// 4. Funções de formatação
function formatarKWh($valor) {
    return number_format($valor, 2, ',', '.') . " kWh";
}
function formatarIrradiacao($valor) {
    return number_format($valor, 2, ',', '.') . " kWh/m²";
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Mensal - EcoFuture</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="styles/styles.css"> 
    <link rel="stylesheet" href="styles/header.css">
    
    <style>
        .report-header {
            background-color: #1a5e55; 
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .report-card {
            background-color: #ffffff;
            border-left: 5px solid;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .irrad-card { border-left-color: #f6b93b; }
        .consumo-card { border-left-color: #e74c3c; }
        .gerada-card { border-left-color: #2ecc71; }
        .saldo-card { border-left-color: #3498db; }
        .stats-label { font-weight: 500; color: #555; }
        .stats-value { font-size: 1.8rem; font-weight: 700; margin-top: 5px; }
        .table-responsive { max-height: 400px; overflow-y: auto; }
    </style>
</head>

<body class="performance-page">

<header class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.html">
            <i class="fas fa-solar-panel me-2"></i>EcoFuture
        </a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="monitora.php">Voltar</a></li>
            </ul>
        </div>
    </div>
</header>

<div class="container" style="padding-top: 100px;">
    
    <div class="report-header text-center">
        <i class="fas fa-chart-line fa-2x mb-2"></i>
        <h2 class="text-white">Relatório Mensal de Desempenho</h2>
        <p class="lead mb-0">Análise dos últimos **<?= count($registros) ?>** registros de medição.</p>
    </div>

    <?php if (isset($erro)): ?>
        <div class="alert alert-danger" role="alert"><?= $erro ?></div>
    <?php endif; ?>

    <div class="row mb-5">
        
        <div class="col-md-3">
            <div class="report-card irrad-card">
                <i class="fas fa-sun fa-2x float-end" style="color:#f6b93b;"></i>
                <div class="stats-label">Irradiação Solar Total (Soma)</div>
                <div class="stats-value text-warning">
                    <?= formatarIrradiacao($totais['irradiacao_total']) ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="report-card consumo-card">
                <i class="fas fa-plug fa-2x float-end" style="color:#e74c3c;"></i>
                <div class="stats-label">Consumo Total (Soma)</div>
                <div class="stats-value text-danger">
                    <?= formatarKWh($totais['consumo_total']) ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="report-card gerada-card">
                <i class="fas fa-bolt fa-2x float-end" style="color:#2ecc71;"></i>
                <div class="stats-label">Energia Gerada Total (Soma)</div>
                <div class="stats-value text-success">
                    <?= formatarKWh($totais['energia_gerada_total']) ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="report-card saldo-card">
                <i class="fas fa-chart-line fa-2x float-end" style="color:#3498db;"></i>
                <div class="stats-label">Saldo Energético (Recente)</div>
                <div class="stats-value text-primary">
                    <?= formatarKWh($totais['saldo_energetico_recente']) ?>
                </div>
            </div>
        </div>

    </div>
    
    <h3>Detalhes das Últimas Medições</h3>
    <p class="text-muted">Os valores abaixo correspondem aos **<?= count($registros) ?>** registros utilizados para o cálculo dos totais.</p>

    <?php if (!empty($registros)): ?>
        <div class="table-responsive bg-white p-3 rounded shadow-sm">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark sticky-top">
                    <tr>
                        <th>Data</th>
                        <th>Irradiação Solar (kWh/m²)</th>
                        <th>Consumo (kWh)</th>
                        <th>Energia Gerada (kWh)</th>
                        <th>Saldo Energético (kWh)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $reg): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($reg['data_formatada']) ?></td>
                        <td><?= formatarIrradiacao($reg['Irrad_solar']) ?></td>
                        <td><?= formatarKWh($reg['Consumo']) ?></td>
                        <td><?= formatarKWh($reg['Energia_Gerada']) ?></td>
                        <td class="fw-bold"><?= formatarKWh($reg['SALDO_ENERGETICO']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle me-2"></i>Nenhum registro encontrado para este usuário.
        </div>
    <?php endif; ?>

</div>

<footer class="bg-dark text-white py-4 mt-5">
    <div class="container text-center">
        <p>&copy; 2025 EcoFuture. Relatório.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>