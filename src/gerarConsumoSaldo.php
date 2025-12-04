<?php
// Arquivo: simulador_processo_diario.php
// Simula o processo noturno/diário de cálculo de Geração, Consumo e Saldo
// NOTA: Os valores de GERAÇÃO e CONSUMO foram SIMULADOS em kWh.

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "config.php"; // Assume conexão PDO é configurada aqui

// Verifica login
if (!isset($_SESSION["ID"])) {
    die("Usuário não logado.");
}

$userId = $_SESSION["ID"];

// ----------------------------------------------------
// !!! CORREÇÃO CRÍTICA !!!
// 1) VERIFICA SE O PROCESSO DIÁRIO JÁ FOI EXECUTADO HOJE.
// O script só deve rodar se AINDA NÃO houver um registro de índice para a data atual.
// ----------------------------------------------------
$stmtCheckInd = $pdo->prepare("SELECT ID FROM indices WHERE FK_USUARIO_SISTEMA_ID = ? AND DATA_INICIO = CURDATE() LIMIT 1");
$stmtCheckInd->execute([$userId]);
$existsIdInd = $stmtCheckInd->fetchColumn();

if ($existsIdInd) {
    // Se o registro para HOJE já existe, a simulação diária já foi concluída.
    // Busca o saldo atual para exibir uma mensagem informativa.
    $sqlUltimo = $pdo->prepare("SELECT SALDO_ENERGETICO FROM indices WHERE FK_USUARIO_SISTEMA_ID = ? ORDER BY ID DESC LIMIT 1");
    $sqlUltimo->execute([$userId]);
    $ultimo = $sqlUltimo->fetch(PDO::FETCH_ASSOC);
    $saldoAtual = $ultimo ? floatval($ultimo["SALDO_ENERGETICO"]) : 0.0;
    
    echo "Simulação diária para hoje (CURDATE()) já foi executada. O saldo atual é de {$saldoAtual} kWh. Nenhuma alteração foi realizada.";
    exit; // Impede a execução do restante da lógica
}


// -----------------------------
// 2) BUSCAR ÚLTIMO SALDO ACUMULADO (indices)
// -----------------------------
$sqlUltimo = $pdo->prepare("
    SELECT SALDO_ENERGETICO
    FROM indices
    WHERE FK_USUARIO_SISTEMA_ID = ?
    ORDER BY ID DESC
    LIMIT 1
");
$sqlUltimo->execute([$userId]);
$ultimo = $sqlUltimo->fetch(PDO::FETCH_ASSOC);

$saldoAnterior = $ultimo ? floatval($ultimo["SALDO_ENERGETICO"]) : 0.0;

// -----------------------------
// 3) SIMULAR GERAÇÃO DO DIA (em kWh)
// -----------------------------
$geracaoDoDia = round(rand(150, 400) / 100, 2); // Simula Geração entre 1.50 e 4.00 kWh

// -----------------------------
// 4) SIMULAR CONSUMO DO DIA (em kWh)
// -----------------------------
$consumoDia = round(rand(800, 2500) / 100, 2); // Simula Consumo entre 0.80 kWh e 2.50 kWh

// -----------------------------
// 5) CALCULAR NOVO SALDO ACUMULADO
// O SALDO ENERGÉTICO é ACUMULADO (saldo anterior + geração - consumo).
// -----------------------------
$saldoNovo = $saldoAnterior + $geracaoDoDia - $consumoDia;
$saldoNovo = round($saldoNovo, 2);


// -----------------------------
// 6) INSERIR REGISTRO NA TABELA medicoes (onde fica o consumo diário)
// -----------------------------

// Não precisamos mais de UPDATE aqui, pois o check de "já existe registro" para a simulação diária 
// está no início do script. Apenas INSERIMOS ou fazemos UPDATE se o objetivo for re-executar (o que não queremos).

// Para fins de atomicidade e de garantir que o registro de 'hoje' exista:
$stmtCheckMed = $pdo->prepare("SELECT ID FROM medicoes WHERE FK_USUARIO_SISTEMA_ID = ? AND DATE(DATA_MEDICAO) = CURDATE() LIMIT 1");
$stmtCheckMed->execute([$userId]);
$existsIdMed = $stmtCheckMed->fetchColumn();

if ($existsIdMed) {
    // Se o registro de medição para hoje existir, atualiza (isto permite que, se a medição 
    // real for enviada mais tarde, o script não falhe)
    $sqlInsertMedicao = $pdo->prepare("
        UPDATE medicoes
        SET ENERGIA_GERADA = ?, CONSUMO = ?, DATA_MEDICAO = NOW()
        WHERE ID = ?
    ");
    $okMedicao = $sqlInsertMedicao->execute([
        $geracaoDoDia,  // ENERGIA_GERADA
        $consumoDia,    // CONSUMO
        $existsIdMed    // ID do registro existente
    ]);
} else {
    // INSERT - Novo registro de medição para o dia
    $sqlInsertMedicao = $pdo->prepare("
        INSERT INTO medicoes
            (DATA_INICIO, ENERGIA_GERADA, DATA_MEDICAO, CONSUMO, Irrad_solar, FK_USUARIO_SISTEMA_ID)
        VALUES
            (CURDATE(), ?, NOW(), ?, NULL, ?)
    ");
    $okMedicao = $sqlInsertMedicao->execute([
        $geracaoDoDia,  // ENERGIA_GERADA
        $consumoDia,    // CONSUMO
        $userId         // FK_USUARIO_SISTEMA_ID
    ]);
}


// -----------------------------
// 7) INSERIR registro de saldo em indices
// Como verificamos no início que não existe um registro de índice para hoje,
// faremos um INSERT.
// -----------------------------

// O ID $existsIdInd está vazio, então sempre será um INSERT aqui, pois a verificação inicial
// garantiu que o processo diário de saldo ainda não foi rodado.
$sqlInsertIndice = $pdo->prepare("
    INSERT INTO indices
        (CONSUMO, DATA_INICIO, PERFORMANCE_RATIO, ECONOMIA, PRODUTIVIDADE, SALDO_ENERGETICO, FK_USUARIO_SISTEMA_ID)
    VALUES
        (?, CURDATE(), 0, 0, ?, ?, ?)
");

$okIndice = $sqlInsertIndice->execute([
    $consumoDia,        // CONSUMO
    $geracaoDoDia,      // PRODUTIVIDADE (geração do dia)
    $saldoNovo,         // SALDO_ENERGETICO (ACUMULADO)
    $userId             // FK_USUARIO_SISTEMA_ID
]);


// -----------------------------
// 8) Mensagem de Resultado
// -----------------------------
if ($okMedicao && $okIndice) {
    echo "Simulação diária CONCLUÍDA com sucesso! Geração: {$geracaoDoDia} kWh, Consumo: {$consumoDia} kWh, Novo Saldo Acumulado: {$saldoNovo} kWh.";
} elseif (!$okMedicao) {
    echo "Erro (CRÍTICO) ao salvar Geração/Consumo em medicoes.";
} elseif (!$okIndice) {
    echo "Erro (CRÍTICO) ao salvar Saldo Acumulado em indices.";
} else {
    echo "Erro desconhecido ao salvar consumo e saldo.";
}
?>