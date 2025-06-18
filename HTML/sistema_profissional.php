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

// Função para buscar estatísticas do dashboard do profissional
function getDashboardStatsProfissional($pdo, $profissional_id) {
    $stats = [];
    
    // Total de agendamentos hoje
    $sql = "SELECT COUNT(*) as total FROM agendamentos WHERE profissional_id = ? AND DATE(data_agendamento) = CURDATE()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$profissional_id]);
    $stats['agendamentos_hoje'] = $stmt->fetch()['total'];
    
    // Total de agendamentos este mês
    $sql = "SELECT COUNT(*) as total FROM agendamentos WHERE profissional_id = ? AND MONTH(data_agendamento) = MONTH(CURRENT_DATE()) AND YEAR(data_agendamento) = YEAR(CURRENT_DATE())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$profissional_id]);
    $stats['agendamentos_mes'] = $stmt->fetch()['total'];
    
    // Faturamento este mês
    $sql = "SELECT SUM(valor) as total FROM agendamentos WHERE profissional_id = ? AND status = 'concluido' AND MONTH(data_agendamento) = MONTH(CURRENT_DATE()) AND YEAR(data_agendamento) = YEAR(CURRENT_DATE())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$profissional_id]);
    $stats['faturamento_mes'] = $stmt->fetch()['total'] ?? 0;
    
    // Faturamento hoje
    $sql = "SELECT SUM(valor) as total FROM agendamentos WHERE profissional_id = ? AND status = 'concluido' AND DATE(data_agendamento) = CURDATE()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$profissional_id]);
    $stats['faturamento_hoje'] = $stmt->fetch()['total'] ?? 0;
    
    // Total de clientes únicos
    $sql = "SELECT COUNT(DISTINCT cliente_id) as total FROM agendamentos WHERE profissional_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$profissional_id]);
    $stats['clientes_unicos'] = $stmt->fetch()['total'];
    
    // Novos clientes este mês
    $sql = "SELECT COUNT(DISTINCT a.cliente_id) as total FROM agendamentos a WHERE a.profissional_id = ? AND MONTH(a.data_agendamento) = MONTH(CURRENT_DATE()) AND YEAR(a.data_agendamento) = YEAR(CURRENT_DATE())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$profissional_id]);
    $stats['novos_clientes_mes'] = $stmt->fetch()['total'];
    
    // Média de avaliações
    $sql = "SELECT AVG(f.avaliacao) as media FROM feedback f INNER JOIN agendamentos a ON f.agendamento_id = a.id_agendamento WHERE a.profissional_id = ? AND f.avaliacao > 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$profissional_id]);
    $stats['media_avaliacoes'] = round($stmt->fetch()['media'] ?? 0, 1);
    
    // Total de serviços que o profissional oferece
    $sql = "SELECT COUNT(DISTINCT servico_id) as total FROM agendamentos WHERE profissional_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$profissional_id]);
    $stats['servicos_oferecidos'] = $stmt->fetch()['total'];
    
    // Agendamentos por status
    $sql = "SELECT status, COUNT(*) as total FROM agendamentos WHERE profissional_id = ? AND MONTH(data_agendamento) = MONTH(CURRENT_DATE()) AND YEAR(data_agendamento) = YEAR(CURRENT_DATE()) GROUP BY status";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$profissional_id]);
    $stats['agendamentos_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Próximos agendamentos (hoje e amanhã)
    $sql = "SELECT a.*, u.nome as cliente_nome, s.nome as servico_nome
            FROM agendamentos a
            JOIN usuario u ON a.cliente_id = u.id_usuario
            JOIN servicos s ON a.servico_id = s.id_servico
            WHERE a.profissional_id = ? AND a.data_agendamento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            AND a.status = 'agendado'
            ORDER BY a.data_agendamento, a.hora_agendamento
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$profissional_id]);
    $stats['proximos_agendamentos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Serviços mais procurados este mês
    $sql = "SELECT s.nome, COUNT(*) as total, SUM(a.valor) as receita
            FROM agendamentos a
            JOIN servicos s ON a.servico_id = s.id_servico
            WHERE a.profissional_id = ? AND MONTH(a.data_agendamento) = MONTH(CURRENT_DATE()) 
            AND YEAR(a.data_agendamento) = YEAR(CURRENT_DATE())
            GROUP BY s.id_servico, s.nome
            ORDER BY total DESC
            LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$profissional_id]);
    $stats['servicos_populares'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agendamentos dos últimos 7 dias
    $sql = "SELECT DATE(data_agendamento) as data, COUNT(*) as total 
            FROM agendamentos 
            WHERE profissional_id = ? AND data_agendamento >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(data_agendamento)
            ORDER BY data";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$profissional_id]);
    $stats['agendamentos_7_dias'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $stats;
}

$stats = getDashboardStatsProfissional($pdo, $profissional_id);

include_once('topo_sistema_profissional.php');
?>

<!-- Incluir CSS personalizado -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0"><i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard Profissional</h2>
                    <p class="text-muted mb-0">Olá, <?php echo htmlspecialchars($_SESSION['nome']); ?>! Bem-vindo de volta.</p>
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
                            <small class="text-primary">
                                <i class="bi bi-calendar-day"></i> 
                                Para hoje
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
                            <h3 class="mb-1">R$ <?php echo number_format($stats['faturamento_mes'], 2, ',', '.'); ?></h3>
                            <p class="text-muted mb-0">Faturamento do Mês</p>
                            <small class="text-success">
                                <i class="bi bi-trending-up"></i> 
                                Receita mensal
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
                            <h3 class="mb-1"><?php echo $stats['clientes_unicos']; ?></h3>
                            <p class="text-muted mb-0">Clientes Únicos</p>
                            <small class="text-info">
                                <i class="bi bi-person-check"></i> 
                                Base de clientes
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100 card-hover">
                        <div class="card-body text-center">
                            <div class="text-warning mb-2">
                                <i class="bi bi-star-fill display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['media_avaliacoes']; ?></h3>
                            <p class="text-muted mb-0">Avaliação Média</p>
                            <small class="text-warning">
                                <i class="bi bi-star"></i> 
                                De 5 estrelas
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Próximos Agendamentos -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-clock me-2"></i>Próximos Agendamentos</h5>
                                <a href="agenda_profissional.php" class="btn btn-light btn-sm">
                                    Ver agenda
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($stats['proximos_agendamentos'])): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($stats['proximos_agendamentos'], 0, 5) as $agendamento): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($agendamento['cliente_nome']); ?></h6>
                                                <p class="mb-1"><?php echo htmlspecialchars($agendamento['servico_nome']); ?></p>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar me-1"></i>
                                                    <?php echo date('d/m', strtotime($agendamento['data_agendamento'])); ?> às 
                                                    <?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-primary">R$ <?php echo number_format($agendamento['valor'], 2, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-calendar-x display-1 text-muted"></i>
                                    <h6 class="text-muted mt-2">Nenhum agendamento próximo</h6>
                                    <p class="text-muted">Seus próximos agendamentos aparecerão aqui!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Serviços Populares -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-fire me-2"></i>Serviços Mais Procurados</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($stats['servicos_populares'])): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Serviço</th>
                                                <th>Agendamentos</th>
                                                <th>Receita</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats['servicos_populares'] as $servico): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($servico['nome']); ?></td>
                                                <td><span class="badge bg-info"><?php echo $servico['total']; ?></span></td>
                                                <td><span class="badge bg-success">R$ <?php echo number_format($servico['receita'], 2, ',', '.'); ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-scissors display-1 text-muted"></i>
                                    <h6 class="text-muted mt-2">Nenhum serviço realizado ainda</h6>
                                </div>
                            <?php endif; ?>
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
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.bg-gradient-danger {
    background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
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
        'cancelado': 'Cancelado',
        'confirmado': 'Confirmado'
    };
    return labels[item.status] || item.status;
});

const statusChart = new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusData.map(item => item.total),
            backgroundColor: ['#ffc107', '#28a745', '#dc3545', '#17a2b8'],
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
