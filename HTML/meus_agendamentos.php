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

// Filtros
$status_filtro = $_GET['status'] ?? 'todos';
$periodo_filtro = $_GET['periodo'] ?? 'todos';

// Processar cancelamento de agendamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_agendamento'])) {
    try {
        $id_agendamento = $_POST['id_agendamento'];
        $motivo = $_POST['motivo_cancelamento'];
        
        // Verificar se o agendamento pertence ao cliente e não está cancelado
        $sql_verificar = "SELECT status, data_agendamento, hora_agendamento 
                         FROM agendamentos 
                         WHERE id_agendamento = ? AND cliente_id = ? AND status NOT IN ('cancelado', 'concluido')";
        $stmt_verificar = $pdo->prepare($sql_verificar);
        $stmt_verificar->execute([$id_agendamento, $cliente_id]);
        $agendamento = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
        
        if ($agendamento) {
            // Verificar se o agendamento é com pelo menos 24h de antecedência
            $data_hora_agendamento = $agendamento['data_agendamento'] . ' ' . $agendamento['hora_agendamento'];
            $timestamp_agendamento = strtotime($data_hora_agendamento);
            $timestamp_limite = strtotime('+24 hours');
            
            if ($timestamp_agendamento > $timestamp_limite) {
                $sql = "UPDATE agendamentos SET status = 'cancelado', motivo_cancelamento = ? WHERE id_agendamento = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$motivo, $id_agendamento]);
                
                $mensagem = "Agendamento cancelado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Não é possível cancelar agendamentos com menos de 24 horas de antecedência. Entre em contato conosco.";
                $tipo_mensagem = "warning";
            }
        } else {
            $mensagem = "Agendamento não encontrado ou não pode ser cancelado.";
            $tipo_mensagem = "danger";
        }
    } catch (Exception $e) {
        $mensagem = "Erro ao cancelar agendamento: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Construir consulta com filtros
$sql_where = "WHERE a.cliente_id = ?";
$params = [$cliente_id];

if ($status_filtro !== 'todos') {
    $sql_where .= " AND a.status = ?";
    $params[] = $status_filtro;
}

if ($periodo_filtro !== 'todos') {
    switch ($periodo_filtro) {
        case 'proximos':
            $sql_where .= " AND a.data_agendamento >= CURDATE()";
            break;
        case 'mes_atual':
            $sql_where .= " AND MONTH(a.data_agendamento) = MONTH(CURDATE()) AND YEAR(a.data_agendamento) = YEAR(CURDATE())";
            break;
        case 'mes_passado':
            $sql_where .= " AND MONTH(a.data_agendamento) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(a.data_agendamento) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
            break;
    }
}

// Buscar agendamentos do cliente
$sql = "SELECT a.*, u.nome as profissional_nome, s.nome as servico_nome, s.duracao
        FROM agendamentos a
        JOIN usuario u ON a.profissional_id = u.id_usuario
        JOIN servicos s ON a.servico_id = s.id_servico
        {$sql_where}
        ORDER BY a.data_agendamento DESC, a.hora_agendamento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$sql_stats = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'agendado' THEN 1 ELSE 0 END) as agendados,
                SUM(CASE WHEN status = 'confirmado' THEN 1 ELSE 0 END) as confirmados,
                SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as concluidos,
                SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                SUM(CASE WHEN status = 'concluido' THEN valor ELSE 0 END) as valor_total
              FROM agendamentos WHERE cliente_id = ?";
$stmt_stats = $pdo->prepare($sql_stats);
$stmt_stats->execute([$cliente_id]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

include_once('topo_sistema_cliente.php');
?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1"><i class="bi bi-calendar-check text-primary me-2"></i>Meus Agendamentos</h4>
                    <small class="text-muted">Visualize e gerencie seus agendamentos</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="cliente_agendar.php" class="btn btn-custom btn-sm">
                        <i class="bi bi-plus-circle me-1"></i>Novo Agendamento
                    </a>
                    <span class="badge bg-success fs-6 px-3 py-2">
                        <i class="bi bi-calendar-event me-1"></i><?php echo count($agendamentos); ?> agendamentos
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

            <!-- Cards de estatísticas -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-primary mb-2">
                                <i class="bi bi-calendar-check display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['total']; ?></h3>
                            <p class="text-muted mb-0">Total de Agendamentos</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-success mb-2">
                                <i class="bi bi-check-circle display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['concluidos']; ?></h3>
                            <p class="text-muted mb-0">Concluídos</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-warning mb-2">
                                <i class="bi bi-clock display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['agendados'] + $stats['confirmados']; ?></h3>
                            <p class="text-muted mb-0">Pendentes</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-info mb-2">
                                <i class="bi bi-currency-dollar display-4"></i>
                            </div>
                            <h3 class="mb-1">R$ <?php echo number_format($stats['valor_total'], 2, ',', '.'); ?></h3>
                            <p class="text-muted mb-0">Valor Investido</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros e Lista de Agendamentos -->
            <div class="card shadow-sm">
                <div class="card-header bg-gradient-dark text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtros e Agendamentos</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#filtrosCollapse">
                                <i class="bi bi-funnel me-1"></i>Filtros
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Filtros colapsáveis -->
                <div class="collapse" id="filtrosCollapse">
                    <div class="card-body border-bottom">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="todos" <?php echo $status_filtro === 'todos' ? 'selected' : ''; ?>>Todos os Status</option>
                                    <option value="agendado" <?php echo $status_filtro === 'agendado' ? 'selected' : ''; ?>>Agendado</option>
                                    <option value="confirmado" <?php echo $status_filtro === 'confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                                    <option value="concluido" <?php echo $status_filtro === 'concluido' ? 'selected' : ''; ?>>Concluído</option>
                                    <option value="cancelado" <?php echo $status_filtro === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Período</label>
                                <select name="periodo" class="form-select">
                                    <option value="todos" <?php echo $periodo_filtro === 'todos' ? 'selected' : ''; ?>>Todos os Períodos</option>
                                    <option value="proximos" <?php echo $periodo_filtro === 'proximos' ? 'selected' : ''; ?>>Próximos</option>
                                    <option value="mes_atual" <?php echo $periodo_filtro === 'mes_atual' ? 'selected' : ''; ?>>Mês Atual</option>
                                    <option value="mes_passado" <?php echo $periodo_filtro === 'mes_passado' ? 'selected' : ''; ?>>Mês Passado</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-search me-1"></i>Filtrar
                                </button>
                                <a href="meus_agendamentos.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Limpar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card-body p-0">
                    <?php if (!empty($agendamentos)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Serviço</th>
                                    <th>Profissional</th>
                                    <th>Status</th>
                                    <th>Valor</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agendamentos as $agendamento): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <div class="fw-bold"><?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?></div>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($agendamento['servico_nome']); ?></div>
                                            <small class="text-muted"><?php echo $agendamento['duracao']; ?> minutos</small>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($agendamento['profissional_nome']); ?></td>
                                    <td>
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
                                    </td>
                                    <td>
                                        <span class="badge bg-success">R$ <?php echo number_format($agendamento['valor'], 2, ',', '.'); ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-primary" title="Ver Detalhes"
                                                    data-bs-toggle="modal" data-bs-target="#detalhesModal"
                                                    data-agendamento='<?php echo json_encode($agendamento); ?>'>
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            
                                            <?php 
                                            $pode_cancelar = ($agendamento['status'] === 'agendado' || $agendamento['status'] === 'confirmado') 
                                                           && strtotime($agendamento['data_agendamento'] . ' ' . $agendamento['hora_agendamento']) > strtotime('+24 hours');
                                            ?>
                                            
                                            <?php if ($pode_cancelar): ?>
                                            <button class="btn btn-sm btn-outline-danger" title="Cancelar"
                                                    data-bs-toggle="modal" data-bs-target="#cancelarModal"
                                                    data-id="<?php echo $agendamento['id_agendamento']; ?>"
                                                    data-servico="<?php echo htmlspecialchars($agendamento['servico_nome']); ?>"
                                                    data-data="<?php echo date('d/m/Y H:i', strtotime($agendamento['data_agendamento'] . ' ' . $agendamento['hora_agendamento'])); ?>">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x display-1 text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum agendamento encontrado</h5>
                        <p class="text-muted mb-4">
                            <?php if ($status_filtro !== 'todos' || $periodo_filtro !== 'todos'): ?>
                                Tente ajustar os filtros ou
                            <?php endif; ?>
                            Comece criando seu primeiro agendamento!
                        </p>
                        <a href="cliente_agendar.php" class="btn btn-custom">
                            <i class="bi bi-plus-circle me-2"></i>Agendar Serviço
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalhes -->
<div class="modal fade" id="detalhesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-dark text-white">
                <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>Detalhes do Agendamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3" id="detalhes-content">
                    <!-- Conteúdo será preenchido via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Cancelamento -->
<div class="modal fade" id="cancelarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Cancelar Agendamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_agendamento" id="id_agendamento_cancelar">
                    <div class="alert alert-warning">
                        <h6><i class="bi bi-info-circle me-2"></i>Informações importantes:</h6>
                        <ul class="mb-0">
                            <li>O cancelamento só é permitido com 24h de antecedência</li>
                            <li>Após confirmar, o agendamento não poderá ser revertido</li>
                            <li>Para reagendar, você precisará criar um novo agendamento</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Agendamento:</label>
                        <div class="p-2 bg-light rounded">
                            <div id="info_cancelamento"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Motivo do Cancelamento *</label>
                        <select name="motivo_cancelamento" class="form-select" required>
                            <option value="">Selecione um motivo...</option>
                            <option value="Imprevisto pessoal">Imprevisto pessoal</option>
                            <option value="Problema de saúde">Problema de saúde</option>
                            <option value="Conflito de horário">Conflito de horário</option>
                            <option value="Viagem">Viagem</option>
                            <option value="Reagendamento necessário">Reagendamento necessário</option>
                            <option value="Outro">Outro motivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-arrow-left me-1"></i>Manter Agendamento
                    </button>
                    <button type="submit" name="cancelar_agendamento" class="btn btn-warning">
                        <i class="bi bi-x-circle me-1"></i>Confirmar Cancelamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Estilos -->
<style>
.bg-gradient-dark {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
}

.card {
    border: none;
    border-radius: 10px;
}

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

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75em;
}

.display-4 {
    font-size: 2.5rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
}

.fs-6 {
    font-size: 0.9rem !important;
}
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal de detalhes
    const detalhesModal = document.getElementById('detalhesModal');
    if (detalhesModal) {
        detalhesModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const agendamento = JSON.parse(button.getAttribute('data-agendamento'));
            
            const statusLabels = {
                'agendado': 'Agendado',
                'confirmado': 'Confirmado',
                'concluido': 'Concluído',
                'cancelado': 'Cancelado'
            };
            
            const statusColors = {
                'agendado': 'warning',
                'confirmado': 'info',
                'concluido': 'success',
                'cancelado': 'danger'
            };
            
            const dataFormatada = new Date(agendamento.data_agendamento + 'T00:00:00').toLocaleDateString('pt-BR');
            const horaFormatada = agendamento.hora_agendamento.substring(0, 5);
            
            const content = `
                <div class="col-md-6">
                    <label class="form-label fw-bold">Serviço:</label>
                    <p class="mb-2">${agendamento.servico_nome}</p>
                    
                    <label class="form-label fw-bold">Profissional:</label>
                    <p class="mb-2">${agendamento.profissional_nome}</p>
                    
                    <label class="form-label fw-bold">Data:</label>
                    <p class="mb-2">${dataFormatada}</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Horário:</label>
                    <p class="mb-2">${horaFormatada}</p>
                    
                    <label class="form-label fw-bold">Duração:</label>
                    <p class="mb-2">${agendamento.duracao} minutos</p>
                    
                    <label class="form-label fw-bold">Valor:</label>
                    <p class="mb-2">R$ ${parseFloat(agendamento.valor).toFixed(2).replace('.', ',')}</p>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Status:</label>
                    <p class="mb-2">
                        <span class="badge bg-${statusColors[agendamento.status]} fs-6">
                            ${statusLabels[agendamento.status]}
                        </span>
                    </p>
                    
                    ${agendamento.observacoes ? `
                        <label class="form-label fw-bold">Observações:</label>
                        <p class="mb-2">${agendamento.observacoes}</p>
                    ` : ''}
                    
                    ${agendamento.motivo_cancelamento ? `
                        <label class="form-label fw-bold">Motivo do Cancelamento:</label>
                        <p class="mb-2">${agendamento.motivo_cancelamento}</p>
                    ` : ''}
                    
                    <label class="form-label fw-bold">Data de Criação:</label>
                    <p class="mb-0">${new Date(agendamento.data_criacao).toLocaleString('pt-BR')}</p>
                </div>
            `;
            
            document.getElementById('detalhes-content').innerHTML = content;
        });
    }

    // Modal de cancelamento
    const cancelarModal = document.getElementById('cancelarModal');
    if (cancelarModal) {
        cancelarModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const servico = button.getAttribute('data-servico');
            const data = button.getAttribute('data-data');
            
            document.getElementById('id_agendamento_cancelar').value = id;
            document.getElementById('info_cancelamento').innerHTML = `
                <strong>Serviço:</strong> ${servico}<br>
                <strong>Data/Hora:</strong> ${data}
            `;
        });
    }

    // Auto-fechar alertas
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>

<!-- Bootstrap JS -->


<!-- Adiciona espaço no final da página -->
<div class="mb-5"></div>
