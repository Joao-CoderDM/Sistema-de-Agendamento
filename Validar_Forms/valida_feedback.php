<?php
session_start();
include_once('../Conexao/conexao.php');

// Ativar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar se é uma requisição AJAX de forma mais robusta
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$has_ajax_header = isset($_SERVER['HTTP_X_REQUESTED_WITH']);

// Se for AJAX, definir header JSON ANTES de qualquer output
if ($is_ajax || $has_ajax_header) {
    header('Content-Type: application/json; charset=utf-8');
    // Limpar qualquer output buffer que possa existir
    if (ob_get_level()) {
        ob_clean();
    }
}

// Inicializar variáveis
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debug: mostrar dados recebidos
        error_log("=== INÍCIO PROCESSAMENTO FEEDBACK ===");
        error_log("Dados POST recebidos: " . print_r($_POST, true));
        error_log("É AJAX: " . ($is_ajax ? 'sim' : 'não'));
        error_log("Tem header AJAX: " . ($has_ajax_header ? 'sim' : 'não'));
        
        // Sanitizar e validar dados de entrada
        $nome = filter_var(trim($_POST['nome'] ?? ''), FILTER_SANITIZE_STRING);
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $mensagem_feedback = trim($_POST['mensagem'] ?? '');
        $avaliacao = (int)($_POST['avaliacao'] ?? 5);
        $agendamento_id = !empty($_POST['agendamento_id']) ? (int)$_POST['agendamento_id'] : null;
        $usuario_id = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : null;
        
        // Debug: mostrar valores processados
        error_log("Valores processados - Nome: $nome, Email: $email, Usuario_id: $usuario_id");
        
        // Array para armazenar erros
        $erros = [];
        
        // Validações
        if (empty($nome)) {
            $erros[] = "Nome é obrigatório";
        } elseif (strlen($nome) < 2) {
            $erros[] = "Nome deve ter pelo menos 2 caracteres";
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "Email válido é obrigatório";
        }
        
        if (empty($mensagem_feedback)) {
            $erros[] = "Mensagem é obrigatória";
        } elseif (strlen($mensagem_feedback) < 10) {
            $erros[] = "Mensagem deve ter pelo menos 10 caracteres";
        } elseif (strlen($mensagem_feedback) > 1000) {
            $erros[] = "Mensagem deve ter no máximo 1000 caracteres";
        }
        
        if ($avaliacao < 1 || $avaliacao > 5) {
            $erros[] = "Avaliação deve ser entre 1 e 5 estrelas";
        }
        
        // Se há erros, retornar resposta apropriada
        if (!empty($erros)) {
            error_log("Erros encontrados: " . implode(', ', $erros));
            
            if ($is_ajax || $has_ajax_header) {
                echo json_encode([
                    'status' => 'erro',
                    'mensagem' => implode(', ', $erros)
                ]);
                exit;
            } else {
                $_SESSION['erro_feedback'] = implode(', ', $erros);
                $_SESSION['form_data'] = $_POST;
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../HTML/feedback.php'));
                exit;
            }
        }
        
        // Verificar se o feedback já existe para evitar duplicatas
        if ($usuario_id && $agendamento_id) {
            $sql_check = "SELECT id_feedback FROM feedback WHERE usuario_id = ? AND agendamento_id = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$usuario_id, $agendamento_id]);
            
            if ($stmt_check->rowCount() > 0) {
                error_log("Feedback duplicado detectado para usuario_id: $usuario_id, agendamento_id: $agendamento_id");
                
                if ($is_ajax || $has_ajax_header) {
                    echo json_encode([
                        'status' => 'erro',
                        'mensagem' => 'Você já avaliou este agendamento.'
                    ]);
                    exit;
                } else {
                    $_SESSION['erro_feedback'] = "Você já avaliou este agendamento.";
                    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../HTML/feedback.php'));
                    exit;
                }
            }
        }
        
        // Se não há usuário logado, tentar encontrar pelo email
        if (!$usuario_id) {
            $sql_user = "SELECT id_usuario FROM usuario WHERE email = ? AND ativo = 1";
            $stmt_user = $pdo->prepare($sql_user);
            $stmt_user->execute([$email]);
            
            if ($stmt_user->rowCount() > 0) {
                $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
                $usuario_id = $user_data['id_usuario'];
                error_log("Usuario encontrado pelo email: $usuario_id");
            }
        }
        
        // Preparar query de inserção
        $sql = "INSERT INTO feedback (nome, email, mensagem, avaliacao, usuario_id, agendamento_id, data_criacao) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $nome,
            $email, 
            $mensagem_feedback,
            $avaliacao,
            $usuario_id,
            $agendamento_id
        ];
        
        // Debug: mostrar query e parâmetros
        error_log("SQL: $sql");
        error_log("Parâmetros: " . print_r($params, true));
        
        $stmt = $pdo->prepare($sql);
        
        if (!$stmt) {
            $errorInfo = $pdo->errorInfo();
            throw new Exception("Erro ao preparar consulta SQL: " . print_r($errorInfo, true));
        }
        
        // Executar inserção
        $success = $stmt->execute($params);
        
        if (!$success) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Erro ao executar inserção: " . print_r($errorInfo, true));
        }
        
        // Verificar se foi inserido
        $id_feedback = $pdo->lastInsertId();
        
        if (!$id_feedback) {
            throw new Exception("Feedback não foi salvo - lastInsertId retornou 0");
        }
        
        error_log("Feedback inserido com sucesso - ID: $id_feedback");
        error_log("=== SUCESSO NO PROCESSAMENTO ===");
        
        // Sucesso - retornar resposta apropriada
        if ($is_ajax || $has_ajax_header) {
            // Garantir que não há output antes do JSON
            if (ob_get_level()) {
                ob_clean();
            }
            
            echo json_encode([
                'status' => 'sucesso',
                'mensagem' => 'Obrigado pelo seu feedback! Sua avaliação foi registrada com sucesso.',
                'id_feedback' => $id_feedback,
                'redirect' => isset($_SESSION['loggedin']) && $_SESSION['loggedin'] ? '../HTML/cliente_avaliacoes.php?sucesso=1' : '../HTML/feedback.php?sucesso=1'
            ]);
            exit;
        } else {
            $_SESSION['sucesso_feedback'] = "Obrigado pelo seu feedback! Sua avaliação foi registrada com sucesso.";
            
            // Limpar dados do formulário da sessão
            unset($_SESSION['form_data']);
            
            // Redirecionar
            if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
                header('Location: ../HTML/cliente_avaliacoes.php?sucesso=1');
            } else {
                header('Location: ../HTML/feedback.php?sucesso=1');
            }
            exit;
        }
        
    } catch (PDOException $e) {
        error_log("Erro PDO no feedback: " . $e->getMessage());
        error_log("Código do erro: " . $e->getCode());
        
        if ($is_ajax || $has_ajax_header) {
            echo json_encode([
                'status' => 'erro',
                'mensagem' => 'Erro de banco de dados: ' . $e->getMessage()
            ]);
            exit;
        } else {
            $_SESSION['erro_feedback'] = "Erro de banco de dados: " . $e->getMessage();
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../HTML/feedback.php'));
            exit;
        }
        
    } catch (Exception $e) {
        error_log("Erro geral no feedback: " . $e->getMessage());
        
        if ($is_ajax || $has_ajax_header) {
            echo json_encode([
                'status' => 'erro',
                'mensagem' => 'Erro ao processar feedback: ' . $e->getMessage()
            ]);
            exit;
        } else {
            $_SESSION['erro_feedback'] = "Erro ao processar feedback: " . $e->getMessage();
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../HTML/feedback.php'));
            exit;
        }
    }
} else {
    if ($is_ajax || $has_ajax_header) {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Método de requisição não permitido'
        ]);
        exit;
    } else {
        $_SESSION['erro_feedback'] = "Método de requisição não permitido";
        header('Location: ../HTML/feedback.php');
        exit;
    }
}
?>