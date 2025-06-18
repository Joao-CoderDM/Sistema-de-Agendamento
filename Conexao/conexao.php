<?php
try {
    // Configurações do banco de dados
    $host = 'localhost';
    $dbname = 'barbd1'; // Nome do seu banco
    $username = 'root';
    $password = '';
    
    // Criar conexão PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Configurar PDO para mostrar erros
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Conexão MySQLi como alternativa
    $mysqli = new mysqli($host, $username, $password, $dbname);
    
    if ($mysqli->connect_error) {
        throw new Exception("Erro na conexão MySQLi: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
    
} catch (PDOException $e) {
    // Log apenas em desenvolvimento - comentar em produção
    // error_log("Erro PDO: " . $e->getMessage());
    die("Erro de conexão com o banco de dados");
} catch (Exception $e) {
    // Log apenas em desenvolvimento - comentar em produção
    // error_log("Erro MySQLi: " . $e->getMessage());
    die("Erro de conexão com o banco de dados");
}
?>