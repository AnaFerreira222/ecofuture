/**
 * Script para busca automática de CEP
 * Busca DIRETAMENTE na API ViaCEP (SEM PRECISAR DE PHP)
 */

document.addEventListener('DOMContentLoaded', function() {
    const campoCEP = document.getElementById('CEP');
    
    if (campoCEP) {
        // Adiciona máscara ao CEP enquanto digita
        campoCEP.addEventListener('input', function(e) {
            let valor = e.target.value.replace(/\D/g, ''); // Remove não-numéricos
            
            if (valor.length <= 8) {
                valor = valor.replace(/^(\d{5})(\d)/, '$1-$2'); // Adiciona hífen
                e.target.value = valor;
            }
        });
        
        // Busca o CEP quando o usuário terminar de digitar
        campoCEP.addEventListener('blur', function() {
            buscarCEP(this.value);
        });
        
        // Ou busca quando pressionar Enter
        campoCEP.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarCEP(this.value);
            }
        });
    }
});

/**
 * Função para buscar CEP DIRETO na API ViaCEP
 */
function buscarCEP(cep) {
    // Remove caracteres não numéricos
    cep = cep.replace(/\D/g, '');
    
    // Valida o CEP
    if (cep.length !== 8) {
        return;
    }
    
    // Exibe loading
    mostrarLoading(true);
    limparCampos();
    
    // URL da API ViaCEP - BUSCA DIRETA SEM PHP
    const url = `https://viacep.com.br/ws/${cep}/json/`;
    
    // Faz a requisição DIRETA para a API ViaCEP
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na requisição');
            }
            return response.json();
        })
        .then(dados => {
            mostrarLoading(false);
            
            // Verifica se houve erro
            if (dados.erro) {
                mostrarErro('CEP não encontrado. Verifique e tente novamente.');
                return;
            }
            
            // Preenche os campos com os dados retornados
            preencherCampos(dados);
            mostrarSucesso('CEP encontrado! Endereço preenchido automaticamente.');
            
            // Foca no campo número (primeiro campo vazio)
            setTimeout(() => {
                document.getElementById('num').focus();
            }, 500);
        })
        .catch(erro => {
            mostrarLoading(false);
            mostrarErro('Erro ao buscar CEP. Verifique sua conexão com a internet.');
            console.error('Erro:', erro);
        });
}

/**
 * Preenche os campos do formulário com os dados do CEP
 */
function preencherCampos(dados) {
    const campos = {
        'rua': dados.logradouro || '',
        'bairro': dados.bairro || '',
        'cidade': dados.localidade || '',
        'estado': dados.uf || '',
        'regiao': dados.complemento || ''
    };
    
    for (let campo in campos) {
        const elemento = document.getElementById(campo);
        if (elemento && campos[campo]) {
            elemento.value = campos[campo];
            elemento.classList.add('campo-preenchido');
            
            // Remove a classe após animação
            setTimeout(() => {
                elemento.classList.remove('campo-preenchido');
            }, 1000);
        }
    }
}

/**
 * Limpa os campos de endereço
 */
function limparCampos() {
    const campos = ['rua', 'bairro', 'cidade', 'estado', 'regiao'];
    campos.forEach(campo => {
        const elemento = document.getElementById(campo);
        if (elemento) {
            elemento.value = '';
        }
    });
}

/**
 * Mostra/oculta indicador de loading
 */
function mostrarLoading(mostrar) {
    const campoCEP = document.getElementById('CEP');
    
    if (mostrar) {
        campoCEP.classList.add('loading');
        campoCEP.disabled = true;
    } else {
        campoCEP.classList.remove('loading');
        campoCEP.disabled = false;
    }
}

/**
 * Exibe mensagem de erro
 */
function mostrarErro(mensagem) {
    const campoCEP = document.getElementById('CEP');
    
    // Remove mensagens anteriores
    removerMensagens();
    
    // Cria e exibe nova mensagem
    const divErro = document.createElement('div');
    divErro.className = 'cep-erro alert alert-danger alert-dismissible fade show mt-2';
    divErro.innerHTML = `
        <i class="fas fa-exclamation-circle me-2"></i>${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    campoCEP.parentElement.appendChild(divErro);
    
    // Remove automaticamente após 5 segundos
    setTimeout(() => {
        if (divErro.parentElement) {
            divErro.remove();
        }
    }, 5000);
}

/**
 * Exibe mensagem de sucesso
 */
function mostrarSucesso(mensagem) {
    const campoCEP = document.getElementById('CEP');
    
    // Remove mensagens anteriores
    removerMensagens();
    
    // Cria e exibe nova mensagem
    const divSucesso = document.createElement('div');
    divSucesso.className = 'cep-sucesso alert alert-success alert-dismissible fade show mt-2';
    divSucesso.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    campoCEP.parentElement.appendChild(divSucesso);
    
    // Remove automaticamente após 3 segundos
    setTimeout(() => {
        if (divSucesso.parentElement) {
            divSucesso.remove();
        }
    }, 3000);
}

/**
 * Remove todas as mensagens de erro e sucesso
 */
function removerMensagens() {
    const mensagens = document.querySelectorAll('.cep-erro, .cep-sucesso');
    mensagens.forEach(msg => msg.remove());
}