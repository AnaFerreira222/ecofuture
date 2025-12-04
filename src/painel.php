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

<!-- Modal de Informações da Placa -->
<div class="modal fade" id="placaModal" tabindex="-1" aria-labelledby="placaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content placa-info">
      <div class="modal-header">
        <h5 class="modal-title" id="placaModalLabel">String #1</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body" id="placaModalBody">
        <div class="info-box">Modelo nominal: Cannadian Wolf</div>
        <div class="info-box">Potência nominal: 40 kWh</div>
        <div class="info-box">Quantidade: 10</div>
        <div class="info-box">Potência Total da String: 400 kWh</div>
      </div>
      <div class="modal-footer justify-content-center">
        <button class="btn btn-danger" id="excluirBtn">Excluir</button>
        <button class="btn btn-light" id="editarBtn">Editar</button>
        <button class="btn btn-success d-none" id="salvarBtn">Salvar</button>
      </div>
    </div>
  </div>
</div>


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
                        <a class="nav-link" href="monitora.html">Voltar</a>
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
        <h2 class="section-title">Placas Instaladas</h2>
        <p class="section-subtitle">Painel de monitoramento</p>
      </div>
    </div>

    <!-- Linha 1 -->
    <div class="row">
      <div class="col-lg-4 col-md-6 mb-4">
        <a href="monitoramento.html" class="service-link">
          <div class="service-card h-100">
            <div class="service-icon"><i class="fa-solid fa-solar-panel" style="color: #4a7c59;"></i></div>
            <h4>String 1</h4>
          </div>
        </a>
      </div>

      <div class="col-lg-4 col-md-6 mb-4">
        <a href="monitoramento.html" class="service-link">
          <div class="service-card h-100">
             <div class="service-icon"><i class="fa-solid fa-solar-panel" style="color: #4a7c59;"></i></div>
            <h4>String 2</h4>
          </div>
        </a>
      </div>

      <div class="col-lg-4 col-md-6 mb-4">
        <a href="monitoramento.html" class="service-link">
          <div class="service-card h-100">
             <div class="service-icon"><i class="fa-solid fa-solar-panel" style="color: #4a7c59;"></i></div>
            <h4>String 3</h4>
          </div>
        </a>
      </div>
    </div>

    <!-- Linha 2 -->
    <div class="row">
      <div class="col-lg-4 col-md-6 mb-4">
        <a href="monitoramento.html" class="service-link">
          <div class="service-card h-100">
             <div class="service-icon"><i class="fa-solid fa-solar-panel" style="color: #4a7c59;"></i></div>
            <h4>String 4</h4>
          </div>
        </a>
      </div>

      <div class="col-lg-4 col-md-6 mb-4">
        <a href="monitoramento.html" class="service-link">
          <div class="service-card h-100">
             <div class="service-icon"><i class="fa-solid fa-solar-panel" style="color: #4a7c59;"></i></div>
            <h4>String 5</h4>
          </div>
        </a>
      </div>

      <div class="col-lg-4 col-md-6 mb-4">
        <a href="monitoramento.html" class="service-link">
          <div class="service-card h-100">
             <div class="service-icon"><i class="fa-solid fa-solar-panel" style="color: #4a7c59;"></i></div>
            <h4>String 6</h4>
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
          <div class="col-lg-4">
              <h5><i class="fas fa-solar-panel me-2"></i>EcoFuture</h5>
              <p class="mb-0">Energia limpa para um futuro sustentável</p>
          </div>

          <div class="col-lg-4 text-center">
            <p class="mb-1"><i class="fa-solid fa-phone me-2"></i>Contato: (19) 1234-5678</p>
            <p class="mb-1"><i class="fa-solid fa-envelope"></i> E-mail: EcoFuture@gmail.com</p>
            <p class="mb-1"> <i class="fa-brands fa-instagram"></i> Instagram: EcoFuture_Energia</p>
            <p class="mb-1"> <i class="fa-brands fa-linkedin"></i> Linkedin: EcoFutureEnergia</p>
        </div>

          <div class="col-lg-4 text-lg-end">
              <p class="mb-0">&copy; 2025 EcoFuture. Todos os direitos reservados.</p>
          </div>
      </div>
  </div>
</footer>

<script>
  const placas = {
    1: { modelo: "Cannadian Wolf", potencia: "40", qtd: 10 },
    2: { modelo: "Trina Solar", potencia: "45", qtd: 8 },
    3: { modelo: "JA Solar", potencia: "50", qtd: 6 },
    4: { modelo: "BYD Green", potencia: "35", qtd: 12 },
    5: { modelo: "Jinko Tiger", potencia: "42", qtd: 9 },
    6: { modelo: "Q Cells", potencia: "38", qtd: 11 }
  };

  let placaAtual = null;

  document.querySelectorAll('.service-link').forEach((link, index) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const id = index + 1;
      const placa = placas[id];
      placaAtual = id;

      const total = placa.qtd * placa.potencia;

      document.getElementById('placaModalLabel').textContent = `String #${id}`;
      document.getElementById('placaModalBody').innerHTML = `
        <div class="info-box">Modelo nominal: ${placa.modelo}</div>
        <div class="info-box">Potência nominal: ${placa.potencia} kWh</div>
        <div class="info-box">Quantidade: ${placa.qtd}</div>
        <div class="info-box">Potência Total da String: ${total} kWh</div>
      `;

      document.getElementById('editarBtn').classList.remove('d-none');
      document.getElementById('salvarBtn').classList.add('d-none');

      const modal = new bootstrap.Modal(document.getElementById('placaModal'));
      modal.show();
    });
  });

  // Ao clicar em "Editar"
  document.getElementById('editarBtn').addEventListener('click', () => {
    const placa = placas[placaAtual];
    const body = document.getElementById('placaModalBody');
    body.innerHTML = `
      <div class="info-box">
        Modelo nominal: <input type="text" id="modeloInput" class="form-control" value="${placa.modelo}">
      </div>
      <div class="info-box">
        Potência nominal (kWh): <input type="number" id="potenciaInput" class="form-control" value="${placa.potencia}">
      </div>
      <div class="info-box">
        Quantidade: <input type="number" id="qtdInput" class="form-control" value="${placa.qtd}">
      </div>
    `;
    document.getElementById('editarBtn').classList.add('d-none');
    document.getElementById('salvarBtn').classList.remove('d-none');
  });

  // Ao clicar em "Salvar"
  document.getElementById('salvarBtn').addEventListener('click', () => {
    const modelo = document.getElementById('modeloInput').value;
    const potencia = parseInt(document.getElementById('potenciaInput').value);
    const qtd = parseInt(document.getElementById('qtdInput').value);
    const total = potencia * qtd;

    placas[placaAtual] = { modelo, potencia, qtd };

    const body = document.getElementById('placaModalBody');
    body.innerHTML = `
      <div class="info-box">Modelo nominal: ${modelo}</div>
      <div class="info-box">Potência nominal: ${potencia} kWh</div>
      <div class="info-box">Quantidade: ${qtd}</div>
      <div class="info-box">Potência Total da String: ${total} kWh</div>
    `;

    document.getElementById('editarBtn').classList.remove('d-none');
    document.getElementById('salvarBtn').classList.add('d-none');
  });

  // 🗑️ Ao clicar em "Excluir"
  document.getElementById('excluirBtn').addEventListener('click', () => {
    if (confirm("Tem certeza que deseja excluir esta placa?")) {
      // Remove visualmente o card da placa
      const card = document.querySelectorAll('.service-link')[placaAtual - 1];
      if (card) card.closest('.col-lg-4').remove();

      // Remove dos dados
      delete placas[placaAtual];

      // Fecha o modal
      const modal = bootstrap.Modal.getInstance(document.getElementById('placaModal'));
      modal.hide();

      alert("Placa excluída com sucesso!");
    }
  });
</script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>