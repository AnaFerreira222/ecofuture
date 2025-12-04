<?php
/**
 * API de Consulta de CEP - Versão Alternativa
 * Utiliza file_get_contents em vez de cURL
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Verifica se o CEP foi enviado
if (!isset($_GET['cep'])) {
    echo json_encode([
        'erro' => true,
        'mensagem' => 'CEP não informado'
    ]);
    exit;
}

$cep = preg_replace('/[^0-9]/', '', $_GET['cep']); // Remove caracteres não numéricos

// Valida o CEP (deve ter 8 dígitos)
if (strlen($cep) != 8) {
    echo json_encode([
        'erro' => true,
        'mensagem' => 'CEP inválido. Deve conter 8 dígitos.'
    ]);
    exit;
}

// Consulta a API ViaCEP
$url = "https://viacep.com.br/ws/{$cep}/json/";

// Configurações do contexto para file_get_contents
$options = [
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
        'timeout' => 10,
        'ignore_errors' => true
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
];

$context = stream_context_create($options);

// Tenta obter os dados
$response = @file_get_contents($url, false, $context);

// Verifica se houve erro na requisição
if ($response === false) {
    echo json_encode([
        'erro' => true,
        'mensagem' => 'Erro ao consultar CEP. Verifique sua conexão.'
    ]);
    exit;
}

// Decodifica a resposta
$dados = json_decode($response, true);

// Verifica se houve erro no JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'erro' => true,
        'mensagem' => 'Erro ao processar resposta da API'
    ]);
    exit;
}

// Verifica se o CEP foi encontrado
if (isset($dados['erro']) && $dados['erro'] === true) {
    echo json_encode([
        'erro' => true,
        'mensagem' => 'CEP não encontrado'
    ]);
    exit;
}

// Retorna os dados formatados
echo json_encode([
    'erro' => false,
    'cep' => $dados['cep'] ?? '',
    'logradouro' => $dados['logradouro'] ?? '',
    'complemento' => $dados['complemento'] ?? '',
    'bairro' => $dados['bairro'] ?? '',
    'localidade' => $dados['localidade'] ?? '',
    'uf' => $dados['uf'] ?? '',
    'regiao' => $dados['regiao'] ?? ''
]);
?>