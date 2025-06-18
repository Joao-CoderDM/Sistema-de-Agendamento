<?php 
session_start();
include_once('../Conexao/conexao.php');

if (isset($_POST['confirmar'])) {
    // Verificar se usuário está logado
    if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
        header('Location: ../HTML/login.php');
        exit;
    }
    
    $id_usuario = $_SESSION['id_usuario'];
    
    try {
        // Marcar como inativo ao invés de excluir
        $stmt = $pdo->prepare("UPDATE usuario SET ativo = 0 WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        
        // Destruir sessão
        session_destroy();
        
        $_SESSION = [];
        $_SESSION['sucesso_exclusao'] = "Conta desativada com sucesso";
        
        header('Location: ../HTML/index.php');
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['erro_exclusao'] = "Erro ao desativar conta";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}
?>