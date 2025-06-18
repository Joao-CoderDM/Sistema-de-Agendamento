<?php
include_once('../Conexao/conexao.php');

// Usando PDO diretamente
$sql_usuario = "SELECT * FROM usuario";
$stmt = $pdo->query($sql_usuario);
$qtd_usuario = $stmt->rowCount();

// Para compatibilidade, mantivemos o $query_usuario para código existente
$query_usuario = $stmt;
?>