<?php
// Garante que o PHP exiba qualquer erro de conexão local se algo estiver errado
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurações de acesso ao banco de dados do XAMPP
$host    = "localhost";
$dbname  = "securtech";
$usuario = "root";
$senha   = ""; // Padrão do XAMPP local é vazio

try {
    // Cria a conexão PDO configurando o banco para aceitar acentos (UTF-8)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $usuario, $senha);
    
    // Configura o PDO para lançar exceções em caso de erros SQL (ajuda muito a descobrir bugs)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Se o banco estiver desligado ou a senha errada, o sistema para aqui e avisa o motivo
    die("Erro crítico de conexão com o banco de dados: " . $e->getMessage());
}