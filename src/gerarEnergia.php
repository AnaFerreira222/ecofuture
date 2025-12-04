<?php
session_start();
require_once "config.php"; // conexão PDO

if (!isset($_SESSION["ID"])) {
    die("Usuário não logado.");
}

$userId = $_SESSION["ID"];

/* ============================
    1. BUSCA A ÚLTIMA IRRADIAÇÃO
   ============================ */
$stmt = $pdo->prepare("SELECT Irrad_solar 
                       FROM medicoes 
                       WHERE FK_USUARIO_SISTEMA_ID = ? 
                       ORDER BY ID DESC 
                       LIMIT 1");
$stmt->execute([$userId]);
$irradiacao = $stmt->fetchColumn();

if (!$irradiacao) {
    die("Nenhum valor de irradiação encontrado para calcular energia.");
}

/* ============================
    2. BUSCA A POTÊNCIA TOTAL DO USUÁRIO
   ============================ */
$stmt = $pdo->prepare("SELECT POTENCIA_TOTAL 
                       FROM usuario_sistema 
                       WHERE ID = ?");
$stmt->execute([$userId]);
$potencia_total = $stmt->fetchColumn();

if (!$potencia_total) {
    die("Potência total não configurada para este usuário.");
}

/* ============================
    3. BUSCA O PR NA TABELA INDICES
   ============================ */
$stmt = $pdo->prepare("SELECT PERFORMANCE_RATIO 
                       FROM indices 
                       LIMIT 1");
$stmt->execute();
$pr = $stmt->fetchColumn();

if (!$pr) {
    die("PR não encontrado.");
}

/* ============================
    4. CALCULA A GERAÇÃO DIÁRIA
   ============================ */
$potenciaKW = $potencia_total / 1000; // transforma para kW
$energiaGerada = $irradiacao * $potenciaKW * $pr;

/* Arredonda para kWh */
$energiaGerada = round($energiaGerada, 2);

/* ============================
    5. SALVA NO BANCO (tabela medicoes)
   ============================ */
$stmt = $pdo->prepare("
    INSERT INTO medicoes (DATA_MEDICAO, ENERGIA_GERADA, FK_USUARIO_SISTEMA_ID)
    VALUES (NOW(), ?, ?)
");
$stmt->execute([$energiaGerada, $userId]);

/* ============================
    6. SALVA EM SESSÃO
   ============================ */
$_SESSION["energia_atual"] = $energiaGerada;

/* ============================
    7. REDIRECIONA DE VOLTA
   ============================ */
header("Location: geracao.php"); 
exit;
?>
