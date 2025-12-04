<?php
// Configurações do banco de dados
$host = 'localhost';
$usuario = 'root';
$senha = '';
$banco = 'banco';
$porta = '3306'; // porta padrão do MySQL no XAMPP

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$banco;port=$porta;charset=utf8",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
?>
