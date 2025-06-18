<?php
session_start();

// Verifica se o usuário está logado e é profissional
if(!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'Profissional'){
    header('Location: login.php');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
include_once('../Conexao/conexao.php');

$profissional_id = $_SESSION['id_usuario'];
$mensagem = '';
$tipo_mensagem = '';

// Data selecionada (padrão: hoje)
$data_selecionada = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');

// Função para gerenciar pontos de fidelidade
function gerenciarPontosFidelidade($pdo, $cliente_id, $valor_agendamento) {
    try {
        // Buscar configuração de fidelidade ativa
        $sql_config = "SELECT * FROM config_fid WHERE ativo = 1 LIMIT 1";
        $stmt_config = $pdo->prepare($sql_config);
        $stmt_config->execute();
        $config = $stmt_config->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            // Se não há configuração, criar uma padrão
            $sql_insert_config = "INSERT INTO config_fid (pontos_por_real, pontos_expiracao_dias, ativo) VALUES (1.00, 365, 1)";
            $pdo->exec($sql_insert_config);
            $config_id = $pdo->lastInsertId();
            $pontos_por_real = 1.00;
        } else {
            $config_id = $config['id_config'];
            $pontos_por_real = $config['pontos_por_real'];
        }
        
        // Calcular pontos ganhos
        $pontos_ganhos = floor($valor_agendamento * $pontos_por_real);
        
        // Verificar se cliente já tem registro de fidelidade
        $sql_fidelidade = "SELECT * FROM fidelidade WHERE usuario_id = ?";
        $stmt_fidelidade = $pdo->prepare($sql_fidelidade);
        $stmt_fidelidade->execute([$cliente_id]);
        $fidelidade = $stmt_fidelidade->fetch(PDO::FETCH_ASSOC);
        
        if ($fidelidade) {
            // Atualizar pontos existentes
            $novos_pontos_atuais = $fidelidade['pontos_atuais'] + $pontos_ganhos;
            $novos_pontos_acumulados = $fidelidade['pontos_acumulados'] + $pontos_ganhos;
            
            $sql_update = "UPDATE fidelidade SET 
                          pontos_atuais = ?, 
                          pontos_acumulados = ? 
                          WHERE usuario_id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$novos_pontos_atuais, $novos_pontos_acumulados, $cliente_id]);
        } else {
            // Criar novo registro de fidelidade
            $sql_insert = "INSERT INTO fidelidade (usuario_id, config_id, pontos_atuais, pontos_acumulados, pontos_resgatados) 
                          VALUES (?, ?, ?, ?, 0)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([$cliente_id, $config_id, $pontos_ganhos, $pontos_ganhos]);
        }
        
        return $pontos_ganhos;
    } catch (Exception $e) {
        error_log("Erro ao gerenciar pontos de fidelidade: " . $e->getMessage());
        return 0;
    }
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['criar_agendamento'])) {
            $cliente_id = $_POST['cliente_id'];
            $servico_id = $_POST['servico_id'];
            $data_agendamento = $_POST['data_agendamento'];
            $hora_agendamento = $_POST['hora_agendamento'];
            $observacoes = $_POST['observacoes'] ?? '';
            
            // Buscar valor do serviço
            $stmt = $pdo->prepare("SELECT valor FROM servicos WHERE id_servico = ?");
            $stmt->execute([$servico_id]);
            $valor_servico = $stmt->fetchColumn();
            
            $sql = "INSERT INTO agendamentos (cliente_id, profissional_id, servico_id, data_agendamento, hora_agendamento, valor, status, observacoes) 
                    VALUES (?, ?, ?, ?, ?, ?, 'agendado', ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$cliente_id, $profissional_id, $servico_id, $data_agendamento, $hora_agendamento, $valor_servico, $observacoes]);
            
            $mensagem = "Agendamento criado com sucesso!";
            $tipo_mensagem = "success";
        }
        
        if (isset($_POST['atualizar_status'])) {
            $id_agendamento = $_POST['id_agendamento_status'];
            $novo_status = $_POST['novo_status'];
            $observacao = $_POST['observacao'] ?? '';
            
            // Debug: Verificar valores recebidos
            error_log("ID Agendamento: " . $id_agendamento);
            error_log("Novo Status: " . $novo_status);
            error_log("Observação: " . $observacao);
            
            // Verificar se o agendamento existe primeiro
            $sql_verificar = "SELECT a.id_agendamento, a.status, a.observacoes, a.cliente_id, a.valor 
                             FROM agendamentos a 
                             WHERE a.id_agendamento = ? AND a.profissional_id = ?";
            $stmt_verificar = $pdo->prepare($sql_verificar);
            $stmt_verificar->execute([$id_agendamento, $profissional_id]);
            $agendamento_atual = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
            
            if ($agendamento_atual) {
                // Validar se o novo status é válido
                $status_validos = ['agendado', 'confirmado', 'concluido', 'cancelado'];
                if (!in_array($novo_status, $status_validos)) {
                    $mensagem = "Status inválido selecionado: " . htmlspecialchars($novo_status);
                    $tipo_mensagem = "danger";
                } else {
                    try {
                        // Verificar se está mudando para concluído
                        $status_anterior = $agendamento_atual['status'];
                        $mudou_para_concluido = ($novo_status === 'concluido' && $status_anterior !== 'concluido');
                        
                        // Atualizar o status
                        if (!empty($observacao)) {
                            $observacoes_atuais = $agendamento_atual['observacoes'] ?? '';
                            $novas_observacoes = !empty($observacoes_atuais) ? $observacoes_atuais . ' | ' . $observacao : $observacao;
                            
                            $sql = "UPDATE agendamentos SET status = ?, observacoes = ? WHERE id_agendamento = ? AND profissional_id = ?";
                            $stmt = $pdo->prepare($sql);
                            $resultado = $stmt->execute([$novo_status, $novas_observacoes, $id_agendamento, $profissional_id]);
                        } else {
                            $sql = "UPDATE agendamentos SET status = ? WHERE id_agendamento = ? AND profissional_id = ?";
                            $stmt = $pdo->prepare($sql);
                            $resultado = $stmt->execute([$novo_status, $id_agendamento, $profissional_id]);
                        }
                        
                        if ($resultado && $stmt->rowCount() > 0) {
                            // Se mudou para concluído, gerenciar pontos de fidelidade
                            if ($mudou_para_concluido) {
                                $pontos_ganhos = gerenciarPontosFidelidade($pdo, $agendamento_atual['cliente_id'], $agendamento_atual['valor']);
                                if ($pontos_ganhos > 0) {
                                    $mensagem = "Status atualizado para Concluído! Cliente ganhou {$pontos_ganhos} pontos de fidelidade.";
                                } else {
                                    $mensagem = "Status atualizado para Concluído!";
                                }
                            } else {
                                $status_nome = [
                                    'agendado' => 'Agendado',
                                    'confirmado' => 'Confirmado', 
                                    'concluido' => 'Concluído',
                                    'cancelado' => 'Cancelado'
                                ];
                                $mensagem = "Status atualizado com sucesso para: " . $status_nome[$novo_status];
                            }
                            $tipo_mensagem = "success";
                            
                            // Log de sucesso
                            error_log("Status atualizado com sucesso: ID " . $id_agendamento . " -> " . $novo_status);
                        } else {
                            $mensagem = "Nenhuma alteração foi feita. Verifique se o agendamento existe.";
                            $tipo_mensagem = "warning";
                            error_log("Nenhuma linha foi afetada na atualização");
                        }
                    } catch (PDOException $e) {
                        $mensagem = "Erro no banco de dados: " . $e->getMessage();
                        $tipo_mensagem = "danger";
                        error_log("Erro PDO: " . $e->getMessage());
                    }
                }
            } else {
                $mensagem = "Agendamento não encontrado (ID: " . htmlspecialchars($id_agendamento) . ")";
                $tipo_mensagem = "danger";
                error_log("Agendamento não encontrado: ID " . $id_agendamento);
            }
        }
        
        if (isset($_POST['cancelar_agendamento'])) {
            $id_agendamento = $_POST['id_agendamento_cancelar'];
            $motivo = $_POST['motivo_cancelamento'];
            
            // Verificar se o agendamento existe e pertence ao profissional
            $sql_verificar = "SELECT id_agendamento FROM agendamentos WHERE id_agendamento = ? AND profissional_id = ? AND status != 'cancelado'";
            $stmt_verificar = $pdo->prepare($sql_verificar);
            $stmt_verificar->execute([$id_agendamento, $profissional_id]);
            
            if ($stmt_verificar->rowCount() > 0) {
                $sql = "UPDATE agendamentos SET status = 'cancelado', motivo_cancelamento = ? WHERE id_agendamento = ? AND profissional_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$motivo, $id_agendamento, $profissional_id]);
                
                $mensagem = "Agendamento cancelado com sucesso!";
                $tipo_mensagem = "warning";
            } else {
                $mensagem = "Erro: Agendamento não encontrado ou já está cancelado.";
                $tipo_mensagem = "danger";
            }
        }
        
        if (isset($_POST['excluir_agendamento'])) {
            $id_agendamento = $_POST['id_agendamento_excluir'];
            
            // Verificar se o agendamento existe e pertence ao profissional
            $sql_verificar = "SELECT id_agendamento FROM agendamentos WHERE id_agendamento = ? AND profissional_id = ?";
            $stmt_verificar = $pdo->prepare($sql_verificar);
            $stmt_verificar->execute([$id_agendamento, $profissional_id]);
            
            if ($stmt_verificar->rowCount() > 0) {
                $sql = "DELETE FROM agendamentos WHERE id_agendamento = ? AND profissional_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_agendamento, $profissional_id]);
                
                $mensagem = "Agendamento excluído com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Erro: Agendamento não encontrado ou você não tem permissão para excluí-lo.";
                $tipo_mensagem = "danger";
            }
        }
        
    } catch (Exception $e) {
        $mensagem = "Erro ao processar ação: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Buscar configuração da agenda do profissional
$stmt = $pdo->prepare("SELECT * FROM profissional_agenda WHERE profissional_id = ?");
$stmt->execute([$profissional_id]);
$config_agenda = $stmt->fetch(PDO::FETCH_ASSOC);

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

// Função para verificar se o profissional trabalha em determinado dia
function profissionalTrabalha($config, $dia_semana) {
    $dia_campo = $dias_semana_map[$dia_semana] ?? 'segunda';
    return $config && $config["{$dia_campo}_trabalha"];
}

// Buscar agendamentos da data selecionada
$sql = "SELECT a.*, u.nome as cliente_nome, u.telefone as cliente_telefone, s.nome as servico_nome, s.duracao
        FROM agendamentos a
        JOIN usuario u ON a.cliente_id = u.id_usuario
        JOIN servicos s ON a.servico_id = s.id_servico
        WHERE a.profissional_id = ? AND a.data_agendamento = ?
        ORDER BY a.hora_agendamento";
$stmt = $pdo->prepare($sql);
$stmt->execute([$profissional_id, $data_selecionada]);
$agendamentos_dia = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar dias bloqueados do mês atual
$primeiro_dia_mes = date('Y-m-01', strtotime($data_selecionada));
$ultimo_dia_mes = date('Y-m-t', strtotime($data_selecionada));

$sql = "SELECT data_bloqueio FROM dias_bloqueados 
        WHERE profissional_id = ? AND data_bloqueio BETWEEN ? AND ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$profissional_id, $primeiro_dia_mes, $ultimo_dia_mes]);
$dias_bloqueados = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'data_bloqueio');

// Gerar calendário do mês
function gerarCalendario($ano, $mes, $data_selecionada, $dias_bloqueados, $config_agenda, $profissional_id, $pdo) {
    global $dias_semana_map;
    
    $primeiro_dia = mktime(0, 0, 0, $mes, 1, $ano);
    $dias_no_mes = date('t', $primeiro_dia);
    $dia_semana_primeiro = date('w', $primeiro_dia);
    $mes_nome = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'][$mes];
    
    $html = "<div class='calendar-header text-center mb-3'>";
    $html .= "<h4>{$mes_nome} {$ano}</h4>";
    $html .= "</div>";
    
    $html .= "<div class='calendar'>";
    $html .= "<div class='calendar-header'>";
    $dias_semana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    foreach ($dias_semana as $dia) {
        $html .= "<div class='weekday'>{$dia}</div>";
    }
    $html .= "</div>";
    
    $html .= "<div class='calendar-days'>";
    
    // Dias vazios antes do primeiro dia do mês
    for ($i = 0; $i < $dia_semana_primeiro; $i++) {
        $html .= "<div class='calendar-day empty'></div>";
    }
    
    // Dias do mês
    for ($dia = 1; $dia <= $dias_no_mes; $dia++) {
        $data_atual = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
        $dia_semana = date('w', mktime(0, 0, 0, $mes, $dia, $ano));
        $dia_campo = $dias_semana_map[$dia_semana];
        
        $classes = ['calendar-day'];
        $pode_agendar = true;
        
        // Verificar se é hoje
        if ($data_atual === date('Y-m-d')) {
            $classes[] = 'today';
        }
        
        // Verificar se está selecionado
        if ($data_atual === $data_selecionada) {
            $classes[] = 'selected';
        }
        
        // Verificar se é dia passado
        if ($data_atual < date('Y-m-d')) {
            $classes[] = 'past-day';
            $pode_agendar = false;
        }
        
        // Verificar se é dia bloqueado
        if (in_array($data_atual, $dias_bloqueados)) {
            $classes[] = 'blocked-day';
            $pode_agendar = false;
        }
        
        // Verificar se profissional trabalha neste dia
        $trabalha_dia = $config_agenda && $config_agenda["{$dia_campo}_trabalha"];
        if (!$trabalha_dia) {
            $classes[] = 'no-work-day';
            $pode_agendar = false;
        }
        
        // Contar agendamentos do dia
        $sql = "SELECT COUNT(*) as total FROM agendamentos WHERE profissional_id = ? AND data_agendamento = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$profissional_id, $data_atual]);
        $total_agendamentos = $stmt->fetchColumn();
        
        if ($total_agendamentos > 0) {
            $classes[] = 'has-events';
        }
        
        $class_str = implode(' ', $classes);
        $link = $pode_agendar ? "href='?data={$data_atual}'" : "";
        
        $html .= "<a {$link} class='{$class_str}'>";
        $html .= "<span class='day-number'>{$dia}</span>";
        if ($total_agendamentos > 0) {
            $html .= "<div class='event-dot'>{$total_agendamentos}</div>";
        }
        $html .= "</a>";
    }
    
    $html .= "</div></div>";
    
    return $html;
}

$ano_atual = date('Y', strtotime($data_selecionada));
$mes_atual = date('n', strtotime($data_selecionada));

include_once('topo_sistema_profissional.php');
?>

<!-- CSS -->
<link rel="stylesheet" href="../CSS/Profissional/agenda_profissional.css">

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12">
            <!-- Header compacto -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1"><i class="bi bi-calendar3 text-primary me-2"></i>Minha Agenda</h4>
                    <small class="text-muted">Gerencie seus agendamentos e horários</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="profissional_configurar_agenda.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-gear me-1"></i>Configurar Agenda
                    </a>
                    <span class="badge bg-success fs-6 px-3 py-2">
                        <i class="bi bi-calendar-check me-1"></i><?php echo date('d/m/Y', strtotime($data_selecionada)); ?>
                    </span>
                </div>
            </div>

            <!-- Mensagem -->
            <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $tipo_mensagem === 'success' ? 'check-circle' : ($tipo_mensagem === 'warning' ? 'exclamation-triangle' : 'x-circle'); ?> me-2"></i>
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="row">
                <!-- Calendário -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Calendário</h5>
                                <div class="d-flex gap-1">
                                    <a href="?data=<?php echo date('Y-m-d', strtotime($data_selecionada . ' -1 month')); ?>" class="btn btn-sm btn-outline-light">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                    <a href="?data=<?php echo date('Y-m-d', strtotime($data_selecionada . ' +1 month')); ?>" class="btn btn-sm btn-outline-light">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php echo gerarCalendario($ano_atual, $mes_atual, $data_selecionada, $dias_bloqueados, $config_agenda, $profissional_id, $pdo); ?>
                            
                            <!-- Legenda -->
                            <div class="calendar-legend mt-3">
                                <small class="text-muted d-block mb-2"><strong>Legenda:</strong></small>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-warning"><i class="legend-dot today-dot me-1"></i>Hoje</span>
                                    <span class="badge bg-info"><i class="legend-dot event-dot-sample me-1"></i>Com agendamentos</span>
                                    <span class="badge bg-secondary">Dias sem trabalho</span>
                                    <span class="badge bg-danger">Dias bloqueados</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Agendamentos do Dia -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-clock me-2"></i>Agendamentos - <?php echo date('d/m/Y', strtotime($data_selecionada)); ?>
                                </h5>
                                <div class="d-flex gap-2 align-items-center">
                                    <span class="badge bg-light text-dark"><?php echo count($agendamentos_dia); ?> agendamentos</span>
                                    <a href="profissional_agendar.php?data=<?php echo $data_selecionada; ?>" class="btn btn-custom btn-sm">
                                        <i class="bi bi-plus-circle me-1"></i>Novo Agendamento
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($agendamentos_dia)): ?>
                            <div class="day-timeline">
                                <?php foreach ($agendamentos_dia as $agendamento): ?>
                                <div class="time-slot status-<?php echo $agendamento['status']; ?>">
                                    <div class="time">
                                        <?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?>
                                    </div>
                                    <div class="appointment">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($agendamento['cliente_nome']); ?></h6>
                                                <div class="appointment-details">
                                                    <div class="detail">
                                                        <i class="bi bi-scissors"></i>
                                                        <?php echo htmlspecialchars($agendamento['servico_nome']); ?>
                                                    </div>
                                                    <div class="detail">
                                                        <i class="bi bi-clock"></i>
                                                        <?php echo $agendamento['duracao']; ?> min
                                                    </div>
                                                    <div class="detail">
                                                        <i class="bi bi-currency-dollar"></i>
                                                        R$ <?php echo number_format($agendamento['valor'], 2, ',', '.'); ?>
                                                    </div>
                                                    <div class="detail">
                                                        <i class="bi bi-telephone"></i>
                                                        <?php echo htmlspecialchars($agendamento['cliente_telefone']); ?>
                                                    </div>
                                                </div>
                                                
                                                <!-- Status Badge -->
                                                <div class="mt-2">
                                                    <?php
                                                    switch($agendamento['status']) {
                                                        case 'agendado':
                                                            echo '<span class="badge bg-warning"><i class="bi bi-clock me-1"></i>Agendado</span>';
                                                            break;
                                                        case 'confirmado':
                                                            echo '<span class="badge bg-info"><i class="bi bi-check-circle me-1"></i>Confirmado</span>';
                                                            break;
                                                        case 'concluido':
                                                            echo '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Concluído</span>';
                                                            break;
                                                        case 'cancelado':
                                                            echo '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Cancelado</span>';
                                                            break;
                                                    }
                                                    ?>
                                                </div>

                                                <?php if (!empty($agendamento['observacoes'])): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="bi bi-chat-left-text me-1"></i>
                                                        <?php echo htmlspecialchars($agendamento['observacoes']); ?>
                                                    </small>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Botões de Ação -->
                                            <div class="d-flex flex-column gap-1">
                                                <?php if ($agendamento['status'] !== 'cancelado' && $agendamento['status'] !== 'concluido'): ?>
                                                <button class="btn btn-sm btn-outline-primary" title="Atualizar Status"
                                                        data-bs-toggle="modal" data-bs-target="#statusModal"
                                                        data-id="<?php echo $agendamento['id_agendamento']; ?>">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($agendamento['status'] !== 'cancelado'): ?>
                                                <button class="btn btn-sm btn-outline-warning" title="Cancelar"
                                                        data-bs-toggle="modal" data-bs-target="#cancelarModal"
                                                        data-id="<?php echo $agendamento['id_agendamento']; ?>"
                                                        data-nome="<?php echo htmlspecialchars($agendamento['cliente_nome']); ?>">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-sm btn-outline-danger" title="Excluir"
                                                        data-bs-toggle="modal" data-bs-target="#excluirModal"
                                                        data-id="<?php echo $agendamento['id_agendamento']; ?>"
                                                        data-nome="<?php echo htmlspecialchars($agendamento['cliente_nome']); ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="empty-state text-center py-5">
                                <i class="bi bi-calendar-x display-1 text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhum agendamento para este dia</h5>
                                <p class="text-muted mb-4">Clique no botão abaixo para criar seu primeiro agendamento do dia.</p>
                                <a href="profissional_agendar.php?data=<?php echo $data_selecionada; ?>" class="btn btn-custom btn-lg px-4 py-2">
                                    <i class="bi bi-plus-circle me-2"></i>Criar Primeiro Agendamento
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para atualizar status -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Atualizar Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formStatus">
                <div class="modal-body">
                    <input type="hidden" name="id_agendamento_status" id="id_agendamento_status">
                    <div class="mb-3">
                        <label class="form-label">Novo Status *</label>
                        <select name="novo_status" id="novo_status" class="form-select" required>
                            <option value="">Selecione um status...</option>
                            <option value="agendado">Agendado</option>
                            <option value="confirmado">Confirmado</option>
                            <option value="concluido">Concluído</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observação (opcional)</label>
                        <textarea name="observacao" id="observacao" class="form-control" rows="3" placeholder="Digite uma observação..."></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>Esta ação atualizará o status do agendamento no sistema.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="atualizar_status" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Atualizar Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para cancelar agendamento -->
<div class="modal fade" id="cancelarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Cancelar Agendamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_agendamento_cancelar" id="id_agendamento_cancelar">
                    <p>Tem certeza que deseja cancelar o agendamento de <strong id="cliente_nome_cancelar"></strong>?</p>
                    <div class="mb-3">
                        <label class="form-label">Motivo do Cancelamento *</label>
                        <textarea name="motivo_cancelamento" class="form-control" rows="3" required placeholder="Digite o motivo do cancelamento..."></textarea>
                    </div>
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>O agendamento ficará marcado como cancelado e não será excluído permanentemente.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não, manter agendamento</button>
                    <button type="submit" name="cancelar_agendamento" class="btn btn-warning">
                        <i class="bi bi-x-circle me-1"></i>Sim, cancelar agendamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para excluir agendamento -->
<div class="modal fade" id="excluirModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Excluir Agendamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_agendamento_excluir" id="id_agendamento_excluir">
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle display-1 text-warning mb-3"></i>
                        <h5 class="mt-3">Tem certeza que deseja excluir?</h5>
                        <p class="mb-3">O agendamento de <strong id="cliente_nome_excluir"></strong> será removido permanentemente do sistema.</p>
                        <div class="alert alert-danger text-start">
                            <h6><i class="bi bi-exclamation-triangle me-2"></i>ATENÇÃO:</h6>
                            <ul class="mb-0">
                                <li>Esta ação não pode ser desfeita</li>
                                <li>Todos os dados do agendamento serão perdidos</li>
                                <li>Recomendamos usar "Cancelar" ao invés de "Excluir"</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-arrow-left me-1"></i>Cancelar exclusão
                    </button>
                    <button type="submit" name="excluir_agendamento" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Sim, excluir permanentemente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Estilos adicionais -->
<style>
.bg-gradient-dark {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
}

.card-header.bg-gradient-dark {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
}

/* Botão personalizado com gradiente */
.btn-custom {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border: none;
    color: white;
    font-weight: 500;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(44, 62, 80, 0.2);
    transition: all 0.3s ease;
}

.btn-custom:hover {
    background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(44, 62, 80, 0.3);
}

.btn-custom:focus,
.btn-custom:active {
    background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
    color: white;
    box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
}

.btn-custom.btn-lg {
    padding: 0.75rem 2rem;
    font-size: 1.1rem;
    border-radius: 10px;
}

.btn-custom.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 6px;
}

.calendar-day.past-day {
    background-color: #f8f9fa !important;
    color: #6c757d !important;
    cursor: not-allowed !important;
    opacity: 0.6;
}

.calendar-day.blocked-day {
    background-color: #ffebee !important;
    color: #c62828 !important;
    cursor: not-allowed !important;
}

.calendar-day.no-work-day {
    background-color: #f5f5f5 !important;
    color: #9e9e9e !important;
    cursor: not-allowed !important;
    text-decoration: line-through;
}

.calendar-day.past-day:hover,
.calendar-day.blocked-day:hover,
.calendar-day.no-work-day:hover {
    transform: none !important;
    box-shadow: none !important;
}

/* Estilos para formulário inline */
.form-select, .form-control {
    font-size: 0.9rem;
}

.badge.fs-6 {
    font-size: 0.9rem !important;
}

#resumo_agendamento .badge {
    display: inline-block;
    margin: 2px 0;
}
</style>

<!-- Scripts -->
<script src="../JS/agenda_profissional.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<div class="mb-5"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal para atualizar status
    const statusModal = document.getElementById('statusModal');
    if (statusModal) {
        statusModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            
            // Debug
            console.log('Abrindo modal para agendamento ID:', id);
            
            document.getElementById('id_agendamento_status').value = id;
            
            // Limpar campos
            document.getElementById('novo_status').value = '';
            document.getElementById('observacao').value = '';
        });
    }

    // Validação do formulário de status
    const formStatus = document.getElementById('formStatus');
    if (formStatus) {
        formStatus.addEventListener('submit', function(e) {
            const novoStatus = document.getElementById('novo_status').value;
            const idAgendamento = document.getElementById('id_agendamento_status').value;
            
            console.log('Enviando formulário:', {
                id: idAgendamento,
                status: novoStatus
            });
            
            if (!novoStatus) {
                e.preventDefault();
                alert('Por favor, selecione um status.');
                return false;
            }
            
            if (!idAgendamento) {
                e.preventDefault();
                alert('Erro: ID do agendamento não encontrado.');
                return false;
            }
        });
    }

    // Modal para cancelar agendamento
    const cancelarModal = document.getElementById('cancelarModal');
    if (cancelarModal) {
        cancelarModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nome = button.getAttribute('data-nome');
            
            document.getElementById('id_agendamento_cancelar').value = id;
            document.getElementById('cliente_nome_cancelar').textContent = nome;
            
            // Limpar o campo de motivo
            const motivoField = cancelarModal.querySelector('textarea[name="motivo_cancelamento"]');
            if (motivoField) {
                motivoField.value = '';
            }
        });
    }

    // Modal para excluir agendamento
    const excluirModal = document.getElementById('excluirModal');
    if (excluirModal) {
        excluirModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nome = button.getAttribute('data-nome');
            
            document.getElementById('id_agendamento_excluir').value = id;
            document.getElementById('cliente_nome_excluir').textContent = nome;
        });
    }

    // Auto-fechar alertas após 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>
