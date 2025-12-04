<?php
session_start();
?>

<!DOCTYPE html> <!--Alterou o arquivo todo-->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoFuture - Energia Limpa e Sustentável</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/styles.css">
    <link rel="stylesheet" href="styles/header.css">
    <link rel="stylesheet" href="styles/cep_styles.css">
</head>
<body>

    <!-- Cabeçalho com navegação -->
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
                        <a class="nav-link" href="index.html">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Seção de cadastro -->
    <section id="cadastro" class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-5">
                    <h2 class="section-title">Realize seu cadastro</h2>
                    <p class="section-subtitle">Informações totalmente confidenciais</p>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- ÚNICO FORMULÁRIO -->
                    <form method="POST" action="caduser.php">
                        
                        <!-- === DADOS PESSOAIS === -->
                        <h4 class="mb-3">Dados Pessoais</h4>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       placeholder="Maria da Silva Santos" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telefone" class="form-label">Telefone de contato</label>
                                <input type="tel" class="form-control" id="telefone" name="telefone" 
                                       placeholder="(00) 0000-0000" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="maria333@gmail.com" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="senha" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="senha" name="senha" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="senhaconf" class="form-label">Confirme sua senha</label>
                                <input type="password" class="form-control" id="senhaconf" name="senhaconf" required>
                            </div>
                        </div>
                        
                        <!-- === DADOS DO ENDEREÇO === -->
                        <h4 class="mb-3 mt-4">Cadastro da Residência</h4>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="CEP" class="form-label">CEP</label>
                                <input type="text" class="form-control" id="CEP" name="CEP" 
                                       placeholder="00.000-000" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="rua" class="form-label">Endereço</label>
                                <input type="text" class="form-control" id="rua" name="rua" 
                                       placeholder="Rua Itacir das Rosas" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="num" class="form-label">Número</label>
                                <input type="number" class="form-control" id="num" name="num" 
                                       placeholder="581" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="complemento" class="form-label">Complemento</label>
                                <input type="text" class="form-control" id="complemento" name="complemento" 
                                       placeholder="Apto, Bloco">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="bairro" class="form-label">Bairro</label>
                                <input type="text" class="form-control" id="bairro" name="bairro" 
                                       placeholder="Jardim dos Ypês" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="regiao" class="form-label">Região</label>
                                <input type="text" class="form-control" id="regiao" name="regiao" 
                                       placeholder="Leste">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cidade" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="cidade" name="cidade" 
                                       placeholder="Campinas" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <input type="text" class="form-control" id="estado" name="estado" 
                                       placeholder="SP" maxlength="2" required>
                            </div>
                        </div>

                        <!-- Botão de enviar -->
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-4">
                                <i class="bi bi-arrow-right me-2"></i>Próximo
                            </button>
                        </div>

                        <br>
                        <p class="text-center">Já tem uma conta? <a href="login.php" class="link-primary">Login</a></p>
                        <div class="text-center">
                            <a href="index.html" class="link-secondary voltar">← Voltar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Rodapé -->
    <footer class="bg-dark text-white py-4">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/buscar_cep.js"></script>
</body>
</html>