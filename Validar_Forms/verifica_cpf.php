<?php
include_once('../Conexao/conexao.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cpf'])) {
    $cpf = preg_replace("/[^0-9]/", "", $_POST['cpf']);
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE cpf = ?");
        $stmt->execute([$cpf]);
        $count = $stmt->fetchColumn();
        
        echo json_encode(['existe' => $count > 0]);
    } catch (PDOException $e) {
        echo json_encode(['existe' => false, 'erro' => 'Erro na consulta']);
    }
} else {
    echo json_encode(['existe' => false, 'erro' => 'Método inválido']);
}
?>
