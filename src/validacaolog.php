<?php
session_start();
require_once "config.php";

if (empty($_POST["email"])) {
    header("Location: login.php?error=empty_email");
    exit;
}

if (empty($_POST["senha"])) {
    header("Location: login.php?error=empty_password");
    exit;
}

$email = $_POST["email"];
$senha = $_POST["senha"];

try {
    // Buscar usuário pelo e-mail
    $sql = "SELECT * FROM usuario_sistema WHERE EMAIL = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario["SENHA"])) {

        // Login bem-sucedido
        $_SESSION["ID"] = $usuario["ID"];
        $_SESSION["_Nome"] = $usuario["_Nome"]; // <- AGORA 100% CORRETO
        
        header("Location: concluidologin.html");
        exit;

    } else {
        header("Location: login.php?error=invalid_credentials");
        exit;
    }

} catch (PDOException $e) {
    error_log("Erro no login: " . $e->getMessage());
    header("Location: login.php?error=db_error");
    exit;
}
?>
