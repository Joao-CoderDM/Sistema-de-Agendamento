<?php
// Este arquivo seria responsável por validar os dados de login submetidos pelo formulário
// Verifica se sessão já foi iniciada
session_start();

header('Content-Type: application/json');

try {
    // Verificar se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de requisição inválido');
    }
    
    // Verificar se os campos estão presentes
    if (!isset($_POST['email']) || !isset($_POST['senha'])) {
        throw new Exception('Email e senha são obrigatórios');
    }
    
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }
    
    // Incluir conexão com banco
    include_once('../Conexao/conexao.php');
    
    // Verificar se a conexão existe
    if (!isset($pdo)) {
        throw new Exception('Erro de conexão com o banco de dados');
    }
    
    // Buscar usuário no banco
    $sql = "SELECT id_usuario, nome, email, senha, tipo_usuario, ativo FROM usuario WHERE email = ? AND ativo = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        throw new Exception('Email ou senha incorretos');
    }
    
    // Verificar senha
    if (!password_verify($senha, $usuario['senha'])) {
        throw new Exception('Email ou senha incorretos');
    }
    
    // Login bem-sucedido - criar sessão
    $_SESSION['loggedin'] = true;
    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['nome'] = $usuario['nome'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
    
    // Determinar página de redirecionamento
    $redirect = '';
    switch ($usuario['tipo_usuario']) {
        case 'Cliente':
            $redirect = '../HTML/sistema_cliente.php';
            break;
        case 'Profissional':
            $redirect = '../HTML/sistema_profissional.php';
            break;
        case 'Administrador':
            $redirect = '../HTML/sistema_adm.php';
            break;
        default:
            $redirect = '../HTML/index.php';
    }
    
    echo json_encode([
        'status' => 'sucesso',
        'mensagem' => 'Login realizado com sucesso!',
        'redirect' => $redirect
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage()
    ]);
}
?>