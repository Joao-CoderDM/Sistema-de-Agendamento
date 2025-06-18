<?php
// Arquivo para verificar se um email existe no banco de dados (para login)
header('Content-Type: application/json');

include_once('../Conexao/conexao.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = filter_var(trim(strtolower($_POST['email'])), FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['existe' => false, 'erro' => 'Email inválido']);
        exit;
    }
    
    try {
        // Prepara a consulta para verificar se o email existe
        $query = "SELECT id_usuario FROM usuario WHERE email = ? AND ativo = 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        
        // Retorna verdadeiro se o email existir
        echo json_encode(['existe' => ($result !== false)]);
    } catch (PDOException $e) {
        echo json_encode(['existe' => false, 'erro' => 'Erro na consulta']);
    }
} else {
    // Se não houver email na requisição, retorna erro
    echo json_encode(['existe' => false, 'erro' => 'Método não permitido']);
}
?>
