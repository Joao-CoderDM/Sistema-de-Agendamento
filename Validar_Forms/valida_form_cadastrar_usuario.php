<?php
// Verificar se a sessão já foi iniciada ANTES de chamar session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Ativar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Função auxiliar para validar CPF
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

try {
    // Verificar se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de requisição inválido');
    }
    
    // Incluir conexão com banco
    include_once('../Conexao/conexao.php');
    
    // Verificar se a conexão existe
    if (!isset($pdo)) {
        throw new Exception('Erro de conexão com o banco de dados');
    }
    
    // Coletar e sanitizar dados
    $nome = trim($_POST['nome'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $telefone = trim($_POST['telefone'] ?? '');
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $tipo_usuario = $_POST['tipo_usuario'] ?? 'Cliente';
    
    // Debug: log dos dados recebidos
    error_log("=== INÍCIO CADASTRO ===");
    error_log("Nome: $nome");
    error_log("Email: $email");
    error_log("Tipo: $tipo_usuario");
    error_log("CPF: $cpf");
    
    // Array para armazenar erros
    $erros = [];
    
    // Validações básicas
    if (empty($nome)) {
        $erros[] = "Nome é obrigatório";
    } elseif (strlen($nome) < 2) {
        $erros[] = "Nome deve ter pelo menos 2 caracteres";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Email válido é obrigatório";
    }
    
    if (empty($telefone)) {
        $erros[] = "Telefone é obrigatório";
    }
    
    if (empty($data_nascimento)) {
        $erros[] = "Data de nascimento é obrigatória";
    } else {
        $hoje = new DateTime();
        $nascimento = new DateTime($data_nascimento);
        $idade = $hoje->diff($nascimento)->y;
        
        if ($idade < 10) {
            $erros[] = "Idade mínima é 10 anos";
        } elseif ($idade > 120) {
            $erros[] = "Data de nascimento inválida";
        }
    }
    
    if (empty($cpf) || strlen($cpf) !== 11) {
        $erros[] = "CPF deve conter 11 dígitos";
    } elseif (!validarCPF($cpf)) {
        $erros[] = "CPF inválido";
    }
    
    if (empty($senha)) {
        $erros[] = "Senha é obrigatória";
    } elseif (strlen($senha) < 6) {
        $erros[] = "Senha deve ter pelo menos 6 caracteres";
    }
    
    if ($senha !== $confirmar_senha) {
        $erros[] = "Senhas não coincidem";
    }
    
    // Verificar se email já existe
    if (empty($erros)) {
        $sql_check_email = "SELECT id_usuario FROM usuario WHERE email = ?";
        $stmt_check_email = $pdo->prepare($sql_check_email);
        $stmt_check_email->execute([$email]);
        
        if ($stmt_check_email->rowCount() > 0) {
            $erros[] = "Este email já está sendo usado por outro usuário";
        }
    }
    
    // Verificar se CPF já existe
    if (empty($erros)) {
        $sql_check_cpf = "SELECT id_usuario FROM usuario WHERE cpf = ?";
        $stmt_check_cpf = $pdo->prepare($sql_check_cpf);
        $stmt_check_cpf->execute([$cpf]);
        
        if ($stmt_check_cpf->rowCount() > 0) {
            $erros[] = "Este CPF já está sendo usado por outro usuário";
        }
    }
    
    // Se há erros, retornar
    if (!empty($erros)) {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => implode(', ', $erros)
        ]);
        exit;
    }
    
    // Hash da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    
    // Inserir usuário no banco
    $sql_insert = "INSERT INTO usuario (tipo_usuario, nome, email, telefone, data_nascimento, cpf, senha, data_cadastro, ativo) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 1)";
    
    $stmt_insert = $pdo->prepare($sql_insert);
    $success = $stmt_insert->execute([
        $tipo_usuario,
        $nome,
        $email,
        $telefone,
        $data_nascimento,
        $cpf,
        $senha_hash
    ]);
    
    if (!$success) {
        throw new Exception('Erro ao cadastrar usuário. Tente novamente.');
    }
    
    // Obter ID do usuário recém-criado
    $novo_usuario_id = $pdo->lastInsertId();
    
    if (!$novo_usuario_id) {
        throw new Exception('Erro ao obter ID do usuário cadastrado');
    }
    
    error_log("Usuário cadastrado com ID: $novo_usuario_id");
    
    // **FAZER LOGIN AUTOMÁTICO** - Regenerar ID da sessão por segurança
    session_regenerate_id(true);
    
    $_SESSION['loggedin'] = true;
    $_SESSION['id_usuario'] = $novo_usuario_id;
    $_SESSION['nome'] = $nome;
    $_SESSION['email'] = $email;
    $_SESSION['tipo_usuario'] = $tipo_usuario;
    $_SESSION['telefone'] = $telefone;
    $_SESSION['cpf'] = $cpf;
    $_SESSION['data_nascimento'] = $data_nascimento;
    
    // Debug: verificar se a sessão foi criada
    error_log("Sessão criada após cadastro:");
    error_log("Session ID: " . session_id());
    error_log("loggedin: " . ($_SESSION['loggedin'] ? 'true' : 'false'));
    error_log("id_usuario: " . $_SESSION['id_usuario']);
    error_log("nome: " . $_SESSION['nome']);
    error_log("tipo_usuario: " . $_SESSION['tipo_usuario']);
    
    // Forçar gravação da sessão
    session_write_close();
    
    // Se é cliente, criar registro na tabela de fidelidade
    if ($tipo_usuario === 'Cliente') {
        try {
            // Verificar se existe configuração de fidelidade ativa
            $sql_config = "SELECT id_config FROM config_fid WHERE ativo = 1 LIMIT 1";
            $stmt_config = $pdo->prepare($sql_config);
            $stmt_config->execute();
            $config = $stmt_config->fetch(PDO::FETCH_ASSOC);
            
            if ($config) {
                // Criar registro de fidelidade para o novo cliente
                $sql_fidelidade = "INSERT INTO fidelidade (usuario_id, config_id, pontos_atuais, pontos_acumulados, pontos_resgatados, data_criacao) 
                                   VALUES (?, ?, 0, 0, 0, NOW())";
                $stmt_fidelidade = $pdo->prepare($sql_fidelidade);
                $stmt_fidelidade->execute([$novo_usuario_id, $config['id_config']]);
                error_log("Registro de fidelidade criado");
            }
        } catch (Exception $e) {
            error_log("Erro ao criar registro de fidelidade: " . $e->getMessage());
        }
    }
    
    // Determinar página de redirecionamento baseada no tipo de usuário
    $redirect = '';
    switch ($tipo_usuario) {
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
            $redirect = '../HTML/sistema_cliente.php';
    }
    
    error_log("Redirecionamento para: $redirect");
    error_log("=== FIM CADASTRO SUCESSO ===");
    
    // Sucesso - retornar resposta com redirecionamento automático
    echo json_encode([
        'status' => 'sucesso',
        'mensagem' => 'Cadastro realizado com sucesso! Você será redirecionado para seu dashboard.',
        'redirect' => $redirect,
        'auto_login' => true,
        'session_id' => session_id(),
        'debug_session' => [
            'loggedin' => true,
            'id_usuario' => $novo_usuario_id,
            'nome' => $nome,
            'tipo_usuario' => $tipo_usuario
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Erro PDO no cadastro: " . $e->getMessage());
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro de banco de dados. Tente novamente mais tarde.'
    ]);
    
} catch (Exception $e) {
    error_log("Erro geral no cadastro: " . $e->getMessage());
    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage()
    ]);
}
?>