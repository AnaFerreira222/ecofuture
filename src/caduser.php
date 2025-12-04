<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- DADOS DO USUÁRIO ---
    $nome_user = $_POST["nome"] ?? "";
    $email = $_POST["email"] ?? "";
    $senha = $_POST["senha"] ?? "";
    $telefone = $_POST["telefone"] ?? "";
   
    // --- DADOS DO ENDEREÇO ---
    $cep = $_POST["CEP"] ?? "";
    $rua_nome = $_POST["rua"] ?? "";
    $numero = $_POST["num"] ?? "";
    $complemento = $_POST["complemento"] ?? "";
    $bairro_nome = $_POST["bairro"] ?? "";
    $cidade_nome = $_POST["cidade"] ?? "";
    $estado_sigla = $_POST["estado"] ?? "";

    // 🔒 Validação
    if (empty($nome_user) || empty($email) || empty($senha)) {
        $_SESSION['erro'] = "Preencha todos os campos obrigatórios.";
        header("Location: cadastro.php");
        exit;
    }

    // --- CADASTRA O USUÁRIO ---
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    try {
        // Inicia transação
        $pdo->beginTransaction();

        // Usuário
        $stmt = $pdo->prepare("
            INSERT INTO usuario_sistema 
            (TEL, EMAIL, TIPO, SENHA, _NOME, STATUS, POTENCIA_TOTAL, DATA_INSTALACAO)
            VALUES (?, ?, 'RESIDENCIAL', ?, ?, 'ATIVO', NULL, NOW())
        ");
        $stmt->execute([$telefone, $email, $senhaHash, $nome_user]);
        $usuario_id = $pdo->lastInsertId();

        // --- ESTADO ---
        $stmt = $pdo->prepare("SELECT ID FROM estado WHERE UF_SIGLA = ?");
        $stmt->execute([$estado_sigla]);
        $estado_id = $stmt->fetchColumn();

        if (!$estado_id) {
            $stmt = $pdo->prepare("INSERT INTO estado (UF_SIGLA) VALUES (?)");
            $stmt->execute([$estado_sigla]);
            $estado_id = $pdo->lastInsertId();
        }

        // --- CIDADE ---
        $stmt = $pdo->prepare("SELECT ID FROM cidade WHERE NOME = ? AND FK_ESTADO_ID = ?");
        $stmt->execute([$cidade_nome, $estado_id]);
        $cidade_id = $stmt->fetchColumn();

        if (!$cidade_id) {
            $stmt = $pdo->prepare("INSERT INTO cidade (NOME, FK_ESTADO_ID) VALUES (?, ?)");
            $stmt->execute([$cidade_nome, $estado_id]);
            $cidade_id = $pdo->lastInsertId();
        }

        // --- BAIRRO ---
        $stmt = $pdo->prepare("SELECT ID FROM bairro WHERE NOME = ? AND FK_CIDADE_ID = ?");
        $stmt->execute([$bairro_nome, $cidade_id]);
        $bairro_id = $stmt->fetchColumn();

        if (!$bairro_id) {
            $stmt = $pdo->prepare("INSERT INTO bairro (NOME, FK_CIDADE_ID) VALUES (?, ?)");
            $stmt->execute([$bairro_nome, $cidade_id]);
            $bairro_id = $pdo->lastInsertId();
        }

        // --- RUA ---
        $stmt = $pdo->prepare("SELECT ID FROM rua WHERE NUMERO = ? AND CEP = ? AND FK_BAIRRO_ID = ? AND DESC_RUA = ?");
        $stmt->execute([$numero, $cep, $bairro_id, $rua_nome]);
        $rua_id = $stmt->fetchColumn();

        if (!$rua_id) {
            $stmt = $pdo->prepare("INSERT INTO rua (NUMERO, CEP, FK_BAIRRO_ID, DESC_RUA) VALUES (?, ?, ?, ?)");
            $stmt->execute([$numero, $cep, $bairro_id, $rua_nome]);
            $rua_id = $pdo->lastInsertId();
        }

        // --- ENDEREÇO FINAL ---
        $stmt = $pdo->prepare("
            INSERT INTO endereco (COMPLEMENTO, FK_USUARIO_SISTEMA_ID, FK_RUA_ID)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$complemento, $usuario_id, $rua_id]);

        // ✅ Tudo certo
        $pdo->commit();
        $_SESSION['sucesso'] = "Usuário e endereço cadastrados com sucesso!";
        header("Location: concluidocadastro.html");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['erro'] = "Erro ao cadastrar: " . $e->getMessage();
        header("Location: cadastro.php");
        exit;
    }
}
?>
