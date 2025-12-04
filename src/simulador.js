(function() {
  const form = document.getElementById('form-simulador');
  const btnLimpar = document.getElementById('limpar');
  const alerta = document.getElementById('alerta');
  const resultado = document.getElementById('resultado');

  const genMensalEl = document.getElementById('genMensal');
  const ecoMensalEl = document.getElementById('ecoMensal');
  const ecoAnualEl = document.getElementById('ecoAnual');
  const investimentoEl = document.getElementById('investimento');
  const paybackBadge = document.getElementById('paybackBadge');

  // Parâmetros simples
  const FATOR_GERACAO = 120; // kWh/mês por kWp (média simples Brasil)
  const CUSTO_POR_KWP = 5500; // R$ por kWp (estimativa simples)

  function formatarMoeda(valor) {
    return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  }

  function calcular(e) {
    e.preventDefault();
    alerta.classList.add('d-none');

    const consumo = parseFloat(document.getElementById('consumo').value);
    const tarifa = parseFloat(document.getElementById('tarifa').value);
    const potencia = parseFloat(document.getElementById('potencia').value);

    const dadosValidos = [consumo, tarifa, potencia].every(v => !isNaN(v) && v >= 0);

    if (!dadosValidos) {
      resultado.classList.add('d-none');
      alerta.classList.remove('d-none');
      return;
    }

    // Geração mensal estimada (kWh)
    const geracaoMensal = potencia * FATOR_GERACAO;

    // Consumo compensado é limitado ao consumo do cliente
    const kwhCompensados = Math.min(geracaoMensal, consumo);

    // Economia mensal (R$)
    const economiaMensal = kwhCompensados * tarifa;

    // Economia anual
    const economiaAnual = economiaMensal * 12;

    // Investimento estimado
    const investimento = potencia * CUSTO_POR_KWP;

    // Payback
    const payback = economiaAnual > 0 ? (investimento / economiaAnual) : Infinity;

    // Render
    genMensalEl.textContent = geracaoMensal.toFixed(0);
    ecoMensalEl.textContent = formatarMoeda(economiaMensal);
    ecoAnualEl.textContent = formatarMoeda(economiaAnual);
    investimentoEl.textContent = formatarMoeda(investimento);
    paybackBadge.textContent = isFinite(payback) ? `Payback: ${payback.toFixed(1)} anos` : 'Payback: —';

    resultado.classList.remove('d-none');
  }

  function limpar() {
    form.reset();
    resultado.classList.add('d-none');
    alerta.classList.add('d-none');
  }

  form.addEventListener('submit', calcular);
  btnLimpar.addEventListener('click', limpar);
})();