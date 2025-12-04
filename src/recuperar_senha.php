<?php
session_start();
require_once "config.php";

// Aqui o usuário vai apenas definir uma nova senha diretamente
// Sem e-mail, sem validação externa

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $novaSenha = trim($_POST["novaSenha"]);
    $confirmarSenha = trim($_POST["confirmarSenha"]);

    if ($novaSenha !== $confirmarSenha) {
        echo "<script>alert('As senhas não coincidem!');</script>";
    } else {
        // Atualiza diretamente no banco
      $sql = "UPDATE usuario_sistema SET SENHA = ? WHERE EMAIL = ?";
$stmt = $pdo->prepare($sql);

$senhaCriptografada = password_hash($novaSenha, PASSWORD_DEFAULT);

$stmt->execute([$senhaCriptografada, $email]);


        echo "<script>
                alert('Senha alterada com sucesso!');
                window.location.href='login.php';
              </script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoFuture - Alterar Senha</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Seus estilos -->
    <link rel="stylesheet" href="styles/styles.css">
    <link rel="stylesheet" href="styles/header.css">
</head>
<body>

    <!-- Cabeçalho -->
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
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Voltar</a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- SEÇÃO PRINCIPAL -->
    <section id="cadastro" class="py-5 bg-light" style="margin-top: 70px;">
        <div class="container">

            <div class="row">
                <div class="col-lg-12 text-center mb-5">
                    <h2 class="section-title">Alterar Senha</h2>
                    <p class="text-muted">Digite seu e-mail e escolha uma nova senha.</p>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-6">

                    <form method="POST" class="contact-form">

                        <div class="mb-3">
                            <label class="form-label">E-mail cadastrado</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nova senha</label>
                            <input type="password" name="novaSenha" class="form-control" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Confirmar nova senha</label>
                            <input type="password" name="confirmarSenha" class="form-control" required>
                        </div>

                        <div class="text-center">
                            <button class="btn btn-success btn-lg px-4" type="submit">
                                Alterar Senha
                            </button>
                        </div>

                    </form>

                    <div class="text-center mt-4">
                        <a href="login.php" class="link-secondary">← Voltar ao login</a>
                    </div>

                </div>
            </div>

        </div>
    </section>

    <!-- Rodapé -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <h5><i class="fas fa-solar-panel me-2"></i>EcoFuture</h5>
                    <p class="mb-0">Energia limpa para um futuro sustentável</p>
                </div>
                <div class="col-lg-6 text-lg-end">
                    <p class="mb-0">&copy; 2025 EcoFuture. Todos os direitos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
