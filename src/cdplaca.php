<?php
session_start();
require_once 'config.php';

// ID do usuário logado
$usuario_id = $_SESSION['usuario_id'] ?? null;

// Verifica se está logado
if (!$usuario_id) {
    $_SESSION['erro'] = "É necessário estar logado para cadastrar uma placa.";
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recebe os dados do formulário
    $modelo = $_POST['modelo'] ?? '';
    $potencia_total = $_POST['potencia_total'] ?? '';
    $quantidade = $_POST['quantidade'] ?? '';
    $data_instalacao = $_POST['data_instalacao'] ?? '';
    $status = $_POST['status'] ?? '';

    // Validação
    if (
        empty($modelo) || empty($potencia_total) || empty($quantidade) ||
        empty($data_instalacao) || empty($status)
    ) {
        $_SESSION['erro'] = "Preencha todos os campos obrigatórios.";
        header("Location: cadastroplaca.php");
        exit;
    }

    try {
        // Inicia transação
        $pdo->beginTransaction();

        // 1️⃣ Cadastra a placa solar
        $stmt = $pdo->prepare("
            INSERT INTO placasolar (Modelo, Quantidade, potenciaNominal, fk_USUARIO_SISTEMA_ID)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$modelo, $quantidade, $potencia_total, $usuario_id]);

        // 2️⃣ Atualiza dados do usuário
        $stmt = $pdo->prepare("
            UPDATE usuario_sistema 
            SET STATUS = ?, DATA_INSTALACAO = ?
            WHERE ID = ?
        ");
        $stmt->execute([$status, $data_instalacao, $usuario_id]);

        // Finaliza
        $pdo->commit();
        $_SESSION['sucesso'] = "Placa cadastrada com sucesso!";
        header("Location: monitora.html");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['erro'] = "Erro ao cadastrar: " . $e->getMessage();
        header("Location: cadastroplaca.php");
        exit;
    }
}
?>
