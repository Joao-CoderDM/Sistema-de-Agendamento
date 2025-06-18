<?php
// Arquivo para verificar se um email já existe no banco de dados
header('Content-Type: application/json');

include_once('../Conexao/conexao.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim(strtolower($_POST['email']));
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE email = ?");
        $stmt->execute([$email]);
        $count = $stmt->fetchColumn();
        
        echo json_encode(['existe' => $count > 0]);
    } catch (PDOException $e) {
        echo json_encode(['existe' => false, 'erro' => 'Erro na consulta']);
    }
} else {
    echo json_encode(['existe' => false, 'erro' => 'Método não permitido']);
}
?>
    }
} else {
    echo json_encode(['existe' => false, 'erro' => 'Método não permitido']);
}
?>
