<?php
session_start();

// Verifica se o usuário está logado e é cliente
if(!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'Cliente'){
    header('Location: login.php');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
include_once('../Conexao/conexao.php');

$cliente_id = $_SESSION['id_usuario'];
$mensagem = '';
$tipo_mensagem = '';

// Data selecionada (padrão: hoje)
$data_selecionada = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');
$profissional_selecionado = isset($_GET['profissional']) ? $_GET['profissional'] : '';

// Processar novo agendamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_agendamento'])) {
    try {
        $profissional_id = $_POST['profissional_id'];
        $servico_id = $_POST['servico_id'];
        $data_agendamento = $_POST['data_agendamento'];
        $hora_agendamento = $_POST['hora_agendamento'];
        $observacoes = $_POST['observacoes'] ?? '';
        
        // Verificar se o horário está disponível
        $sql_verificar = "SELECT COUNT(*) FROM agendamentos 
                         WHERE profissional_id = ? AND data_agendamento = ? AND hora_agendamento = ? 
                         AND status NOT IN ('cancelado')";
        $stmt_verificar = $pdo->prepare($sql_verificar);
        $stmt_verificar->execute([$profissional_id, $data_agendamento, $hora_agendamento]);
        
        if ($stmt_verificar->fetchColumn() > 0) {
            $mensagem = "Este horário já está ocupado. Por favor, escolha outro horário.";
            $tipo_mensagem = "warning";
        } else {
            // Buscar valor do serviço
            $stmt = $pdo->prepare("SELECT valor FROM servicos WHERE id_servico = ?");
            $stmt->execute([$servico_id]);
            $valor_servico = $stmt->fetchColumn();
            
            $sql = "INSERT INTO agendamentos (cliente_id, profissional_id, servico_id, data_agendamento, hora_agendamento, valor, status, observacoes) 
                    VALUES (?, ?, ?, ?, ?, ?, 'agendado', ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$cliente_id, $profissional_id, $servico_id, $data_agendamento, $hora_agendamento, $valor_servico, $observacoes]);
            
            $mensagem = "Agendamento realizado com sucesso! Aguarde a confirmação.";
            $tipo_mensagem = "success";
            
            // Redirecionar para evitar reenvio do formulário
            header("Location: cliente_agendar.php?sucesso=1");
            exit;
        }
    } catch (Exception $e) {
        $mensagem = "Erro ao criar agendamento: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Mensagem de sucesso via GET
if (isset($_GET['sucesso'])) {
    $mensagem = "Agendamento realizado com sucesso! Aguarde a confirmação.";
    $tipo_mensagem = "success";
}

// Buscar profissionais ativos
$sql_profissionais = "SELECT u.id_usuario, u.nome, u.foto 
                     FROM usuario u 
                     WHERE u.tipo_usuario = 'Profissional' AND u.ativo = 1 
                     ORDER BY u.nome";
$stmt_profissionais = $pdo->prepare($sql_profissionais);
$stmt_profissionais->execute();
$profissionais = $stmt_profissionais->fetchAll(PDO::FETCH_ASSOC);

// Buscar serviços ativos organizados por categoria
$sql_servicos = "SELECT * FROM servicos WHERE ativo = 1 ORDER BY categoria, nome";
$stmt_servicos = $pdo->prepare($sql_servicos);
$stmt_servicos->execute();
$servicos_raw = $stmt_servicos->fetchAll(PDO::FETCH_ASSOC);

// Organizar serviços por categoria
$servicos_por_categoria = [];
foreach ($servicos_raw as $servico) {
    $categoria = $servico['categoria'] ?? 'Geral';
    if (!isset($servicos_por_categoria[$categoria])) {
        $servicos_por_categoria[$categoria] = [];
    }
    $servicos_por_categoria[$categoria][] = $servico;
}

// Buscar meus agendamentos futuros
$sql_meus_agendamentos = "SELECT a.*, u.nome as profissional_nome, s.nome as servico_nome, s.duracao
                         FROM agendamentos a
                         JOIN usuario u ON a.profissional_id = u.id_usuario
                         JOIN servicos s ON a.servico_id = s.id_servico
                         WHERE a.cliente_id = ? AND a.data_agendamento >= CURDATE()
                         ORDER BY a.data_agendamento, a.hora_agendamento";
$stmt_meus = $pdo->prepare($sql_meus_agendamentos);
$stmt_meus->execute([$cliente_id]);
$meus_agendamentos = $stmt_meus->fetchAll(PDO::FETCH_ASSOC);

// Buscar configuração da agenda do profissional se selecionado
$config_agenda = null;
if ($profissional_selecionado) {
    $stmt = $pdo->prepare("SELECT * FROM profissional_agenda WHERE profissional_id = ?");
    $stmt->execute([$profissional_selecionado]);
    $config_agenda = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Buscar dias bloqueados do profissional
    $primeiro_dia_mes = date('Y-m-01', strtotime($data_selecionada));
    $ultimo_dia_mes = date('Y-m-t', strtotime($data_selecionada));
    
    $sql = "SELECT data_bloqueio FROM dias_bloqueados 
            WHERE profissional_id = ? AND data_bloqueio BETWEEN ? AND ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$profissional_selecionado, $primeiro_dia_mes, $ultimo_dia_mes]);
    $dias_bloqueados = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'data_bloqueio');
} else {
    $dias_bloqueados = [];
}

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
function profissionalTrabalhaCliente($config, $dia_semana) {
    global $dias_semana_map;
    if (!$config) return false;
    $dia_campo = $dias_semana_map[$dia_semana] ?? 'segunda';
    return $config["{$dia_campo}_trabalha"] ?? false;
}

// Função para gerar horários disponíveis baseado na agenda do profissional
function gerarHorariosDisponiveisComAgenda($pdo, $profissional_id, $data, $config_agenda) {
    if (!$config_agenda) return [];
    
    $dia_semana = date('w', strtotime($data));
    global $dias_semana_map;
    $dia_campo = $dias_semana_map[$dia_semana];
    
    // Verificar se trabalha neste dia
    if (!$config_agenda["{$dia_campo}_trabalha"]) {
        return [];
    }
    
    $horarios = [];
    $hora_inicio_campo = "{$dia_campo}_inicio";
    $hora_fim_campo = "{$dia_campo}_fim";
    
    $hora_inicio = $config_agenda[$hora_inicio_campo];
    $hora_fim = $config_agenda[$hora_fim_campo];
    
    if (!$hora_inicio || !$hora_fim) return [];
    
    $inicio = new DateTime($hora_inicio);
    $fim = new DateTime($hora_fim);
    $intervalo = new DateInterval('PT30M'); // Intervalos de 30 minutos
    
    while ($inicio < $fim) {
        $horario = $inicio->format('H:i:s');
        
        // Verificar se o horário está ocupado
        $sql = "SELECT COUNT(*) FROM agendamentos 
               WHERE profissional_id = ? AND data_agendamento = ? AND hora_agendamento = ? 
               AND status NOT IN ('cancelado')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$profissional_id, $data, $horario]);
        
        if ($stmt->fetchColumn() == 0) {
            $horarios[] = $horario;
        }
        
        $inicio->add($intervalo);
    }
    
    return $horarios;
}

// Gerar calendário do mês baseado na agenda do profissional
function gerarCalendarioClienteComAgenda($ano, $mes, $data_selecionada, $profissional_id, $config_agenda, $dias_bloqueados, $pdo) {
    global $dias_semana_map;
    
    $primeiro_dia = mktime(0, 0, 0, $mes, 1, $ano);
    $dias_no_mes = date('t', $primeiro_dia);
    $dia_semana_primeiro = date('w', $primeiro_dia);
    $mes_nome = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'][$mes];
    
    $html = "<div class='calendar-header-title text-center mb-3'>";
    $html .= "<h5 class='text-primary'><i class='bi bi-calendar3 me-2'></i>{$mes_nome} {$ano}</h5>";
    $html .= "</div>";
    
    $html .= "<div class='calendar-grid'>";
    $html .= "<div class='calendar-weekdays'>";
    $dias_semana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    foreach ($dias_semana as $dia) {
        $html .= "<div class='weekday-header'>{$dia}</div>";
    }
    $html .= "</div>";
    
    $html .= "<div class='calendar-days-grid'>";
    
    // Dias vazios antes do primeiro dia do mês
    for ($i = 0; $i < $dia_semana_primeiro; $i++) {
        $html .= "<div class='calendar-day empty-day'></div>";
    }
    
    // Dias do mês
    for ($dia = 1; $dia <= $dias_no_mes; $dia++) {
        $data_atual = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
        $dia_semana = date('w', mktime(0, 0, 0, $mes, $dia, $ano));
        $dia_campo = $dias_semana_map[$dia_semana];
        
        $classes = ['calendar-day'];
        $pode_selecionar = true;
        
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
            $pode_selecionar = false;
        }
        
        // Verificar se é dia bloqueado
        if (in_array($data_atual, $dias_bloqueados)) {
            $classes[] = 'blocked-day';
            $pode_selecionar = false;
        }
        
        // Verificar se profissional trabalha neste dia
        $trabalha_dia = profissionalTrabalhaCliente($config_agenda, $dia_semana);
        if (!$trabalha_dia) {
            $classes[] = 'no-work-day';
            $pode_selecionar = false;
        }
        
        // Contar agendamentos do dia
        $total_agendamentos = 0;
        if ($profissional_id) {
            $sql = "SELECT COUNT(*) as total FROM agendamentos WHERE profissional_id = ? AND data_agendamento = ? AND status NOT IN ('cancelado')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$profissional_id, $data_atual]);
            $total_agendamentos = $stmt->fetchColumn();
        }
        
        if ($total_agendamentos > 0) {
            $classes[] = 'has-events';
        }
        
        $class_str = implode(' ', $classes);
        $link = $pode_selecionar ? "href='?data={$data_atual}&profissional={$profissional_id}'" : "";
        $cursor = $pode_selecionar ? "style='cursor: pointer;'" : "style='cursor: not-allowed;'";
        
        $html .= "<a {$link} class='{$class_str}' {$cursor}>";
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

include_once('topo_sistema_cliente.php');
?>

<!-- CSS personalizado -->
<link rel="stylesheet" href="../CSS/Cliente/sistema_cliente.css">
<link rel="stylesheet" href="../CSS/Profissional/agenda_profissional.css">

<style>
/* Simplificar estilos - remover gradientes e deixar mais limpo */
.profissional-selector {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.step-indicator {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.step {
    display: flex;
    align-items: center;
    color: #6c757d;
}

.step.active {
    color: #495057;
    font-weight: 600;
}

.step-number {
    background: #e9ecef;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 8px;
    font-weight: 600;
    font-size: 0.875rem;
}

.step.active .step-number {
    background: #495057;
    color: white;
}

.step-separator {
    width: 30px;
    height: 1px;
    background: #dee2e6;
    margin: 0 10px;
}

.agenda-info {
    background: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 12px;
    margin-top: 10px;
}

/* Calendário simples */
.calendar-grid {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #dee2e6;
}

.calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: #495057;
}

.weekday-header {
    padding: 10px;
    text-align: center;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
}

.calendar-days-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #f8f9fa;
}

.calendar-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    text-decoration: none;
    color: #495057;
    font-weight: 500;
    transition: all 0.2s ease;
    position: relative;
    min-height: 40px;
}

.calendar-day:hover:not(.past-day):not(.no-work-day):not(.blocked-day) {
    background: #e9ecef;
    color: #495057;
}

.calendar-day.today {
    background: #007bff;
    color: white;
}

.calendar-day.selected {
    background: #495057;
    color: white;
}

.calendar-day.past-day {
    background: #f8f9fa;
    color: #adb5bd;
    opacity: 0.6;
}

.calendar-day.blocked-day {
    background: #fff5f5;
    color: #dc3545;
    opacity: 0.7;
}

.calendar-day.no-work-day {
    background: #f8f9fa;
    color: #adb5bd;
    text-decoration: line-through;
}

.calendar-day.empty-day {
    background: transparent;
}

.calendar-day.has-events .event-dot {
    position: absolute;
    top: 3px;
    right: 3px;
    background: #ffc107; /* Mudança: de #dc3545 para #ffc107 (amarelo) */
    color: #212529; /* Mudança: de white para texto escuro para melhor contraste */
    border-radius: 50%;
    width: 16px;
    height: 16px;
    font-size: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* Força a cor amarela para dias com agendamentos */
.calendar-day .event-dot,
.has-events .event-dot {
    background-color: #ffc107 !important;
    color: #212529 !important;
    border: 1px solid #e0a800;
}

/* Garantir que a legenda também mostre a cor correta */
.legend-dot.event-dot {
    background-color: #ffc107 !important;
    border-color: #e0a800 !important;
}

/* Estilos para a legenda do calendário */
.calendar-legend {
    background-color: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding: 15px;
    margin-top: 15px;
}

.legend-dot {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 1px solid rgba(0,0,0,0.1);
    flex-shrink: 0;
}

/* Cores que correspondem aos dias do calendário */
.today-dot {
    background-color: #007bff;
    border-color: #0056b3;
}

.selected-dot {
    background-color: #495057;
    border-color: #343a40;
}

.event-dot {
    background-color: #ffc107;
    border-color: #e0a800;
}

.no-work-dot {
    background-color: #6c757d;
    border-color: #5a6268;
}

.blocked-dot {
    background-color: #dc3545;
    border-color: #c82333;
}

.past-dot {
    background-color: #e9ecef;
    border-color: #adb5bd;
}

/* Estilização dos badges da legenda */
.calendar-legend .badge {
    font-size: 0.75rem;
    padding: 0.5em 0.75em;
    font-weight: 500;
    margin: 0.125rem;
    gap: 0.375rem;
}

/* Responsividade */
@media (max-width: 576px) {
    .calendar-legend .d-flex.flex-wrap {
        flex-direction: column;
    }
    
    .calendar-legend .badge {
        justify-content: flex-start;
        margin-bottom: 0.25rem;
    }
}

/* Cards simples */
.appointment-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    border: 1px solid #dee2e6;
    transition: none;
}

.appointment-card:hover {
    transform: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Botões simples */
.btn-primary {
    background: #495057;
    border: 1px solid #495057;
    border-radius: 6px;
    padding: 8px 16px;
    font-weight: 500;
}

.btn-primary:hover {
    background: #343a40;
    border-color: #343a40;
}

.btn-outline-primary {
    border: 1px solid #495057;
    color: #495057;
    background: transparent;
    border-radius: 6px;
}

.btn-outline-primary:hover {
    background: #495057;
    border-color: #495057;
    color: white;
}

/* Formulário limpo */
.form-control, .form-select {
    border-radius: 6px;
    border: 1px solid #ced4da;
    padding: 8px 12px;
}

.form-control:focus, .form-select:focus {
    border-color: #495057;
    box-shadow: 0 0 0 0.2rem rgba(73, 80, 87, 0.25);
}

/* Header limpo */
.page-header {
    background: white;
    border-bottom: 1px solid #dee2e6;
    padding: 20px 0;
    margin-bottom: 20px;
}

/* Time slots simples */
.time-slot {
    background: white;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 10px;
    border: 1px solid #dee2e6;
    border-left: 3px solid #495057;
}

.status-agendado { border-left-color: #ffc107; }
.status-confirmado { border-left-color: #17a2b8; }
.status-concluido { border-left-color: #28a745; }
.status-cancelado { border-left-color: #dc3545; }

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

/* Remover estilos de gradiente e efeitos excessivos */
.welcome-banner,
.overview-card,
.icon-container {
    background: white !important;
    border: 1px solid #dee2e6 !important;
    box-shadow: none !important;
}

.overview-card:hover {
    transform: none !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
}

/* Aplicar gradiente escuro nos headers dos cards */
.card-header.bg-dark {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
    border: none;
}

/* Garantir que o texto permaneça branco */
.card-header.bg-dark,
.card-header.bg-dark h5,
.card-header.bg-dark .btn-outline-light {
    color: white !important;
}

/* Botões outline light nos headers */
.card-header .btn-outline-light {
    border-color: rgba(255, 255, 255, 0.3);
    color: rgba(255, 255, 255, 0.9);
}

.card-header .btn-outline-light:hover {
    background-color: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
}

/* Estilizar select com categorias */
.form-select optgroup {
    font-weight: bold;
    font-style: normal;
    background-color: #f8f9fa;
    color: #495057;
    padding: 8px 12px;
}

.form-select option {
    font-weight: normal;
    padding: 6px 20px;
    background-color: white;
    color: #212529;
}

.form-select optgroup option {
    padding-left: 20px;
    font-size: 0.9rem;
}

/* Melhorar aparência do select */
.form-select:focus {
    border-color: #495057;
    box-shadow: 0 0 0 0.2rem rgba(73, 80, 87, 0.25);
}

/* Estilo para informações do serviço selecionado */
.service-info {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 12px;
    margin-top: 10px;
    display: none;
}

.service-info.show {
    display: block;
    animation: fadeInUp 0.3s ease;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.service-detail {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.service-detail:last-child {
    margin-bottom: 0;
}
</style>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12">
            <!-- Header compacto -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1"><i class="bi bi-calendar-plus text-primary me-2"></i>Agendar Serviço</h4>
                    <small class="text-muted">Escolha o profissional e horário desejado</small>
                </div>
                <span class="badge bg-secondary fs-6 px-3 py-2">
                    <i class="bi bi-calendar-event me-1"></i><?php echo date('d/m/Y', strtotime($data_selecionada)); ?>
                </span>
            </div>

            <!-- Seleção de profissional -->
            <div class="profissional-selector">
                <div class="step-indicator">
                    <div class="step <?php echo !$profissional_selecionado ? 'active' : ''; ?>">
                        <div class="step-number">1</div>
                        <span>Selecionar Profissional</span>
                    </div>
                    <div class="step-separator"></div>
                    <div class="step <?php echo $profissional_selecionado && !isset($_POST['criar_agendamento']) ? 'active' : ''; ?>">
                        <div class="step-number">2</div>
                        <span>Escolher Data e Horário</span>
                    </div>
                    <div class="step-separator"></div>
                    <div class="step <?php echo isset($_POST['criar_agendamento']) ? 'active' : ''; ?>">
                        <div class="step-number">3</div>
                        <span>Confirmar Agendamento</span>
                    </div>
                </div>
                
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Escolha o profissional:</label>
                        <select id="seletor_profissional" class="form-select">
                            <option value="">Selecione um profissional...</option>
                            <?php foreach ($profissionais as $prof): ?>
                            <option value="<?php echo $prof['id_usuario']; ?>" <?php echo $profissional_selecionado == $prof['id_usuario'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prof['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if ($profissional_selecionado && $config_agenda): ?>
                    <div class="col-md-6">
                        <div class="agenda-info">
                            <h6 class="mb-2"><i class="bi bi-info-circle me-2"></i>Informações da Agenda</h6>
                            <div class="row">
                                <?php 
                                $dias_trabalho = [];
                                $intervalos_trabalho = [];
                                
                                foreach ($dias_semana_map as $num => $nome) {
                                    if (isset($config_agenda["{$nome}_trabalha"]) && $config_agenda["{$nome}_trabalha"]) {
                                        $dias_trabalho[] = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'][$num];
                                        
                                        // Verificar se há intervalo para este dia
                                        $intervalo_inicio_campo = "{$nome}_intervalo_inicio";
                                        $intervalo_fim_campo = "{$nome}_intervalo_fim";
                                        
                                        if (isset($config_agenda[$intervalo_inicio_campo]) && isset($config_agenda[$intervalo_fim_campo]) &&
                                            $config_agenda[$intervalo_inicio_campo] && $config_agenda[$intervalo_fim_campo]) {
                                            $intervalo = date('H:i', strtotime($config_agenda[$intervalo_inicio_campo])) . ' - ' . 
                                                        date('H:i', strtotime($config_agenda[$intervalo_fim_campo]));
                                            if (!in_array($intervalo, $intervalos_trabalho)) {
                                                $intervalos_trabalho[] = $intervalo;
                                            }
                                        }
                                    }
                                }
                                ?>
                                <div class="col-6">
                                    <small class="text-muted">Dias de trabalho:</small>
                                    <div class="fw-bold"><?php echo !empty($dias_trabalho) ? implode(', ', $dias_trabalho) : 'Não configurado'; ?></div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Intervalos de pausa:</small>
                                    <div class="fw-bold">
                                        <?php if (!empty($intervalos_trabalho)): ?>
                                            <?php echo implode('<br>', $intervalos_trabalho); ?>
                                        <?php else: ?>
                                            Sem intervalos definidos
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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

            <?php if ($profissional_selecionado): ?>
            <div class="row">
                <!-- Calendário -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Selecionar Data</h5>
                                <div class="d-flex gap-1">
                                    <a href="?data=<?php echo date('Y-m-d', strtotime($data_selecionada . ' -1 month')); ?>&profissional=<?php echo $profissional_selecionado; ?>" 
                                       class="btn btn-sm btn-outline-light">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                    <a href="?data=<?php echo date('Y-m-d', strtotime($data_selecionada . ' +1 month')); ?>&profissional=<?php echo $profissional_selecionado; ?>" 
                                       class="btn btn-sm btn-outline-light">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php echo gerarCalendarioClienteComAgenda($ano_atual, $mes_atual, $data_selecionada, $profissional_selecionado, $config_agenda, $dias_bloqueados, $pdo); ?>
                            
                            <!-- Legenda -->
                            <div class="mt-3">
                                <small class="text-muted d-block mb-2"><strong>Legenda:</strong></small>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-primary d-flex align-items-center">
                                        <i class="legend-dot today-dot me-1"></i>Hoje
                                    </span>
                                    <span class="badge bg-dark d-flex align-items-center">
                                        <i class="legend-dot selected-dot me-1"></i>Selecionado
                                    </span>
                                    <span class="badge bg-warning d-flex align-items-center">
                                        <i class="legend-dot event-dot me-1"></i>Com agendamentos
                                    </span>
                                    <span class="badge bg-secondary d-flex align-items-center">
                                        <i class="legend-dot no-work-dot me-1"></i>Não trabalha
                                    </span>
                                    <span class="badge bg-danger d-flex align-items-center">
                                        <i class="legend-dot blocked-dot me-1"></i>Bloqueado
                                    </span>
                                    <span class="badge bg-light text-dark d-flex align-items-center">
                                        <i class="legend-dot past-dot me-1"></i>Data passada
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulário de Agendamento -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Novo Agendamento</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="formAgendamento">
                                <input type="hidden" name="profissional_id" value="<?php echo $profissional_selecionado; ?>">
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Profissional</label>
                                        <input type="text" class="form-control" 
                                               value="<?php 
                                               foreach ($profissionais as $prof) {
                                                   if ($prof['id_usuario'] == $profissional_selecionado) {
                                                       echo htmlspecialchars($prof['nome']);
                                                       break;
                                                   }
                                               }
                                               ?>" readonly>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Serviço *</label>
                                        <select name="servico_id" id="servico_id" class="form-select" required>
                                            <option value="">Selecione um serviço...</option>
                                            <?php foreach ($servicos_por_categoria as $categoria => $servicos): ?>
                                                <optgroup label="📋 <?php echo htmlspecialchars($categoria); ?>">
                                                    <?php foreach ($servicos as $servico): ?>
                                                    <option value="<?php echo $servico['id_servico']; ?>" 
                                                            data-valor="<?php echo $servico['valor']; ?>"
                                                            data-duracao="<?php echo $servico['duracao']; ?>"
                                                            data-categoria="<?php echo htmlspecialchars($categoria); ?>"
                                                            data-descricao="<?php echo htmlspecialchars($servico['descricao'] ?? ''); ?>">
                                                        <?php echo htmlspecialchars($servico['nome']); ?> - 
                                                        R$ <?php echo number_format($servico['valor'], 2, ',', '.'); ?> 
                                                        (<?php echo $servico['duracao']; ?> min)
                                                    </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                        
                                        <!-- Informações do serviço selecionado -->
                                        <div id="service-info" class="service-info">
                                            <h6 class="mb-2">
                                                <i class="bi bi-info-circle text-primary me-1"></i>
                                                Detalhes do Serviço
                                            </h6>
                                            <div class="service-detail">
                                                <span class="text-muted">Categoria:</span>
                                                <span id="service-categoria" class="fw-bold"></span>
                                            </div>
                                            <div class="service-detail">
                                                <span class="text-muted">Duração:</span>
                                                <span id="service-duracao" class="fw-bold text-info"></span>
                                            </div>
                                            <div class="service-detail">
                                                <span class="text-muted">Valor:</span>
                                                <span id="service-valor" class="fw-bold text-success"></span>
                                            </div>
                                            <div id="service-descricao-container" style="display: none;">
                                                <hr class="my-2">
                                                <p id="service-descricao" class="text-muted small mb-0"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Data *</label>
                                        <input type="date" name="data_agendamento" id="data_agendamento" 
                                               class="form-control" value="<?php echo $data_selecionada; ?>" 
                                               min="<?php echo date('Y-m-d'); ?>" required readonly>
                                        <small class="text-muted">Selecione uma data no calendário</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Horário *</label>
                                        <select name="hora_agendamento" id="hora_agendamento" class="form-select" required>
                                            <option value="">Carregando horários...</option>
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Observações</label>
                                        <textarea name="observacoes" class="form-control" rows="3" 
                                                  placeholder="Observações para o agendamento..."></textarea>
                                    </div>
                                </div>

                                <!-- Resumo do Agendamento -->
                                <div id="resumo_agendamento" class="mt-4 p-3 bg-light rounded" style="display: none;">
                                    <h6><i class="bi bi-info-circle me-2"></i>Resumo do Agendamento</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">Profissional:</small>
                                            <div id="resumo_profissional" class="fw-bold"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Serviço:</small>
                                            <div id="resumo_servico" class="fw-bold"></div>
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <small class="text-muted">Data e Horário:</small>
                                            <div id="resumo_data_hora" class="fw-bold"></div>
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <small class="text-muted">Valor:</small>
                                            <div id="resumo_valor" class="fw-bold text-success"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <a href="cliente_agendar.php" class="btn btn-outline-secondary me-2">
                                        <i class="bi bi-arrow-left me-1"></i>Voltar
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary me-2" onclick="limparFormulario()">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Limpar
                                    </button>
                                    <button type="submit" name="criar_agendamento" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-1"></i>Confirmar Agendamento
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Mensagem para selecionar profissional -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-person-check display-1 text-muted mb-3"></i>
                            <h5 class="text-muted">Selecione um profissional para continuar</h5>
                            <p class="text-muted">Escolha o profissional desejado no seletor acima para ver sua agenda disponível.</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Meus Próximos Agendamentos -->
            <?php if (!empty($meus_agendamentos)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Meus Próximos Agendamentos</h5>
                                <a href="meus_agendamentos.php" class="btn btn-outline-light btn-sm">Ver Todos</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach (array_slice($meus_agendamentos, 0, 3) as $agendamento): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="time-slot status-<?php echo $agendamento['status']; ?>">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($agendamento['servico_nome']); ?></h6>
                                                <small class="text-muted">com <?php echo htmlspecialchars($agendamento['profissional_nome']); ?></small>
                                            </div>
                                            <?php
                                            switch($agendamento['status']) {
                                                case 'agendado':
                                                    echo '<span class="badge bg-warning">Agendado</span>';
                                                    break;
                                                case 'confirmado':
                                                    echo '<span class="badge bg-info">Confirmado</span>';
                                                    break;
                                                case 'concluido':
                                                    echo '<span class="badge bg-success">Concluído</span>';
                                                    break;
                                                case 'cancelado':
                                                    echo '<span class="badge bg-danger">Cancelado</span>';
                                                    break;
                                            }
                                            ?>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold">
                                                    <i class="bi bi-calendar me-1"></i>
                                                    <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?>
                                                </div>
                                                <div class="text-muted">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold text-success">R$ <?php echo number_format($agendamento['valor'], 2, ',', '.'); ?></div>
                                                <small class="text-muted"><?php echo $agendamento['duracao']; ?> min</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const seletorProfissional = document.getElementById('seletor_profissional');
    const dataInput = document.getElementById('data_agendamento');
    const horarioSelect = document.getElementById('hora_agendamento');
    const servicoSelect = document.getElementById('servico_id');

    // Mudar profissional
    seletorProfissional.addEventListener('change', function() {
        if (this.value) {
            window.location.href = `?profissional=${this.value}`;
        } else {
            window.location.href = 'cliente_agendar.php';
        }
    });

    // Carregar horários quando a página carrega ou data muda
    function carregarHorarios() {
        const profissionalId = <?php echo $profissional_selecionado ? $profissional_selecionado : 'null'; ?>;
        const data = dataInput ? dataInput.value : '';

        if (profissionalId && data) {
            horarioSelect.innerHTML = '<option value="">Carregando horários...</option>';

            // Fazer requisição AJAX para buscar horários
            fetch('buscar_horarios.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    profissional_id: profissionalId,
                    data: data
                })
            })
            .then(response => response.json())
            .then(data => {
                horarioSelect.innerHTML = '<option value="">Selecione um horário...</option>';
                
                if (data.horarios && data.horarios.length > 0) {
                    data.horarios.forEach(horario => {
                        const option = new Option(horario.substr(0, 5), horario);
                        horarioSelect.add(option);
                    });
                } else {
                    horarioSelect.innerHTML = '<option value="">Nenhum horário disponível para esta data</option>';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                horarioSelect.innerHTML = '<option value="">Erro ao carregar horários</option>';
            });
        }
    }

    // Carregar horários quando a página carrega
    if (<?php echo $profissional_selecionado ? 'true' : 'false'; ?>) {
        carregarHorarios();
    }

    // Função para atualizar resumo
    function atualizarResumo() {
        const profissional = profissionalSelect.options[profissionalSelect.selectedIndex]?.text;
        const servico = servicoSelect.options[servicoSelect.selectedIndex]?.text;
        const data = dataInput.value;
        const horario = horarioSelect.value;
        const valor = servicoSelect.options[servicoSelect.selectedIndex]?.dataset.valor;

        if (profissional && servico && data && horario && valor) {
            document.getElementById('resumo_profissional').textContent = profissional;
            document.getElementById('resumo_servico').textContent = servico;
            document.getElementById('resumo_data_hora').textContent = 
                new Date(data + 'T00:00:00').toLocaleDateString('pt-BR') + ' às ' + horario.substring(0, 5);
            document.getElementById('resumo_valor').textContent = 'R$ ' + parseFloat(valor).toFixed(2).replace('.', ',');
            
            resumoDiv.style.display = 'block';
        } else {
            resumoDiv.style.display = 'none';
        }
    }

    // Função para atualizar informações do serviço
    function atualizarInfoServico() {
        const servicoSelect = document.getElementById('servico_id');
        const serviceInfo = document.getElementById('service-info');
        const selectedOption = servicoSelect.options[servicoSelect.selectedIndex];
        
        if (servicoSelect.value && selectedOption) {
            const categoria = selectedOption.dataset.categoria;
            const duracao = selectedOption.dataset.duracao;
            const valor = selectedOption.dataset.valor;
            const descricao = selectedOption.dataset.descricao;
            
            document.getElementById('service-categoria').textContent = categoria;
            document.getElementById('service-duracao').textContent = duracao + ' minutos';
            document.getElementById('service-valor').textContent = 'R$ ' + parseFloat(valor).toFixed(2).replace('.', ',');
            
            // Mostrar descrição se existir
            if (descricao && descricao.trim() !== '') {
                document.getElementById('service-descricao').textContent = descricao;
                document.getElementById('service-descricao-container').style.display = 'block';
            } else {
                document.getElementById('service-descricao-container').style.display = 'none';
            }
            
            serviceInfo.classList.add('show');
        } else {
            serviceInfo.classList.remove('show');
        }
        
        atualizarResumo();
    }

    // Event listeners
    profissionalSelect.addEventListener('change', function() {
        buscarHorarios();
        atualizarResumo();
    });

    dataInput.addEventListener('change', function() {
        buscarHorarios();
        atualizarResumo();
    });

    horarioSelect.addEventListener('change', atualizarResumo);
    servicoSelect.addEventListener('change', atualizarResumo);
    servicoSelect.addEventListener('change', atualizarInfoServico);

    // Sincronizar data do calendário com o input
    const urlParams = new URLSearchParams(window.location.search);
    const dataSelecionada = urlParams.get('data');
    if (dataSelecionada) {
        dataInput.value = dataSelecionada;
        buscarHorarios();
    }
});

function limparFormulario() {
    document.getElementById('formAgendamento').reset();
    document.getElementById('resumo_agendamento').style.display = 'none';
    const profissionalId = <?php echo $profissional_selecionado ? $profissional_selecionado : 'null'; ?>;
    if (profissionalId) {
        document.querySelector('input[name="profissional_id"]').value = profissionalId;
    }
}
</script>

<!-- Adiciona espaço no final da página -->
<div class="mb-5"></div>
