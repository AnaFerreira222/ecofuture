<?php 
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoFuture - Energia Limpa e Sustentável</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- CSS personalizado -->
    <link rel="stylesheet" href="styles/styles.css">
    <link rel="stylesheet" href="styles/header.css">
</head>
<body>

    <!-- Cabeçalho com navegação -->
    <header class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <!-- Logo da empresa -->
            <a class="navbar-brand fw-bold" href="index.html">
                <i class="fas fa-solar-panel me-2"></i>EcoFuture
            </a>
            
            <!-- Botão para menu mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Menu de navegação -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="perfil.php">Perfil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.html">Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    </br>

<!-- Cards animados de serviços-->
<section id="servicos">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center mb-5">

<?php
// --- CARREGAR NOME CORRETAMENTE ---

require_once 'config.php';

// Recupera ID e Nome da sessão
$id = $_SESSION['ID'] ?? null;
$NomeUsuario = $_SESSION['_Nome'] ?? null;

// Se tiver o ID salvo, busca os dados do BD
if ($id) {
    $sql = "SELECT * FROM usuario_sistema WHERE ID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Atualiza nome caso precise
    if ($usuario) {
        $NomeUsuario = $usuario['_Nome'];  
    }
}

// Exibe o nome corretamente
echo "
<h2 class='section-title'>Olá, $NomeUsuario</h2>
<p class='section-subtitle'>Painel de monitoramento</p>
";
?>

   <!-- Cards animados de serviços-->

   
   

    <!-- Linha 1 -->
    <div class="row">
      <div class="col-lg-6 col-md-6 mb-4">
        <a href="placas.php" class="service-link">
          <div class="service-card h-100">
            <div class="service-icon"><i class="fa-solid fa-solar-panel"></i></div>
            <h4>Placas</h4>
            <p>Adicione, edite e acompanhe todas as placas instaladas para manter seu sistema sempre atualizado.</p>
          </div>
        </a>
      </div>

      <div class="col-lg-6 col-md-6 mb-4">
        <a href="desempenho.php" class="service-link">
          <div class="service-card h-100">
            <div class="service-icon"><i class="fa-solid fa-chart-line"></i></div>
            <h4>Desempenho e Irradiação Solar</h4>
            <p>Visualize gráficos por dia, mês e ano e compare períodos para entender a performance.</p>
          </div>
        </a>
      </div>
    </div>

    <!-- Linha 2 -->
    <div class="row">
      <div class="col-lg-6 col-md-6 mb-4">
        <a href="geracaoconsu.php" class="service-link">
          <div class="service-card h-100">
            <div class="service-icon"><i class="fa-solid fa-file-lines"></i></div>
            <h4>Geração e Consumo</h4>
            <p>Acompanhe instantaneamente a geração, consumo e status do seu sistema.</p>
          </div>
        </a>
      </div>

      <div class="col-lg-6 col-md-6 mb-4">
        <a href="mensal.php" class="service-link">
          <div class="service-card h-100">
            <div class="service-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div>
            <h4>Relatório mensal de desempenho</h4>
            <p>Confira o relatório mensal para monitorar resultados, comparar meses e avaliar a eficiência do sistema.</p>
          </div>
        </a>
      </div>
    </div>

    <!-- Linha 3 -->
    <div class="row">
      <div class="col-lg-12 col-md-12 mb-4">
        <a href="economia.php" class="service-link">
          <div class="service-card h-100">
            <div class="service-icon"><i class="fa-solid fa-coins"></i></div>
            <h4>Economia e Autossuficiência</h4>
            <p>Veja a economia em R$, créditos de compensação e simule payback e ROI.</p>
          </div>
        </a>
</div>
    </div>
  </div>
</section>

<!-- Rodapé -->
<footer class="bg-dark text-white py-4">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h5><i class="fas fa-solar-panel me-2"></i>EcoFuture</h5>
                <p class="mb-0">Energia limpa para um futuro sustentável</p>
            </div>

            <div class="col-lg-4 text-center">
                <p class="mb-1"><i class="fa-solid fa-phone me-2"></i>Contato: (19) 1234-5678</p>
                <p class="mb-1"><i class="fa-solid fa-envelope"></i> E-mail: EcoFuture@gmail.com</p>
                <p class="mb-1"><i class="fa-brands fa-instagram"></i> Instagram: EcoFuture_Energia</p>
                <p class="mb-1"><i class="fa-brands fa-linkedin"></i> Linkedin: EcoFutureEnergia</p>
            </div>

            <div class="col-lg-4 text-lg-end">
                <p class="mb-0">&copy; 2025 EcoFuture. Todos os direitos reservados.</p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>