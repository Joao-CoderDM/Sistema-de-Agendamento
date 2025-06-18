<?php
session_start();

// Verifica se o usuário está logado e é admin
if(!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'Administrador'){
    header('Location: login.php');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
include_once('../Conexao/conexao.php');

// Função para buscar estatísticas do dashboard
function getDashboardStats($pdo) {
    $stats = [];
    
    // Total de agendamentos hoje
    $sql = "SELECT COUNT(*) as total FROM agendamentos WHERE DATE(data_agendamento) = CURDATE()";
    $stmt = $pdo->query($sql);
    $stats['agendamentos_hoje'] = $stmt->fetch()['total'];
    
    // Total de agendamentos este mês
    $sql = "SELECT COUNT(*) as total FROM agendamentos WHERE MONTH(data_agendamento) = MONTH(CURRENT_DATE()) AND YEAR(data_agendamento) = YEAR(CURRENT_DATE())";
    $stmt = $pdo->query($sql);
    $stats['agendamentos_mes'] = $stmt->fetch()['total'];
    
    // Faturamento este mês
    $sql = "SELECT SUM(valor) as total FROM agendamentos WHERE status = 'concluido' AND MONTH(data_agendamento) = MONTH(CURRENT_DATE()) AND YEAR(data_agendamento) = YEAR(CURRENT_DATE())";
    $stmt = $pdo->query($sql);
    $stats['faturamento_mes'] = $stmt->fetch()['total'] ?? 0;
    
    // Faturamento hoje
    $sql = "SELECT SUM(valor) as total FROM agendamentos WHERE status = 'concluido' AND DATE(data_agendamento) = CURDATE()";
    $stmt = $pdo->query($sql);
    $stats['faturamento_hoje'] = $stmt->fetch()['total'] ?? 0;
    
    // Total de clientes ativos
    $sql = "SELECT COUNT(*) as total FROM usuario WHERE tipo_usuario = 'Cliente' AND ativo = 1";
    $stmt = $pdo->query($sql);
    $stats['clientes_ativos'] = $stmt->fetch()['total'];
    
    // Total de profissionais ativos
    $sql = "SELECT COUNT(*) as total FROM usuario WHERE tipo_usuario = 'Profissional' AND ativo = 1";
    $stmt = $pdo->query($sql);
    $stats['profissionais_ativos'] = $stmt->fetch()['total'];
    
    // Total de serviços ativos
    $sql = "SELECT COUNT(*) as total FROM servicos WHERE ativo = 1";
    $stmt = $pdo->query($sql);
    $stats['servicos_ativos'] = $stmt->fetch()['total'];
    
    // Média de avaliações
    $sql = "SELECT AVG(avaliacao) as media FROM feedback WHERE avaliacao > 0";
    $stmt = $pdo->query($sql);
    $stats['media_avaliacoes'] = round($stmt->fetch()['media'] ?? 0, 1);
    
    // Agendamentos por status (corrigir para retornar valores individuais)
    $sql = "SELECT status, COUNT(*) as total FROM agendamentos WHERE MONTH(data_agendamento) = MONTH(CURRENT_DATE()) AND YEAR(data_agendamento) = YEAR(CURRENT_DATE()) GROUP BY status";
    $stmt = $pdo->query($sql);
    $agendamentos_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Criar array associativo para facilitar o acesso
    $stats['agendamentos_status'] = [];
    $stats['agendamentos_concluidos'] = 0;
    $stats['agendamentos_agendados'] = 0;
    $stats['agendamentos_cancelados'] = 0;
    $stats['agendamentos_confirmados'] = 0;
    
    foreach ($agendamentos_status as $status_data) {
        $stats['agendamentos_status'][] = $status_data;
        
        // Mapear valores individuais para acesso fácil
        switch($status_data['status']) {
            case 'concluido':
                $stats['agendamentos_concluidos'] = $status_data['total'];
                break;
            case 'agendado':
                $stats['agendamentos_agendados'] = $status_data['total'];
                break;
            case 'cancelado':
                $stats['agendamentos_cancelados'] = $status_data['total'];
                break;
            case 'confirmado':
                $stats['agendamentos_confirmados'] = $status_data['total'];
                break;
        }
    }
    
    // Próximos agendamentos (hoje e amanhã)
    $sql = "SELECT a.*, u.nome as cliente_nome, s.nome as servico_nome, p.nome as profissional_nome
            FROM agendamentos a
            JOIN usuario u ON a.cliente_id = u.id_usuario
            JOIN servicos s ON a.servico_id = s.id_servico
            JOIN usuario p ON a.profissional_id = p.id_usuario
            WHERE a.data_agendamento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            AND a.status = 'agendado'
            ORDER BY a.data_agendamento, a.hora_agendamento
            LIMIT 10";
    $stmt = $pdo->query($sql);
    $stats['proximos_agendamentos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Serviços mais procurados este mês
    $sql = "SELECT s.nome, COUNT(*) as total, SUM(a.valor) as receita
            FROM agendamentos a
            JOIN servicos s ON a.servico_id = s.id_servico
            WHERE MONTH(a.data_agendamento) = MONTH(CURRENT_DATE()) 
            AND YEAR(a.data_agendamento) = YEAR(CURRENT_DATE())
            GROUP BY s.id_servico, s.nome
            ORDER BY total DESC
            LIMIT 5";
    $stmt = $pdo->query($sql);
    $stats['servicos_populares'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Novos clientes este mês
    $sql = "SELECT COUNT(*) as total FROM usuario WHERE tipo_usuario = 'Cliente' AND MONTH(data_cadastro) = MONTH(CURRENT_DATE()) AND YEAR(data_cadastro) = YEAR(CURRENT_DATE())";
    $stmt = $pdo->query($sql);
    $stats['novos_clientes_mes'] = $stmt->fetch()['total'];
    
    // Agendamentos dos últimos 7 dias
    $sql = "SELECT DATE(data_agendamento) as data, COUNT(*) as total 
            FROM agendamentos 
            WHERE data_agendamento >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(data_agendamento)
            ORDER BY data";
    $stmt = $pdo->query($sql);
    $stats['agendamentos_7_dias'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $stats;
}

$stats = getDashboardStats($pdo);

include_once('topo_sistema_adm.php');
?>

<!-- Incluir CSS personalizado -->
<link rel="stylesheet" href="../CSS/admin_dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12">
            <!-- Header compacto -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1"><i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard</h4>
                    <small class="text-muted">Visão geral do sistema da barbearia</small>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-success fs-6 px-3 py-2">
                        <i class="bi bi-clock me-1"></i><?php echo date('d/m/Y H:i'); ?>
                    </span>
                </div>
            </div>
            
            <!-- Cards de estatísticas principais -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100 card-hover">
                        <div class="card-body text-center">
                            <div class="text-primary mb-2">
                                <i class="bi bi-calendar-check display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['agendamentos_hoje']; ?></h3>
                            <p class="text-muted mb-0">Agendamentos Hoje</p>
                            <small class="text-success">
                                <i class="bi bi-calendar"></i> 
                                <?php echo $stats['agendamentos_mes']; ?> este mês
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100 card-hover">
                        <div class="card-body text-center">
                            <div class="text-success mb-2">
                                <i class="bi bi-currency-dollar display-4"></i>
                            </div>
                            <h3 class="mb-1">R$ <?php echo number_format($stats['faturamento_hoje'], 2, ',', '.'); ?></h3>
                            <p class="text-muted mb-0">Faturamento Hoje</p>
                            <small class="text-success">
                                <i class="bi bi-graph-up"></i> 
                                R$ <?php echo number_format($stats['faturamento_mes'], 2, ',', '.'); ?> este mês
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100 card-hover">
                        <div class="card-body text-center">
                            <div class="text-info mb-2">
                                <i class="bi bi-people display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['clientes_ativos']; ?></h3>
                            <p class="text-muted mb-0">Clientes Ativos</p>
                            <small class="text-info">
                                <i class="bi bi-person-plus"></i> 
                                <?php echo $stats['novos_clientes_mes']; ?> novos este mês
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100 card-hover">
                        <div class="card-body text-center">
                            <div class="text-warning mb-2">
                                <i class="bi bi-star display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['media_avaliacoes']; ?></h3>
                            <p class="text-muted mb-0">Avaliação Média</p>
                            <small class="text-warning">
                                <i class="bi bi-star-fill"></i> 
                                <?php echo $stats['servicos_ativos']; ?> serviços ativos
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Gráfico de agendamentos -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Agendamentos dos Últimos 7 Dias</h5>
                                <div class="d-flex gap-2">
                                    <span class="badge bg-light text-dark">Últimos 7 dias</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="agendamentosChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Status dos agendamentos -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Status dos Agendamentos</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="statusChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Próximos agendamentos -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-clock me-2"></i>Próximos Agendamentos</h5>
                                <a href="admin_agendamentos.php" class="btn btn-light btn-sm">
                                    Ver todos
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($stats['proximos_agendamentos'])): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($stats['proximos_agendamentos'] as $agendamento): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($agendamento['cliente_nome']); ?></h6>
                                            <p class="mb-1"><?php echo htmlspecialchars($agendamento['servico_nome']); ?></p>
                                            <small class="text-muted">
                                                <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($agendamento['profissional_nome']); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-primary">
                                                <?php echo date('d/m', strtotime($agendamento['data_agendamento'])); ?>
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-x display-1 text-muted"></i>
                                <h6 class="text-muted mt-2">Nenhum agendamento próximo</h6>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Serviços mais procurados -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-scissors me-2"></i>Serviços Populares</h5>
                                <a href="admin_relatorios.php" class="btn btn-light btn-sm">
                                    Ver relatório
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($stats['servicos_populares'])): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Serviço</th>
                                            <th>Qtd</th>
                                            <th>Receita</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['servicos_populares'] as $index => $servico): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-primary me-2"><?php echo $index + 1; ?>º</span>
                                                    <?php echo htmlspecialchars($servico['nome']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $servico['total']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">R$ <?php echo number_format($servico['receita'], 2, ',', '.'); ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-scissors display-1 text-muted"></i>
                                <h6 class="text-muted mt-2">Nenhum serviço encontrado</h6>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Estatísticas adicionais -->
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center bg-gradient-info text-white">
                        <div class="card-body">
                            <i class="bi bi-person-gear display-4 mb-2"></i>
                            <h4><?php echo $stats['profissionais_ativos']; ?></h4>
                            <p class="mb-0">Profissionais Ativos</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center bg-gradient-success text-white">
                        <div class="card-body">
                            <i class="bi bi-check-circle display-4 mb-2"></i>
                            <h4><?php echo $stats['agendamentos_concluidos']; ?></h4>
                            <p class="mb-0">Concluídos Este Mês</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center bg-gradient-warning text-white">
                        <div class="card-body">
                            <i class="bi bi-clock display-4 mb-2"></i>
                            <h4><?php echo $stats['agendamentos_agendados']; ?></h4>
                            <p class="mb-0">Agendados Este Mês</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center bg-gradient-danger text-white">
                        <div class="card-body">
                            <i class="bi bi-x-circle display-4 mb-2"></i>
                            <h4><?php echo $stats['agendamentos_cancelados']; ?></h4>
                            <p class="mb-0">Cancelados Este Mês</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-dark {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
}
.bg-gradient-info {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
}

.bg-gradient-danger {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
}

.card {
    border: none;
    border-radius: 10px;
    transition: transform 0.2s ease-in-out;
}

.card-hover:hover {
    transform: translateY(-5px);
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75em;
}

.list-group-item {
    border-left: none;
    border-right: none;
}

.list-group-item:first-child {
    border-top: none;
}

.list-group-item:last-child {
    border-bottom: none;
}

.display-4 {
    font-size: 2.5rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
}
</style>

<script>
// Dados para os gráficos
const agendamentos7Dias = <?php echo json_encode($stats['agendamentos_7_dias']); ?>;
const statusData = <?php echo json_encode($stats['agendamentos_status']); ?>;

// Preencher dados ausentes dos últimos 7 dias
const hoje = new Date();
const dadosCompletos = [];
for (let i = 6; i >= 0; i--) {
    const data = new Date(hoje);
    data.setDate(data.getDate() - i);
    const dataStr = data.toISOString().split('T')[0];
    const dataFormatada = data.toLocaleDateString('pt-BR');
    
    const dadoExistente = agendamentos7Dias.find(item => item.data === dataStr);
    dadosCompletos.push({
        data: dataFormatada,
        total: dadoExistente ? dadoExistente.total : 0
    });
}

// Gráfico de agendamentos dos últimos 7 dias
const agendamentosChart = new Chart(document.getElementById('agendamentosChart'), {
    type: 'line',
    data: {
        labels: dadosCompletos.map(item => item.data),
        datasets: [{
            label: 'Agendamentos',
            data: dadosCompletos.map(item => item.total),
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#007bff',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Gráfico de status dos agendamentos
const statusLabels = statusData.map(item => {
    const labels = {
        'agendado': 'Agendado',
        'concluido': 'Concluído',
        'cancelado': 'Cancelado'
    };
    return labels[item.status] || item.status;
});

const statusChart = new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusData.map(item => item.total),
            backgroundColor: ['#ffc107', '#28a745', '#dc3545'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        },
        cutout: '60%'
    }
});

// Atualizar dashboard a cada 5 minutos
setInterval(function() {
    location.reload();
}, 300000);
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Adiciona espaço no final da página -->
<div class="mb-5"></div>