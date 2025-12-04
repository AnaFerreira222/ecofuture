<?php
session_start();
require_once "config.php"; 

if (!isset($_SESSION["ID"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["ID"];
$limiteSegundos = 86400; // 24 horas
$redirect_page = "desempenho.php"; 

try {
    // Lógica de Cooldown: Verifica se já mediu nas últimas 24h
    $stmt = $pdo->prepare("
        SELECT UNIX_TIMESTAMP(DATA_MEDICAO) AS ultima_medicao_ts
        FROM medicoes
        WHERE FK_USUARIO_SISTEMA_ID = :uid AND Irrad_solar IS NOT NULL
        ORDER BY ID DESC
        LIMIT 1
    ");
    $stmt->execute([':uid' => $userId]);
    $ultimaMedicaoRow = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $bloqueado = false;
    
    if ($ultimaMedicaoRow) {
        $ultima = (int) $ultimaMedicaoRow['ultima_medicao_ts'];
        $agora = time();
        $diferenca = $agora - $ultima;

        if ($diferenca < $limiteSegundos) {
            $bloqueado = true;
            $_SESSION['error_message'] = "A Irradiação Solar possui um bloqueio de 24 horas. Aguarde o tempo restante.";
            header("Location: " . $redirect_page);
            exit;
        }
    }

    // Geração e Inserção da Nova Irradiação (Apenas se NÃO BLOQUEADO)
    if (!$bloqueado) {
        $novaIrradiacao = mt_rand(40, 70) / 10.0; // 4.0 a 7.0 kWh/m²

        $stmtInsert = $pdo->prepare("
            INSERT INTO medicoes (
                FK_USUARIO_SISTEMA_ID, 
                DATA_MEDICAO, 
                Irrad_solar
            ) VALUES (
                :uid, 
                NOW(), 
                :irrad
            )
        ");
        
        $stmtInsert->execute([
            ':uid' => $userId,
            ':irrad' => $novaIrradiacao
        ]);

        $_SESSION['success_message'] = "Irradiação Solar de " . number_format($novaIrradiacao, 2, ',', '.') . " kWh/m² registrada com sucesso!";
        header("Location: " . $redirect_page);
        exit;
    }

} catch (PDOException $e) {
    error_log("Erro ao gerar irradiação: " . $e->getMessage());
    $_SESSION['error_message'] = "Erro interno ao processar a medição de Irradiação.";
    header("Location: " . $redirect_page);
    exit;
}
?>





//?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "config.php"; // Conexão PDO

// Usuário precisa estar logado
if (!isset($_SESSION["ID"])) {
    die("Usuário não logado.");
}

$userId = (int) $_SESSION["ID"];

// Irradiação diária realista (1.0 a 5.5 kWh/m²)
// Geramos o valor que será salvo
$irradiacao = rand(20, 55) / 10;

// Data atual
$dataHoje = date("Y-m-d H:i:s"); // Alterei para H:i:s para ter um timestamp mais preciso para o bloqueio de 24h

try {
    // Inserir medição no banco
    // Observação: Sua tabela 'medicoes' tem 'DATA_MEDICAO' como DATE, o que pode
    // causar problemas se você tentar gerar irradiação mais de uma vez por dia
    // (a coluna DATA_MEDICAO deveria ser DATETIME para rastrear o tempo exato).
    // Estou usando H:i:s no PHP, mas se o campo for DATE, o tempo será ignorado.
    // Para 24h exatas, o campo deve ser DATETIME.
    
    $sql = "INSERT INTO medicoes (DATA_MEDICAO, Irrad_solar, FK_USUARIO_SISTEMA_ID)
             VALUES (:data, :irradiacao, :usuario)";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":data", $dataHoje);
    $stmt->bindParam(":irradiacao", $irradiacao);
    $stmt->bindParam(":usuario", $userId);
    $stmt->execute();

    // Redireciona de volta
    header("Location: desempenho.php");
    exit;

} catch (PDOException $e) {
    echo "Erro ao salvar irradiação: " . $e->getMessage();
}
?>