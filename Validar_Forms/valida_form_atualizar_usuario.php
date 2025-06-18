<?php
session_start();
include_once('../Conexao/conexao.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar se usuário está logado
    if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
        header('Location: ../HTML/login.php');
        exit;
    }
    
    $id_usuario = filter_var($_POST['id_usuario'] ?? '', FILTER_VALIDATE_INT);
    
    // Verificar se o ID é válido e corresponde ao usuário logado ou se é admin
    if (!$id_usuario || ($id_usuario != $_SESSION['id_usuario'] && $_SESSION['tipo_usuario'] !== 'Administrador')) {
        $_SESSION['erro_atualizacao'] = "Acesso negado";
        header('Location: ../HTML/sistema_cliente.php');
        exit;
    }
    
    // Sanitizar dados
    $nome = filter_var($_POST['nome'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var(trim(strtolower($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
    $telefone = filter_var($_POST['telefone'] ?? '', FILTER_SANITIZE_STRING);
    $data_nascimento = $_POST['data_nascimento'];
    $senha = $_POST['senha'];

    // Validações básicas
    if (empty($nome) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($telefone)) {
        $_SESSION['erro_atualizacao'] = "Todos os campos são obrigatórios e devem ser válidos";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    
    // Verifica se a senha foi alterada
    if (!empty($senha)) {
        $senha = password_hash($senha, PASSWORD_DEFAULT); // Aplica o hash na senha
    } else {
        // Caso a senha não tenha sido alterada, mantenha a senha atual
        $query = "SELECT senha FROM usuario WHERE id_usuario = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_usuario]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $senha = $result['senha']; // Mantém o hash da senha atual
        } else {
            die("Usuário não encontrado.");
        }
    }

    // Atualiza os dados do usuário no banco de dados
    $query = "UPDATE usuario SET nome = ?, email = ?, telefone = ?, data_nascimento = ?, senha = ? WHERE id_usuario = ?";
    $stmt = $pdo->prepare($query);

    if ($stmt->execute([$nome, $email, $telefone, $data_nascimento, $senha, $id_usuario])) {
        // Redireciona para a página com o parâmetro sucesso=1
        header("Location: ../Cadastrados/meus_usuarios.php?sucesso=1");
        exit;
    } else {
        echo "Erro ao atualizar usuário: " . implode(", ", $stmt->errorInfo());
    }
}
?>