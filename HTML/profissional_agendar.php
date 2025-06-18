<?php
// Inicia a sessão
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Verifica se o usuário é do tipo correto (profissional)
if ($_SESSION['tipo_usuario'] !== 'Profissional') {
    header("Location: acesso_negado.php");
    exit;
}



// Atualiza o tempo da última atividade
$_SESSION['last_activity'] = time();

// Conexão com o banco de dados
include_once('../Conexao/conexao.php');

$id_profissional = $_SESSION['id_usuario'];
$mensagem = '';
$tipo_mensagem = '';

// Obter data selecionada se for informada via GET
$data_selecionada = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');
$hora_selecionada = isset($_GET['hora']) ? $_GET['hora'] : '';

// Validar formato da data
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_selecionada)) {
    $data_selecionada = date('Y-m-d');
}

// Buscar horários do profissional
$horarios_profissional = [
    'hora_inicio' => '09:00',
    'hora_fim' => '18:00',
    'intervalo_min' => 30,
    'intervalo_inicio' => '12:00',
    'intervalo_fim' => '13:00',
    'dias_semana' => '1,2,3,4,5'  // Segunda a Sexta
];

try {
    // Buscar configuração da agenda do profissional
    $sql_agenda = "SELECT * FROM profissional_agenda WHERE profissional_id = ?";
    $stmt_agenda = $pdo->prepare($sql_agenda);
    $stmt_agenda->execute([$id_profissional]);
    $config_agenda = $stmt_agenda->fetch(PDO::FETCH_ASSOC);
    
    if ($config_agenda) {
        // Mapear dias da semana
        $dias_semana_map = [
            0 => 'domingo',
            1 => 'segunda', 
            2 => 'terca',
            3 => 'quarta',
            4 => 'quinta',
            5 => 'sexta',
            6 => 'sabado'
        ];
        
        // Verificar qual dia da semana é a data selecionada
        $dia_semana_selecionado = date('w', strtotime($data_selecionada));
        $dia_campo = $dias_semana_map[$dia_semana_selecionado];
        
        // Se o profissional trabalha neste dia, usar os horários da agenda
        if ($config_agenda["{$dia_campo}_trabalha"]) {
            $horarios_profissional = [
                'hora_inicio' => $config_agenda["{$dia_campo}_inicio"] ?: '09:00',
                'hora_fim' => $config_agenda["{$dia_campo}_fim"] ?: '18:00',
                'intervalo_min' => $config_agenda['intervalo_agendamentos'] ?: 30,
                'intervalo_inicio' => $config_agenda["{$dia_campo}_intervalo_inicio"] ?: '12:00',
                'intervalo_fim' => $config_agenda["{$dia_campo}_intervalo_fim"] ?: '13:00',
                'dias_semana' => '0,1,2,3,4,5,6' // Todos os dias para verificação
            ];
        } else {
            // Se não trabalha neste dia, não há horários disponíveis
            $horarios_profissional = [
                'hora_inicio' => '00:00',
                'hora_fim' => '00:00',
                'intervalo_min' => 30,
                'intervalo_inicio' => '12:00',
                'intervalo_fim' => '13:00',
                'dias_semana' => ''
            ];
        }
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar agenda do profissional: " . $e->getMessage());
}

// Converter string de dias da semana para array para validação
$dias_semana_array = explode(',', $horarios_profissional['dias_semana']);

// Verificar se o dia selecionado é um dia de trabalho do profissional
$dia_semana_selecionado = date('w', strtotime($data_selecionada));
$e_dia_trabalho = true;

// Se temos configuração da agenda, verificar se trabalha neste dia
if ($config_agenda) {
    $dia_campo = $dias_semana_map[$dia_semana_selecionado];
    $e_dia_trabalho = $config_agenda["{$dia_campo}_trabalha"];
}

// Verificar se há agendamentos existentes no dia selecionado
$sql_agendamentos = "SELECT a.*, s.nome as nome_servico, s.duracao, c.nome as nome_cliente
                     FROM agendamentos a
                     JOIN servicos s ON a.servico_id = s.id_servico
                     JOIN usuario c ON a.cliente_id = c.id_usuario
                     WHERE a.profissional_id = ? AND a.data_agendamento = ?
                     AND a.status != 'cancelado'
                     ORDER BY a.hora_agendamento ASC";

$stmt_agendamentos = $pdo->prepare($sql_agendamentos);
$stmt_agendamentos->execute([$id_profissional, $data_selecionada]);
$agendamentos_existentes = $stmt_agendamentos->fetchAll(PDO::FETCH_ASSOC);

// Obter lista de clientes para o dropdown de seleção
$sql_clientes = "SELECT id_usuario, nome, telefone, email FROM usuario WHERE tipo_usuario = 'Cliente' AND ativo = 1 ORDER BY nome";
$stmt_clientes = $pdo->prepare($sql_clientes);
$stmt_clientes->execute();
$clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

// Obter lista de serviços para o dropdown de seleção
$sql_servicos = "SELECT id_servico, nome, valor, duracao, categoria FROM servicos WHERE ativo = 1 ORDER BY categoria, nome";
$stmt_servicos = $pdo->prepare($sql_servicos);
$stmt_servicos->execute();
$servicos = $stmt_servicos->fetchAll(PDO::FETCH_ASSOC);

// Agrupar serviços por categoria para exibição organizada
$servicos_por_categoria = [];
foreach ($servicos as $servico) {
    $categoria = $servico['categoria'];
    if (!isset($servicos_por_categoria[$categoria])) {
        $servicos_por_categoria[$categoria] = [];
    }
    $servicos_por_categoria[$categoria][] = $servico;
}

// Processar o formulário quando for enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar dados do formulário
    $id_cliente = filter_input(INPUT_POST, 'id_cliente', FILTER_SANITIZE_NUMBER_INT);
    $id_servico = filter_input(INPUT_POST, 'id_servico', FILTER_SANITIZE_NUMBER_INT);
    $data_agendamento = filter_input(INPUT_POST, 'data_agendamento', FILTER_SANITIZE_STRING);
    $hora_agendamento = filter_input(INPUT_POST, 'hora_agendamento', FILTER_SANITIZE_STRING);
    $observacoes = filter_input(INPUT_POST, 'observacoes', FILTER_SANITIZE_STRING);
    
    $erros = [];

    if (empty($id_cliente)) {
        $erros[] = "Por favor, selecione um cliente.";
    }
    
    if (empty($id_servico)) {
        $erros[] = "Por favor, selecione um serviço.";
    }
    
    if (empty($data_agendamento) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_agendamento)) {
        $erros[] = "Por favor, informe uma data válida.";
    }
    
    if (empty($hora_agendamento) || !preg_match("/^\d{2}:\d{2}$/", $hora_agendamento)) {
        $erros[] = "Por favor, informe um horário válido.";
    }
    
    // Verificar se a data não é anterior ao dia atual
    if (strtotime($data_agendamento) < strtotime(date('Y-m-d'))) {
        $erros[] = "Não é possível agendar para datas passadas.";
    }
    
    // Verificar se o dia selecionado é um dia de trabalho do profissional
    $dia_semana_agendamento = date('w', strtotime($data_agendamento));
    if (!in_array($dia_semana_agendamento, $dias_semana_array)) {
        $erros[] = "Você não trabalha no dia selecionado. Por favor, escolha outro dia.";
    }

    // Se não houver erros básicos, obter informações do serviço para validações avançadas
    if (empty($erros)) {
        try {
            // Obter duração e valor do serviço
            $sql_servico = "SELECT duracao, valor FROM servicos WHERE id_servico = ?";
            $stmt_servico = $pdo->prepare($sql_servico);
            $stmt_servico->execute([$id_servico]);
            $servico = $stmt_servico->fetch(PDO::FETCH_ASSOC);
            
            if (!$servico) {
                $erros[] = "Serviço não encontrado.";
            } else {
                $duracao_minutos = $servico['duracao'];
                $valor_servico = $servico['valor'];
                
                // Validar horário de funcionamento
                $hora_inicio_profissional = strtotime($horarios_profissional['hora_inicio']);
                $hora_fim_profissional = strtotime($horarios_profissional['hora_fim']);
                $hora_agendamento_time = strtotime($hora_agendamento);

                if ($hora_agendamento_time < $hora_inicio_profissional || $hora_agendamento_time > $hora_fim_profissional) {
                    $erros[] = "Horário fora do período de atendimento (" . $horarios_profissional['hora_inicio'] . " às " . $horarios_profissional['hora_fim'] . ").";
                }

                // Verificar se o horário não está no intervalo de almoço
                if (isset($horarios_profissional['intervalo_inicio']) && isset($horarios_profissional['intervalo_fim'])) {
                    $intervalo_inicio = strtotime($horarios_profissional['intervalo_inicio']);
                    $intervalo_fim = strtotime($horarios_profissional['intervalo_fim']);
                    
                    // Verificar se o agendamento começa durante o intervalo
                    if ($hora_agendamento_time >= $intervalo_inicio && $hora_agendamento_time < $intervalo_fim) {
                        $erros[] = "Não é possível agendar durante o intervalo de almoço (" . 
                                  date('H:i', $intervalo_inicio) . " às " . 
                                  date('H:i', $intervalo_fim) . ").";
                    }
                    
                    // Verificar se o serviço vai invadir o horário de intervalo
                    $hora_fim_servico = $hora_agendamento_time + ($duracao_minutos * 60);
                    
                    // Se o agendamento começa antes do intervalo mas termina durante ou depois do início do intervalo
                    if ($hora_agendamento_time < $intervalo_inicio && $hora_fim_servico > $intervalo_inicio) {
                        $erros[] = "O serviço não pode ser agendado neste horário pois invadirá o intervalo de almoço (" . 
                                  date('H:i', $intervalo_inicio) . " às " . 
                                  date('H:i', $intervalo_fim) . "). Duração do serviço: {$duracao_minutos} minutos.";
                    }
                    
                    // Verificar se o agendamento começa durante o intervalo (já verificado acima, mas mantendo para clareza)
                    if ($hora_agendamento_time >= $intervalo_inicio && $hora_agendamento_time < $intervalo_fim) {
                        // Já tratado acima
                    }
                    
                    // Verificar se o serviço excede o horário de trabalho do profissional
                    if ($hora_fim_servico > $hora_fim_profissional) {
                        $erros[] = "O serviço não pode ser agendado neste horário pois excede o horário de trabalho do profissional (" . 
                                  date('H:i', $hora_fim_profissional) . "). Duração do serviço: {$duracao_minutos} minutos.";
                    }
                }
            }
        } catch (PDOException $e) {
            $erros[] = "Erro ao validar serviço: " . $e->getMessage();
        }
    }

    // Se ainda não houver erros, continuar com o agendamento
    if (empty($erros)) {
        try {
            // Verificar se o horário está disponível
            $hora_fim_agendamento = date('H:i:s', strtotime($hora_agendamento) + ($duracao_minutos * 60));
            
            // Determinar o número de intervalos necessários para o serviço
            $intervalos_necessarios = ceil($duracao_minutos / $horarios_profissional['intervalo_min']);
            
            // Verificar conflito com outros agendamentos de forma mais abrangente
            $sql_verificar = "SELECT COUNT(*) FROM agendamentos 
                            WHERE profissional_id = ? 
                            AND data_agendamento = ? 
                            AND status != 'cancelado'
                            AND (
                                (hora_agendamento < ? AND ADDTIME(hora_agendamento, SEC_TO_TIME((SELECT duracao FROM servicos WHERE id_servico = agendamentos.servico_id) * 60)) > ?) 
                                OR 
                                (hora_agendamento >= ? AND hora_agendamento < ?)
                            )";
                            
            $stmt_verificar = $pdo->prepare($sql_verificar);
            $stmt_verificar->execute([
                $id_profissional,
                $data_agendamento,
                $hora_fim_agendamento,
                $hora_agendamento,
                $hora_agendamento,
                $hora_fim_agendamento
            ]);
            
            $conflitos = $stmt_verificar->fetchColumn();
            
            if ($conflitos > 0) {
                $mensagem = "O horário selecionado não está disponível. O serviço selecionado tem duração de {$duracao_minutos} minutos e conflita com outro(s) agendamento(s).";
                $tipo_mensagem = "danger";
            } else {
                // Verificar conflito com horários bloqueados
                $sql_bloqueios = "SELECT COUNT(*) FROM dias_bloqueados 
                                WHERE profissional_id = ? 
                                AND data_bloqueio = ?";
                                
                $stmt_bloqueios = $pdo->prepare($sql_bloqueios);
                $stmt_bloqueios->execute([
                    $id_profissional,
                    $data_agendamento
                ]);
                
                $bloqueios = $stmt_bloqueios->fetchColumn();
                
                if ($bloqueios > 0) {
                    $mensagem = "O horário selecionado está bloqueado na sua agenda. Por favor, escolha outro horário.";
                    $tipo_mensagem = "danger";
                } else {
                    // Criar o agendamento
                    $sql_inserir = "INSERT INTO agendamentos (cliente_id, servico_id, profissional_id, data_agendamento, hora_agendamento, observacoes, status, valor, data_criacao) 
                               VALUES (?, ?, ?, ?, ?, ?, 'agendado', ?, NOW())";
                
                    $stmt_inserir = $pdo->prepare($sql_inserir);
                    $resultado = $stmt_inserir->execute([
                        $id_cliente,
                        $id_servico,
                        $id_profissional,
                        $data_agendamento,
                        $hora_agendamento,
                        $observacoes,
                        $valor_servico
                    ]);
                    
                    if ($resultado) {
                        $id_agendamento = $pdo->lastInsertId();
                        
                        // Registrar histórico com tratamento para tabela inexistente
                        try {
                            // Verificar se a tabela historico_agendamentos existe
                            $table_check = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'historico_agendamentos'");
                            $table_exists = $table_check && $table_check->fetchColumn();
                            
                            if ($table_exists) {
                                // Se a tabela existir, insere o histórico
                                $sql_historico = "INSERT INTO historico_agendamentos 
                                            (id_agendamento, id_usuario, acao, descricao, data_acao) 
                                            VALUES (?, ?, 'criacao', 'Agendamento criado pelo profissional', NOW())";
                                $stmt_historico = $pdo->prepare($sql_historico);
                                $stmt_historico->execute([
                                    $id_agendamento, 
                                    $id_profissional
                                ]);
                            } else {
                                // Tabela não existe - poderíamos criar a tabela aqui ou apenas logar o erro
                                error_log("Tabela historico_agendamentos não existe. O histórico não será registrado.");
                            }
                        } catch (PDOException $e) {
                            // Erro no histórico não deve impedir a criação do agendamento
                            error_log("Erro ao registrar histórico: " . $e->getMessage());
                        }
                        
                        // Enviar notificação ao cliente (com tratamento de erro)
                        try {
                            // Verificar se a tabela de notificações existe
                            $table_check = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'notificacoes'");
                            $notif_table_exists = $table_check && $table_check->fetchColumn();
                            
                            if ($notif_table_exists) {
                                $sql_notificacao = "INSERT INTO notificacoes 
                                                   (id_usuario, tipo, titulo, mensagem, data_envio) 
                                                   VALUES (?, 'agendamento', 'Novo agendamento', 'Você tem um novo agendamento para o dia " . date('d/m/Y', strtotime($data_agendamento)) . " às " . date('H:i', strtotime($hora_agendamento)) . "', NOW())";
                                $stmt_notificacao = $pdo->prepare($sql_notificacao);
                                $stmt_notificacao->execute([$id_cliente]);
                            }
                        } catch (PDOException $e) {
                            // Erro na notificação não deve impedir a criação do agendamento
                            error_log("Erro ao enviar notificação: " . $e->getMessage());
                        }
                        
                        $mensagem = "Agendamento criado com sucesso!";
                        $tipo_mensagem = "success";
                        
                        // Redirecionar para a página de agenda
                        header("Location: agenda_profissional.php?data=" . $data_agendamento);
                        exit;
                    } else {
                        $mensagem = "Erro ao criar agendamento.";
                        $tipo_mensagem = "danger";
                    }
                }
            }
        } catch (PDOException $e) {
            $mensagem = "Erro ao processar agendamento: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    } else {
        $mensagem = implode("<br>", $erros);
        $tipo_mensagem = "danger";
    }
}

// Gerar opções de horários disponíveis
function gerarHorariosDisponiveis($horarios_profissional, $agendamentos_existentes, $data_selecionada) {
    $hora_inicio_str = $horarios_profissional['hora_inicio'];
    $hora_fim_str = $horarios_profissional['hora_fim'];
    
    // Se não há horários definidos, retornar array vazio
    if ($hora_inicio_str === '00:00' && $hora_fim_str === '00:00') {
        return [];
    }
    
    $hora_inicio = strtotime($hora_inicio_str);
    $hora_fim = strtotime($hora_fim_str);
    $intervalo_min = intval($horarios_profissional['intervalo_min']);
    
    // Obter horários de intervalo de almoço
    $intervalo_inicio = isset($horarios_profissional['intervalo_inicio']) ? strtotime($horarios_profissional['intervalo_inicio']) : null;
    $intervalo_fim = isset($horarios_profissional['intervalo_fim']) ? strtotime($horarios_profissional['intervalo_fim']) : null;
    
    $horarios = [];
    $horarios_ocupados = [];
    
    // Mapear horários ocupados por agendamentos
    foreach ($agendamentos_existentes as $agendamento) {
        $inicio = strtotime($agendamento['hora_agendamento']);
        $duracao = intval($agendamento['duracao']);
        $fim = $inicio + ($duracao * 60);
        
        // Marcar todos os intervalos dentro da duração do agendamento como ocupados
        for ($i = $inicio; $i < $fim; $i += $intervalo_min * 60) {
            $horarios_ocupados[date('H:i', $i)] = true;
        }
    }
    
    // Marcar horários de intervalo de almoço como ocupados
    if ($intervalo_inicio && $intervalo_fim) {
        for ($i = $intervalo_inicio; $i < $intervalo_fim; $i += $intervalo_min * 60) {
            $horarios_ocupados[date('H:i', $i)] = true;
        }
    }
    
    // Gerar opções de horário em intervalos definidos
    for ($hora = $hora_inicio; $hora < $hora_fim; $hora += $intervalo_min * 60) {
        $horario = date('H:i', $hora);
        
        // Verificar se o horário está livre (não ocupado por agendamentos nem no intervalo de almoço)
        if (!isset($horarios_ocupados[$horario])) {
            $horarios[] = $horario;
        }
    }
    
    return $horarios;
}

$horarios_disponiveis = gerarHorariosDisponiveis($horarios_profissional, $agendamentos_existentes, $data_selecionada);

// Títulos dos dias da semana
$dias_semana_nomes = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Agendamento - Barbearia Mosanberk</title>
    <link rel="icon" href="../Imagens/logotipo.png" type="image/png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- CSS Personalizado -->
    <link href="../CSS/sistema_profissional.css" rel="stylesheet">
    <style>
        .categoria-header {
            background-color: #f8f9fa;
            font-weight: bold;
            padding: 8px;
            margin-top: 10px;
        }
        
        .servico-option {
            padding-left: 15px;
        }
        
        .cliente-info {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .horario-btn {
            width: 80px;
            margin-bottom: 10px;
        }
        
        .agendamento-existente {
            background-color: #f8d7da;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
        }
        
        .status-agendado {
            color: #ffc107;
        }
        
        .status-confirmado {
            color: #28a745;
        }
        
        .status-concluido {
            color: #6c757d;
        }
        
        .status-cancelado {
            color: #dc3545;
        }
        
        .cliente-search-results {
            max-height: 200px;
            overflow-y: auto;
            position: absolute;
            width: 100%;
            z-index: 1000;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .cliente-search-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        
        .cliente-search-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

<!-- Barra de navegação -->
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="sistema_profissional.php">
            <img src="../Imagens/logotipo.png" alt="Logo" width="40" height="40">
            <span class="ms-2 fw-bold">Barbearia Mosanberk</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link" href="sistema_profissional.php">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="profissional_agenda.php">
                        <i class="bi bi-calendar-check me-1"></i> Minha Agenda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profissional_clientes.php">
                        <i class="bi bi-people me-1"></i> Clientes
                    </a>
                </li>
                <div class="nav-divider d-none d-lg-block"></div>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i> 
                        <?php echo htmlspecialchars($_SESSION['nome']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="profissional_perfil.php"><i class="bi bi-person me-2"></i>Meu Perfil</a></li>
                        <li><a class="dropdown-item" href="configurar_horarios.php"><i class="bi bi-gear me-2"></i>Configurações</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main>
    <div class="container py-4">
        <!-- Breadcrumb e título -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="sistema_profissional.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="profissional_agenda.php">Minha Agenda</a></li>
                <li class="breadcrumb-item active" aria-current="page">Criar Agendamento</li>
            </ol>
        </nav>
        
        <h2 class="mb-4">Criar Novo Agendamento</h2>
        
        <?php if (!empty($mensagem)): ?>
        <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Informações do Agendamento</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="" id="formAgendamento">
                            <div class="row mb-4">
                                <!-- Data de agendamento -->
                                <div class="col-md-6 mb-3">
                                    <label for="data_agendamento" class="form-label">Data</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                        <input type="date" class="form-control" id="data_agendamento" name="data_agendamento" 
                                               min="<?php echo date('Y-m-d'); ?>" 
                                               value="<?php echo $data_selecionada; ?>" required>
                                    </div>
                                    <?php if (!$e_dia_trabalho): ?>
                                        <div class="form-text text-danger">
                                            <i class="bi bi-exclamation-triangle"></i> 
                                            Aviso: Você não trabalha no dia selecionado (<?php echo $dias_semana_nomes[$dia_semana_selecionado]; ?>)
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($e_dia_trabalho && $config_agenda): ?>
                                        <div class="form-text text-success">
                                            <i class="bi bi-clock"></i> 
                                            Horário de trabalho: <?php echo $horarios_profissional['hora_inicio']; ?> às <?php echo $horarios_profissional['hora_fim']; ?>
                                            (Intervalos de <?php echo $horarios_profissional['intervalo_min']; ?> min)
                                            <?php if (isset($horarios_profissional['intervalo_inicio']) && isset($horarios_profissional['intervalo_fim'])): ?>
                                                <br><i class="bi bi-cup-hot"></i> 
                                                Intervalo: <?php echo substr($horarios_profissional['intervalo_inicio'], 0, 5); ?> às <?php echo substr($horarios_profissional['intervalo_fim'], 0, 5); ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Hora de agendamento -->
                                <div class="col-md-6 mb-3">
                                    <label for="hora_agendamento" class="form-label">Horário</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                        <select class="form-select" id="hora_agendamento" name="hora_agendamento" required>
                                            <option value="">Selecione um horário</option>
                                            <?php if ($e_dia_trabalho && !empty($horarios_disponiveis)): ?>
                                                <?php foreach ($horarios_disponiveis as $horario): ?>
                                                    <option value="<?php echo $horario; ?>" <?php echo ($horario == $hora_selecionada) ? 'selected' : ''; ?>>
                                                        <?php echo $horario; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php elseif (!$e_dia_trabalho): ?>
                                                <option value="" disabled>Não há horários disponíveis neste dia</option>
                                            <?php elseif (empty($horarios_disponiveis)): ?>
                                                <option value="" disabled>Todos os horários estão ocupados</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    
                                    <?php if ($e_dia_trabalho && !empty($horarios_disponiveis)): ?>
                                        <div class="form-text text-info">
                                            <i class="bi bi-info-circle"></i> 
                                            <?php echo count($horarios_disponiveis); ?> horário(s) disponível(is) para este dia
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <!-- Seleção de cliente -->
                                <div class="col-md-12 mb-3">
                                    <label for="id_cliente" class="form-label">Cliente</label>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" class="form-control" id="busca_cliente" placeholder="Buscar cliente por nome, telefone ou email" autocomplete="off">
                                    </div>
                                    <div id="clienteSearchResults" class="cliente-search-results d-none">
                                        <!-- Resultados da busca serão inseridos aqui via JavaScript -->
                                    </div>
                                    <select class="form-select d-none" id="id_cliente" name="id_cliente" required>
                                        <option value="">Selecione um cliente</option>
                                        <?php foreach ($clientes as $cliente): ?>
                                            <option value="<?php echo $cliente['id_usuario']; ?>" data-nome="<?php echo htmlspecialchars($cliente['nome']); ?>" data-telefone="<?php echo htmlspecialchars($cliente['telefone']); ?>" data-email="<?php echo htmlspecialchars($cliente['email']); ?>">
                                                <?php echo htmlspecialchars($cliente['nome']); ?> - <?php echo htmlspecialchars($cliente['telefone']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="cliente_selecionado" class="mt-2">
                                        <!-- Informações do cliente selecionado serão exibidas aqui -->
                                    </div>
                                </div>

                                <!-- Seleção de serviço -->
                                <div class="col-md-12 mb-3">
                                    <label for="id_servico" class="form-label">Serviço</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-scissors"></i></span>
                                        <select class="form-select" id="id_servico" name="id_servico" required>
                                            <option value="">Selecione um serviço</option>
                                            <?php foreach ($servicos_por_categoria as $categoria => $servicos_cat): ?>
                                                <optgroup label="<?php echo htmlspecialchars($categoria); ?>">
                                                    <?php foreach ($servicos_cat as $servico): ?>
                                                        <option value="<?php echo $servico['id_servico']; ?>" data-duracao="<?php echo $servico['duracao']; ?>" data-valor="<?php echo $servico['valor']; ?>">
                                                            <?php echo htmlspecialchars($servico['nome']); ?> - R$ <?php echo number_format($servico['valor'], 2, ',', '.'); ?> 
                                                            (<?php echo $servico['duracao']; ?> min)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div id="servico_info" class="mt-2">
                                        <!-- Informações do serviço selecionado serão exibidas aqui -->
                                    </div>
                                </div>

                                <!-- Observações -->
                                <div class="col-md-12 mb-3">
                                    <label for="observacoes" class="form-label">Observações (opcional)</label>
                                    <textarea class="form-control" id="observacoes" name="observacoes" rows="3" placeholder="Informe observações importantes para este agendamento..."><?php echo isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : ''; ?></textarea>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="agenda_profissional.php?data=<?php echo $data_selecionada; ?>" class="btn btn-outline-secondary me-md-2">
                                    <i class="bi bi-arrow-left"></i> Voltar
                                </a>
                                <button type="submit" class="btn btn-warning">
                                    <i class="bi bi-save"></i> Criar Agendamento
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mt-4 mt-lg-0">
                <!-- Card com agendamentos do dia -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-calendar-date me-1"></i>
                            Agendamentos do Dia
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <span class="badge bg-light text-dark mb-2">
                                <i class="bi bi-calendar3"></i> 
                                <?php echo date('d/m/Y', strtotime($data_selecionada)); ?> 
                                (<?php echo $dias_semana_nomes[$dia_semana_selecionado]; ?>)
                            </span>
                            <p class="mb-0">
                                <?php echo count($agendamentos_existentes); ?> agendamento(s) neste dia
                            </p>
                        </div>

                        <div class="timeline">
                            <?php if (empty($agendamentos_existentes)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-calendar2-plus fs-4 mb-2"></i>
                                    <p>Não há agendamentos para este dia.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($agendamentos_existentes as $agendamento): ?>
                                    <div class="agendamento-existente">
                                        <small class="d-flex justify-content-between">
                                            <span>
                                                <i class="bi bi-clock"></i> 
                                                <?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?> - 
                                                <?php 
                                                    $hora_fim = strtotime($agendamento['hora_agendamento']) + ($agendamento['duracao'] * 60);
                                                    echo date('H:i', $hora_fim); 
                                                ?>
                                            </span>
                                            <span class="
                                                <?php 
                                                    switch ($agendamento['status']) {
                                                        case 'agendado': echo 'status-agendado'; break;
                                                        case 'confirmado': echo 'status-confirmado'; break;
                                                        case 'concluído': echo 'status-concluido'; break;
                                                        case 'cancelado': echo 'status-cancelado'; break;
                                                    }
                                                ?>
                                            ">
                                                <?php echo ucfirst($agendamento['status']); ?>
                                            </span>
                                        </small>
                                        <strong>
                                            <?php echo htmlspecialchars($agendamento['nome_cliente']); ?>
                                        </strong>
                                        <p class="mb-0">
                                            <i class="bi bi-scissors me-1"></i> 
                                            <?php echo htmlspecialchars($agendamento['nome_servico']); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Card de dicas -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-lightbulb me-1"></i>
                            Dicas Úteis
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-1"></i>
                                Para reagendar um cliente, vá até a agenda e clique em "Editar".
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-1"></i>
                                Verifique sempre se há tempo suficiente entre agendamentos.
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-1"></i>
                                Confirme os detalhes do agendamento com o cliente.
                            </li>
                            <li>
                                <i class="bi bi-check-circle text-success me-1"></i>
                                Você também pode bloquear horários na seção "Bloquear Horários".
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Busca dinâmica de clientes
        const buscaCliente = document.getElementById('busca_cliente');
        const clienteSelect = document.getElementById('id_cliente');
        const clienteResults = document.getElementById('clienteSearchResults');
        const clienteSelecionado = document.getElementById('cliente_selecionado');
        
        buscaCliente.addEventListener('input', function() {
            const termo = this.value.toLowerCase();
            
            if (termo.length < 2) {
                clienteResults.innerHTML = '';
                clienteResults.classList.add('d-none');
                return;
            }
            
            // Filtrar clientes com base no termo de busca
            const options = Array.from(clienteSelect.options).filter(option => {
                if (option.value === '') return false;
                const nome = option.getAttribute('data-nome').toLowerCase();
                const telefone = option.getAttribute('data-telefone').toLowerCase();
                const email = option.getAttribute('data-email').toLowerCase();
                return nome.includes(termo) || telefone.includes(termo) || email.includes(termo);
            });
            
            // Exibir resultados
            clienteResults.innerHTML = '';
            if (options.length > 0) {
                options.forEach(option => {
                    const div = document.createElement('div');
                    div.classList.add('cliente-search-item');
                    div.innerHTML = `
                        <strong>${option.getAttribute('data-nome')}</strong><br>
                        <small>${option.getAttribute('data-telefone')} | ${option.getAttribute('data-email')}</small>
                    `;
                    div.dataset.id = option.value;
                    div.dataset.nome = option.getAttribute('data-nome');
                    div.dataset.telefone = option.getAttribute('data-telefone');
                    div.dataset.email = option.getAttribute('data-email');
                    
                    div.addEventListener('click', function() {
                        // Selecionar cliente no select oculto
                        clienteSelect.value = this.dataset.id;
                        
                        // Atualizar exibição
                        buscaCliente.value = this.dataset.nome;
                        clienteResults.classList.add('d-none');
                        
                        // Mostrar informações do cliente selecionado
                        clienteSelecionado.innerHTML = `
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Cliente selecionado</h6>
                                    <p class="mb-0"><strong>${this.dataset.nome}</strong></p>
                                    <p class="mb-0 small">${this.dataset.telefone}</p>
                                    <p class="mb-0 small">${this.dataset.email}</p>
                                </div>
                            </div>
                        `;
                    });
                    
                    clienteResults.appendChild(div);
                });
                clienteResults.classList.remove('d-none');
            } else {
                clienteResults.innerHTML = `
                    <div class="p-3 text-center">
                        <p class="mb-0">Nenhum cliente encontrado</p>
                    </div>
                `;
                clienteResults.classList.remove('d-none');
            }
        });
        
        // Fechar resultados da busca ao clicar fora
        document.addEventListener('click', function(e) {
            if (e.target !== buscaCliente && e.target !== clienteResults) {
                clienteResults.classList.add('d-none');
            }
        });
        
        // Mostrar detalhes do serviço selecionado
        const servicoSelect = document.getElementById('id_servico');
        const servicoInfo = document.getElementById('servico_info');
        
        servicoSelect.addEventListener('change', function() {
            if (this.value) {
                const option = this.options[this.selectedIndex];
                const duracao = option.getAttribute('data-duracao');
                const valor = option.getAttribute('data-valor');
                const nome = option.textContent.split('-')[0].trim();
                
                servicoInfo.innerHTML = `
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Serviço selecionado</h6>
                            <p class="mb-0"><strong>${nome}</strong></p>
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-clock me-1"></i> Duração: ${duracao} minutos</span>
                                <span><i class="bi bi-currency-dollar me-1"></i> Valor: R$ ${parseFloat(valor).toFixed(2).replace('.', ',')}</span>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                servicoInfo.innerHTML = '';
            }
        });
        
        // Atualizar horários disponíveis ao mudar a data
        const dataInput = document.getElementById('data_agendamento');
        
        dataInput.addEventListener('change', function() {
            // Verificar se a data é válida antes de redirecionar
            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);
            const dataSelecionada = new Date(this.value);
            
            if (dataSelecionada < hoje) {
                alert('Não é possível agendar para datas passadas. Por favor, selecione a data atual ou uma data futura.');
                this.value = new Date().toISOString().split('T')[0];
                return;
            }
            
            // Redirecionar para a mesma página com a nova data
            window.location.href = `profissional_agendar.php?data=${this.value}`;
        });
        
        // Verificação de data passada ao selecionar uma data
        dataInput.addEventListener('change', function() {
            // Obter a data atual (sem hora)
            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);
            
            // Converter a data selecionada para objeto Date
            const dataSelecionada = new Date(this.value);
            
            // Verificar se a data é anterior à data atual
            if (dataSelecionada < hoje) {
                alert('Não é possível agendar para datas passadas. Por favor, selecione a data atual ou uma data futura.');
                // Resetar para a data atual
                this.value = new Date().toISOString().split('T')[0];
                return;
            }
            
            // Se a data for válida, redirecionar para atualizar os horários disponíveis
            window.location.href = `profissional_agendar.php?data=${this.value}`;
        });
        
        // Validação do formulário
        const formAgendamento = document.getElementById('formAgendamento');
        
        formAgendamento.addEventListener('submit', function(e) {
            const clienteValue = clienteSelect.value;
            const servicoValue = servicoSelect.value;
            const dataValue = dataInput.value;
            const horaValue = document.getElementById('hora_agendamento').value;
            
            let hasErrors = false;
            let errorMessage = 'Por favor, corrija os seguintes erros:\n';
            
            if (!clienteValue) {
                errorMessage += '- Selecione um cliente\n';
                hasErrors = true;
            }
            
            if (!servicoValue) {
                errorMessage += '- Selecione um serviço\n';
                hasErrors = true;
            }
            
            if (!dataValue) {
                errorMessage += '- Selecione uma data\n';
                hasErrors = true;
            } else {
                // Verificar novamente se a data não é passada
                const hoje = new Date();
                hoje.setHours(0, 0, 0, 0);
                const dataSelecionada = new Date(dataValue);
                
                if (dataSelecionada < hoje) {
                    errorMessage += '- Não é possível agendar para datas passadas\n';
                    hasErrors = true;
                }
            }
            
            if (!horaValue) {
                errorMessage += '- Selecione um horário\n';
                hasErrors = true;
            }
            
            if (hasErrors) {
                e.preventDefault();
                alert(errorMessage);
            }
        });
    });
</script>
</body>
</html>
