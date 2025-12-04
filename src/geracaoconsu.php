<?php
// geracao.php (Atualizado com Bloqueio de Execução Diária e FORÇAR UPDATE)

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "config.php"; // Assume conexão PDO é configurada aqui

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Erro: configuração do banco (config.php) não carregou \$pdo (PDO).");
}

if (!isset($_SESSION["ID"]) || empty($_SESSION["ID"])) {
    die("Usuário não autenticado.");
}

$userId = (int) $_SESSION["ID"];
$dataHoje = date("Y-m-d");

// Variáveis para os cálculos diários (inicializadas como nulas)
$energiaGerada = null;
$consumoDia = null;
$saldoNovo = null;
$salvoMsgIndices = '';
$salvoMsgMedicoes = '';
$diagnostico = [];

// Checa se a ação de forçar atualização foi solicitada
$forceUpdate = isset($_GET['action']) && $_GET['action'] === 'update_daily';

// --- Funções utilitárias (mantidas) ---

function buscarPR(PDO $pdo) {
    try {
        $stmt = $pdo->query("SELECT PERFORMANCE_RATIO FROM indices ORDER BY ID DESC LIMIT 1");
        $val = $stmt->fetchColumn();
        if ($val === false || $val === null) return null;
        return floatval($val);
    } catch (Exception $e) { return null; }
}

function buscarPotenciaTotal(PDO $pdo, int $userId) {
    try {
        $stmt = $pdo->prepare("SELECT POTENCIA_TOTAL FROM usuario_sistema WHERE ID = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $val = $stmt->fetchColumn();
        if ($val === false || $val === null) return null;
        return floatval($val);
    } catch (Exception $e) { return null; }
}

function buscarUltimaIrrad(PDO $pdo, int $userId) {
    try {
        $stmt = $pdo->prepare("SELECT Irrad_solar, DATA_MEDICAO FROM medicoes WHERE FK_USUARIO_SISTEMA_ID = :id AND Irrad_solar IS NOT NULL ORDER BY DATA_MEDICAO DESC, ID DESC LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        return ['irrad' => floatval($row['Irrad_solar']), 'data' => $row['DATA_MEDICAO']];
    } catch (Exception $e) { return null; }
}

function buscarDadosHistoricos(PDO $pdo, int $userId) {
    try {
        // Busca os dados de Geração e Consumo dos últimos 7 dias da tabela medicoes
        $sqlMed = $pdo->prepare("
            SELECT DATE_FORMAT(DATA_MEDICAO, '%d/%m') as data, ENERGIA_GERADA, Consumo
            FROM medicoes
            WHERE FK_USUARIO_SISTEMA_ID = :uid
            ORDER BY DATA_MEDICAO DESC
            LIMIT 7
        ");
        $sqlMed->execute([':uid' => $userId]);
        $medicoes = $sqlMed->fetchAll(PDO::FETCH_ASSOC);

        // Busca o Saldo dos últimos 7 registros da tabela indices
        $sqlInd = $pdo->prepare("
            SELECT DATE_FORMAT(DATA_INICIO, '%d/%m') as data, SALDO_ENERGETICO
            FROM indices
            WHERE FK_USUARIO_SISTEMA_ID = :uid AND SALDO_ENERGETICO IS NOT NULL
            ORDER BY DATA_INICIO DESC
            LIMIT 7
        ");
        $sqlInd->execute([':uid' => $userId]);
        $indices = $sqlInd->fetchAll(PDO::FETCH_ASSOC);
        
        // Inverte a ordem das listas para exibir do mais antigo ao mais novo no gráfico
        $medicoes = array_reverse($medicoes);
        $indices = array_reverse($indices);

        // Combina e formata os dados
        $historico = [];
        $saldoMap = [];

        // 1. Mapear saldos (para preenchimento)
        foreach ($indices as $row) {
            $saldoMap[$row['data']] = floatval($row['SALDO_ENERGETICO']);
        }

        // 2. Combinar medições com saldo
        foreach ($medicoes as $row) {
             $data = $row['data'];
             $historico[] = [
                 'data' => $data,
                 'geracao' => floatval($row['ENERGIA_GERADA']),
                 'consumo' => floatval($row['Consumo']),
                 // Tenta pegar o saldo do mesmo dia, se não houver, usa 0
                 'saldo' => $saldoMap[$data] ?? 0.0 
             ];
        }

        return $historico;

    } catch (Exception $e) {
        error_log("Erro ao buscar histórico: " . $e->getMessage());
        return [];
    }
}


// --------------------------------------------------------------------------------------------------
// !!! BLOQUEIO DE EXECUÇÃO DIÁRIA (Adaptado para Teste) !!!
// Verifica se o registro de saldo (que marca o fim do processo diário) já existe para hoje.
// Se $forceUpdate for true, a execução é permitida para atualização de teste.
// --------------------------------------------------------------------------------------------------
$stmtCheckIndices = $pdo->prepare("SELECT ID FROM indices WHERE FK_USUARIO_SISTEMA_ID = :uid AND DATA_INICIO = CURDATE()");
$stmtCheckIndices->execute([':uid' => $userId]);
$existsIdIndices = $stmtCheckIndices->fetchColumn();


if (!$existsIdIndices || $forceUpdate) {
    // --- O PROCESSO DIÁRIO SERÁ EXECUTADO AQUI SE: 
    // 1) NÃO HOUVER REGISTRO DE ÍNDICE PARA HOJE (FLUXO NORMAL)
    // 2) OU SE O PARAMETRO 'update_daily' ESTIVER PRESENTE (FLUXO DE TESTE) ---

    // Adiciona uma mensagem de diagnóstico se a atualização foi forçada
    if ($forceUpdate) {
        $diagnostico[] = "⚠️ Recálculo Forçado! Registros existentes serão atualizados.";
    }

    // --- Buscar dados necessários para o cálculo ---
    $pr = buscarPR($pdo); 
    $potTotal = buscarPotenciaTotal($pdo, $userId);
    $ultimaIrrad = buscarUltimaIrrad($pdo, $userId);

    if ($pr === null) $diagnostico[] = "PR não encontrado.";
    if ($potTotal === null) $diagnostico[] = "Potência Total não encontrada.";
    if ($ultimaIrrad === null) $diagnostico[] = "Nenhuma irradiação anterior. (Necessário para cálculo)";

    // --- 1. Cálculo da Geração do Dia (Energia Gerada) ---
    if ($pr !== null && $potTotal !== null && $ultimaIrrad !== null) {
        $irrad = $ultimaIrrad['irrad']; 
        $pot_kw = $potTotal / 1000.0;
        // Fórmula de Geração: Irradiação * Potência_kW * PR
        $energiaGerada = $irrad * ($pot_kw /5) * $pr; // Manter a fórmula original
        $energiaGerada = round($energiaGerada, 2);
    } else {
        $salvoMsgIndices = "Cálculo de geração incompleto.";
    }

    // --- 2. Cálculo do Consumo e Saldo ---
    if ($energiaGerada !== null) {
        try {
            // 2.1. Buscar último Saldo (da tabela indices)
            $sqlUltimo = $pdo->prepare("
                SELECT SALDO_ENERGETICO 
                FROM indices 
                WHERE FK_USUARIO_SISTEMA_ID = :id 
                ORDER BY ID DESC LIMIT 1
            ");
            $sqlUltimo->execute([':id' => $userId]);
            $saldoAnterior = $sqlUltimo->fetchColumn();
            // Se for um update forçado, deve buscar o saldo anterior a hoje para calcular corretamente
            // Mas, para simplificar o teste, ele busca o último saldo e aplica a diferença do dia.
            $saldoAnterior = $saldoAnterior ? floatval($saldoAnterior) : 0;

            // 2.2. Gerar Consumo do Dia (Simulação) - Novo valor a cada recálculo
            $consumoDia = round(rand(900, 3000) / 100, 2); // Simula em kWh

            // 2.3. Calcular Novo Saldo
            // IMPORTANTE: Este cálculo assume que $saldoAnterior é o saldo de ONTEM.
            // Para ser preciso em um UPDATE, teríamos que buscar o saldo de ontem.
            // Para simplificar o teste, mantemos o fluxo: Último Saldo + Geração - Consumo
            // OBS: Se o saldo for de hoje (recalculado), o valor será instável.
            
            // Lógica para Saldo: Se for UPDATE de hoje, subtraímos a geração/consumo anterior do dia
            // e somamos a nova. Simplificando para teste: Apenas recálculo no último saldo.
            // Buscar o saldo de ontem para maior precisão
            $stmtOntem = $pdo->prepare("
                SELECT SALDO_ENERGETICO 
                FROM indices 
                WHERE FK_USUARIO_SISTEMA_ID = :id AND DATE(DATA_INICIO) < CURDATE()
                ORDER BY DATA_INICIO DESC, ID DESC LIMIT 1
            ");
            $stmtOntem->execute([':id' => $userId]);
            $saldoOntem = $stmtOntem->fetchColumn();
            $saldoBase = $saldoOntem ? floatval($saldoOntem) : 0;
            
            // Saldo NOVO (calculado a partir do saldo base de ontem)
            $saldoNovo = $saldoBase + $energiaGerada - $consumoDia;
            $saldoNovo = round($saldoNovo, 2);

        } catch (Exception $e) {
            $salvoMsgIndices = "❌ Erro ao calcular Saldo/Consumo: " . $e->getMessage();
            $consumoDia = null;
            $saldoNovo = null;
        }
    }


    // --- 3. Salvar/Atualizar Consumo na tabela MEDICOES ---
    try {
        if ($energiaGerada !== null && $consumoDia !== null && $ultimaIrrad !== null) {

            // Verifica se já existe registro em medicoes hoje
            $stmtCheck = $pdo->prepare("
                SELECT ID 
                FROM medicoes 
                WHERE FK_USUARIO_SISTEMA_ID = :uid 
                  AND DATE(DATA_MEDICAO) = CURDATE() 
                LIMIT 1
            ");
            $stmtCheck->execute([':uid' => $userId]);
            $existsIdMedicoes = $stmtCheck->fetchColumn();

            if ($existsIdMedicoes) {
                // UPDATE
                $stmtUp = $pdo->prepare("
                    UPDATE medicoes 
                    SET ENERGIA_GERADA = :energia, 
                     Irrad_solar = :irrad, 
                         CONSUMO = :consumo
                    WHERE ID = :id
                ");
                $stmtUp->execute([
                    ':energia' => $energiaGerada,
                     ':irrad'   => $ultimaIrrad['irrad'],
                    ':consumo' => $consumoDia,
                    ':id'      => $existsIdMedicoes
                ]);
                $salvoMsgMedicoes = "✅ Atualizado Geração/Consumo/Irrad em 'medicoes'.";
            } else {
                // INSERT
                $stmtIn = $pdo->prepare("
                    INSERT INTO medicoes 
                        (DATA_MEDICAO, ENERGIA_GERADA, Irrad_solar, CONSUMO, FK_USUARIO_SISTEMA_ID) 
                    VALUES 
                        (NOW(), :energia, :irrad, :consumo, :uid)
                ");
                $stmtIn->execute([
                    ':energia' => $energiaGerada,
                    ':irrad'   => $ultimaIrrad['irrad'],
                    ':consumo' => $consumoDia,
                    ':uid'     => $userId
                ]);
               $salvoMsgMedicoes = "✅ Inserido novo registro em 'medicoes'.";
            }

        } else {
            $salvoMsgMedicoes = "Dados insuficientes para salvar em 'medicoes'.";
        }
    } catch (Exception $e) {
        $salvoMsgMedicoes = "❌ Erro ao salvar em medicoes: " . $e->getMessage();
    }


    // --- 4. Salvar/Atualizar Saldo na tabela INDICES ---
    if ($energiaGerada !== null && $consumoDia !== null && $saldoNovo !== null) {
        try {
            $prForInsert = $pr ?? 0;
            
            if ($existsIdIndices) {
                 // UPDATE (Se a linha já existe, a atualizamos)
                 $sqlIndices = $pdo->prepare("
                    UPDATE indices 
                    SET CONSUMO = ?, PERFORMANCE_RATIO = ?, PRODUTIVIDADE = ?, SALDO_ENERGETICO = ? 
                    WHERE ID = ?
                ");
                $sqlIndices->execute([$consumoDia, $prForInsert, $energiaGerada, $saldoNovo, $existsIdIndices]);
                $salvoMsgIndices = "✅ Saldo diário ATUALIZADO em 'indices' com sucesso.";
            } else {
                // INSERT (Se a linha não existe)
                $sqlInsert = $pdo->prepare("
                    INSERT INTO indices 
                        (CONSUMO, DATA_INICIO, PERFORMANCE_RATIO, ECONOMIA, PRODUTIVIDADE, SALDO_ENERGETICO, FK_USUARIO_SISTEMA_ID)
                    VALUES 
                        (?, CURDATE(), ?, 0, ?, ?, ?)
                ");
                $sqlInsert->execute([$consumoDia, $prForInsert, $energiaGerada, $saldoNovo, $userId]);
                $salvoMsgIndices = "✅ Saldo diário INSERIDO em 'indices' com sucesso.";
            }

        } catch (Exception $e) {
            $salvoMsgIndices = "❌ Erro ao salvar Saldo em 'indices': " . $e->getMessage();
        }
    }

} else {
    // --- PROCESSO JÁ EXECUTADO HOJE (Bloqueado) ---
    $salvoMsgIndices = "Cálculo de saldo diário já foi realizado hoje (Bloqueado).";
    $salvoMsgMedicoes = "Geração/Consumo de hoje já foram registrados (Bloqueado).";
}


// --- 5. Buscar Dados para Exibição (Busca a informação mais recente no DB) ---
// ... (Lógica de exibição mantida, pois está correta) ...

$energiaGeradaParaExibir = $energiaGerada; 
$consumoParaExibir = $consumoDia;
$saldoParaExibir = $saldoNovo; 
$pr = buscarPR($pdo); 
$potTotal = buscarPotenciaTotal($pdo, $userId);
$ultimaIrrad = buscarUltimaIrrad($pdo, $userId);


// Fallback para valores se o cálculo foi pulado ou falhou
if ($consumoParaExibir === null || $energiaGeradaParaExibir === null) {
      try {
           // Busca o último registro de medição (consumo/geracao)
           $sqlDisplayMed = $pdo->prepare("
               SELECT CONSUMO, ENERGIA_GERADA
               FROM medicoes
               WHERE FK_USUARIO_SISTEMA_ID = :uid
               ORDER BY DATA_MEDICAO DESC, ID DESC 
               LIMIT 1
           ");
           $sqlDisplayMed->execute([':uid' => $userId]);
           $dadosExibicaoMed = $sqlDisplayMed->fetch(PDO::FETCH_ASSOC);

           if ($dadosExibicaoMed) {
               $consumoParaExibir = $consumoParaExibir ?? floatval($dadosExibicaoMed['CONSUMO']);
               $energiaGeradaParaExibir = $energiaGeradaParaExibir ?? floatval($dadosExibicaoMed['ENERGIA_GERADA']);
           }
      } catch (Exception $e) { /* silent fail */ }
}

if ($saldoParaExibir === null) {
    try {
        // Busca o último saldo acumulado
        $sqlDisplayInd = $pdo->prepare("
            SELECT SALDO_ENERGETICO
            FROM indices
            WHERE FK_USUARIO_SISTEMA_ID = :uid
            ORDER BY ID DESC 
            LIMIT 1
        ");
        $sqlDisplayInd->execute([':uid' => $userId]);
        $saldoDB = $sqlDisplayInd->fetchColumn();
        
        if ($saldoDB !== false) {
            $saldoParaExibir = floatval($saldoDB);
        } else {
            $saldoParaExibir = null;
        }
    } catch (Exception $e) { 
        $saldoParaExibir = null; 
    }
}

// --- 6. Preparar strings para exibição ---
$energiaExibir = ($energiaGeradaParaExibir === null || $energiaGeradaParaExibir < 0) ? "—" : number_format($energiaGeradaParaExibir, 2, ',', '.');
$consumoExibir = ($consumoParaExibir === null || $consumoParaExibir < 0) ? "—" : number_format($consumoParaExibir, 2, ',', '.');
$saldoExibir = ($saldoParaExibir === null) ? "—" : number_format($saldoParaExibir, 2, ',', '.');
$prExibir = ($pr !== null) ? number_format($pr * 100, 2, ',', '.') . "%" : "—";
$potExibir = ($potTotal === null) ? "—" : ( ($potTotal >= 1000) ? number_format($potTotal/1000,2,',','.') . " kW" : number_format($potTotal,2,',','.') . " W" );
$irradExibir = ($ultimaIrrad === null) ? "—" : number_format($ultimaIrrad['irrad'], 2, ',', '.') . " kWh/m²";

// --- 7. Buscar e codificar dados históricos para JS ---
$dadosHistoricos = buscarDadosHistoricos($pdo, $userId);
$dadosHistoricosJson = json_encode($dadosHistoricos);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>EcoFuture - Geração e Consumo Instantâneo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js CDN para os gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="styles/styles.css">
    <link rel="stylesheet" href="styles/header.css">
    <style>
        body { background-color: #f8f9fa; }
        .stats-card { 
            background: #fff; border-radius: 12px; padding: 18px; 
            box-shadow: 0 6px 18px rgba(0,0,0,0.06); transition: transform 0.3s; 
            height: 100%; /* Garante altura uniforme */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .stats-card:hover { transform: translateY(-5px); }
        .stats-icon { font-size: 36px; }
        .stats-value { font-size: 28px; font-weight: 700; margin-top: 8px; }
        .stats-label { color: #6c757d; margin-top: 6px; font-weight:600; }
        .status-indicator { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:8px; vertical-align:middle; }
        .status-positive { background:#2ecc71; }
        .status-negative { background:#e74c3c; }
        .chart-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); height: 100%; }
    </style>
</head>
<body>
<header class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.html"><i class="fas fa-solar-panel me-2"></i>EcoFuture</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto"><li class="nav-item"><a class="nav-link" href="monitora.php">Voltar</a></li></ul>
        </div>
    </div>
</header>

<br><br>

<section id="servicos" class="pt-4">
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-12 text-center mb-4">
                <h2 class="section-title">Geração e Consumo Diário</h2>
                <p class="section-subtitle">Resumo do dia e histórico da última semana</p>
            </div>
        </div>

        <!-- Linha 1: Cards de Status -->
        <div class="row mb-5">
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stats-card">
                    <div>
                        <i class="fas fa-bolt stats-icon geracao" style="color:#f6b93b;"></i>
                        <div class="stats-value geracao" id="geracaoAtual"><?= $energiaExibir ?> kWh</div>
                        <div class="stats-label"><span class="status-indicator status-positive"></span>Geração do Dia</div>
                        <div class="small text-muted mt-2">
                            Irrad.: <?= $irradExibir ?> &nbsp;•&nbsp; Potência: <?= $potExibir ?> &nbsp;•&nbsp; PR: <?= $prExibir ?>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="small text-success mb-1"><?= htmlspecialchars($salvoMsgMedicoes) ?></div>
                        <?php if (!empty($diagnostico)): ?>
                            <div class="small text-danger mb-2"><?= implode(' ', $diagnostico) ?></div>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="forceDailyUpdate()">
                            <i class="fas fa-sync-alt me-1"></i> Forçar Recálculo/Update
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stats-card">
                    <div>
                        <i class="fas fa-plug stats-icon consumo" style="color:#c94c4c;"></i>
                        <div class="stats-value consumo" id="consumoAtual"><?= $consumoExibir ?> kWh</div>
                        <div class="stats-label"><span class="status-indicator status-negative"></span>Consumo do Dia</div>
                    </div>
                    <div class="mt-3">
                         <div class="small text-success mb-1"><?= htmlspecialchars($salvoMsgIndices) ?></div>
                         <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="forceDailyUpdate()">
                            <i class="fas fa-sync-alt me-1"></i> Forçar Recálculo/Update
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stats-card">
                    <div>
                        <i class="fas fa-chart-line stats-icon saldo" style="color:#4a7c59;"></i>
                        <div class="stats-value saldo" id="saldoAtual"><?= $saldoExibir ?> kWh</div>
                        <div class="stats-label">Saldo Energético (Acumulado)</div>
                        <div class="small text-muted mt-2">Última atualização: <?= $dataHoje ?></div>
                    </div>
                    <!-- Não adiciona botão aqui, conforme solicitado -->
                    <div class="mt-3">
                        <div class="small text-muted">Este valor é o saldo final acumulado após o último recálculo.</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Linha 2: Gráficos de Histórico -->
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="chart-card">
                    <h5 class="mb-3 text-center text-muted">Geração vs. Consumo (Últimos 7 dias)</h5>
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="chart-card">
                    <h5 class="mb-3 text-center text-muted">Evolução do Saldo (Últimos 7 dias)</h5>
                    <canvas id="saldoChart"></canvas>
                </div>
            </div>
        </div>

    </div>
</section>

<footer class="bg-dark text-white py-4 mt-4">
    <div class="container">
        <div class="row">
            <div class="col-lg-4"><h5><i class="fas fa-solar-panel me-2"></i>EcoFuture</h5><p class="mb-0">Energia limpa para um futuro sustentável</p></div>
            <div class="col-lg-4 text-center"><p class="mb-1">Contato: (19) 1234-5678</p><p class="mb-1">EcoFuture@gmail.com</p></div>
            <div class="col-lg-4 text-lg-end"><p class="mb-0">&copy; 2025 EcoFuture. Todos os direitos reservados.</p></div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Função acionada pelos botões para forçar o recálculo e o salvamento.
function forceDailyUpdate() {
    if (confirm('Tem certeza de que deseja forçar o recálculo e atualizar os dados do dia? Isso simulará novos valores de Consumo e Saldo.')) {
        // Redireciona para a mesma página com o parâmetro de ação.
        // O PHP detectará este parâmetro e ignorará o bloqueio.
        window.location.href = window.location.pathname + '?action=update_daily';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const dadosHistoricos = <?= $dadosHistoricosJson ?>;
    
    if (dadosHistoricos.length === 0) {
        console.warn("Não há dados históricos disponíveis para gerar os gráficos.");
        const chartRow = document.querySelector('.row.mb-5 + .row');
        if (chartRow) {
            chartRow.style.display = 'none';
        }
        return;
    }

    // Preparação dos dados
    const labels = dadosHistoricos.map(d => d.data); // Datas: dd/mm
    const geracaoData = dadosHistoricos.map(d => d.geracao);
    const consumoData = dadosHistoricos.map(d => d.consumo);
    const saldoData = dadosHistoricos.map(d => d.saldo);

    // --- GRÁFICO 1: Geração vs. Consumo Diário (Bar Chart) ---
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Geração (kWh)',
                    data: geracaoData,
                    backgroundColor: 'rgba(246, 185, 59, 0.8)', // Amarelo/Dourado
                    borderColor: 'rgb(246, 185, 59)',
                    borderWidth: 1,
                    borderRadius: 5
                },
                {
                    label: 'Consumo (kWh)',
                    data: consumoData,
                    backgroundColor: 'rgba(201, 76, 76, 0.8)', // Vermelho/Laranja
                    borderColor: 'rgb(201, 76, 76)',
                    borderWidth: 1,
                    borderRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Energia (kWh)' }
                }
            },
            plugins: {
                legend: { position: 'top' },
                title: { display: false }
            }
        }
    });

    // --- GRÁFICO 2: Saldo Energético Acumulado (Line Chart) ---
    const saldoCtx = document.getElementById('saldoChart').getContext('2d');
    new Chart(saldoCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Saldo Acumulado (kWh)',
                    data: saldoData,
                    borderColor: 'rgb(74, 124, 89)', // Verde Escuro
                    backgroundColor: 'rgba(74, 124, 89, 0.2)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false, 
                    title: { display: true, text: 'Saldo (kWh)' }
                }
            },
            plugins: {
                legend: { display: false },
                title: { display: false }
            }
        }
    });

    // Ajusta a altura dos gráficos para serem responsivos e manterem uma proporção decente
    const chartCards = document.querySelectorAll('.chart-card');
    chartCards.forEach(card => {
        card.style.height = '400px'; 
    });
});
</script>

</body>
</html>