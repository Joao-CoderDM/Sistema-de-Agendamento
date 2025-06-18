<?php
session_start();

// Verifica se o usuário está logado e é admin
if(!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'Administrador'){
    header('Location: login.php');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
include_once('../Conexao/conexao.php');

// Definir período padrão (últimos 30 dias)
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$tipo_relatorio = isset($_GET['tipo']) ? $_GET['tipo'] : 'geral';

// Função para buscar dados do período
function getDadosPeriodo($pdo, $data_inicio, $data_fim) {
    $dados = [];
    
    try {
        // Total de agendamentos
        $sql = "SELECT COUNT(*) as total FROM agendamentos WHERE data_agendamento BETWEEN ? AND ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data_inicio, $data_fim]);
        $dados['total_agendamentos'] = $stmt->fetch()['total'];
        
        // Agendamentos por status
        $sql = "SELECT status, COUNT(*) as total FROM agendamentos WHERE data_agendamento BETWEEN ? AND ? GROUP BY status";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data_inicio, $data_fim]);
        $dados['agendamentos_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Faturamento total
        $sql = "SELECT SUM(valor) as faturamento FROM agendamentos WHERE data_agendamento BETWEEN ? AND ? AND status = 'concluido'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data_inicio, $data_fim]);
        $dados['faturamento_total'] = $stmt->fetch()['faturamento'] ?? 0;
        
        // Serviços mais procurados
        $sql = "SELECT s.nome, COUNT(*) as total, SUM(a.valor) as receita 
                FROM agendamentos a 
                JOIN servicos s ON a.servico_id = s.id_servico 
                WHERE a.data_agendamento BETWEEN ? AND ? 
                GROUP BY s.id_servico, s.nome 
                ORDER BY total DESC LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data_inicio, $data_fim]);
        $dados['servicos_populares'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Profissionais mais ativos
        $sql = "SELECT u.nome, COUNT(*) as agendamentos, SUM(a.valor) as receita 
                FROM agendamentos a 
                JOIN usuario u ON a.profissional_id = u.id_usuario 
                WHERE a.data_agendamento BETWEEN ? AND ? 
                GROUP BY u.id_usuario, u.nome 
                ORDER BY agendamentos DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data_inicio, $data_fim]);
        $dados['profissionais_ativos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Novos clientes
        $sql = "SELECT COUNT(DISTINCT u.id_usuario) as novos_clientes 
                FROM agendamentos a 
                JOIN usuario u ON a.cliente_id = u.id_usuario 
                WHERE a.data_agendamento BETWEEN ? AND ? 
                AND u.data_cadastro BETWEEN ? AND ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data_inicio, $data_fim, $data_inicio, $data_fim]);
        $dados['novos_clientes'] = $stmt->fetch()['novos_clientes'];
        
        // Média de avaliações
        $sql = "SELECT AVG(f.avaliacao) as media_avaliacoes, COUNT(*) as total_avaliacoes 
                FROM feedback f 
                WHERE f.data_criacao BETWEEN ? AND ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data_inicio, $data_fim]);
        $result = $stmt->fetch();
        $dados['media_avaliacoes'] = round($result['media_avaliacoes'] ?? 0, 1);
        $dados['total_avaliacoes'] = $result['total_avaliacoes'];
        
        // Agendamentos por dia
        $sql = "SELECT DATE(data_agendamento) as data, COUNT(*) as total 
                FROM agendamentos 
                WHERE data_agendamento BETWEEN ? AND ? 
                GROUP BY DATE(data_agendamento) 
                ORDER BY data";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data_inicio, $data_fim]);
        $dados['agendamentos_diarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Dados para gráfico de faturamento semanal
        $sql = "SELECT 
                    YEARWEEK(data_agendamento) as semana,
                    CONCAT('Semana ', WEEK(data_agendamento, 1)) as label_semana,
                    SUM(CASE WHEN status = 'concluido' THEN valor ELSE 0 END) as faturamento,
                    COUNT(*) as total_agendamentos
                FROM agendamentos 
                WHERE data_agendamento BETWEEN ? AND ?
                GROUP BY YEARWEEK(data_agendamento)
                ORDER BY semana";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data_inicio, $data_fim]);
        $dados['faturamento_semanal'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Agendamentos por dia da semana
        $sql = "SELECT 
                    CASE DAYOFWEEK(data_agendamento)
                        WHEN 1 THEN 'Domingo'
                        WHEN 2 THEN 'Segunda'
                        WHEN 3 THEN 'Terça'
                        WHEN 4 THEN 'Quarta'
                        WHEN 5 THEN 'Quinta'
                        WHEN 6 THEN 'Sexta'
                        WHEN 7 THEN 'Sábado'
                    END as dia_semana,
                    COUNT(*) as total
                FROM agendamentos 
                WHERE data_agendamento BETWEEN ? AND ?
                GROUP BY DAYOFWEEK(data_agendamento)
                ORDER BY DAYOFWEEK(data_agendamento)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data_inicio, $data_fim]);
        $dados['agendamentos_por_dia_semana'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Erro ao buscar dados dos relatórios: " . $e->getMessage());
        // Retornar dados vazios em caso de erro
        $dados = [
            'total_agendamentos' => 0,
            'agendamentos_status' => [],
            'faturamento_total' => 0,
            'servicos_populares' => [],
            'profissionais_ativos' => [],
            'novos_clientes' => 0,
            'media_avaliacoes' => 0,
            'total_avaliacoes' => 0,
            'agendamentos_diarios' => [],
            'faturamento_semanal' => [],
            'agendamentos_por_dia_semana' => []
        ];
    }
    
    return $dados;
}

$dados = getDadosPeriodo($pdo, $data_inicio, $data_fim);

include_once('topo_sistema_adm.php');
?>

<!-- Incluir CSS personalizado -->
<link rel="stylesheet" href="../CSS/admin_relatorios.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0"><i class="bi bi-graph-up text-primary me-2"></i>Relatórios e Estatísticas</h2>
                    <p class="text-muted mb-0">Análise de desempenho da barbearia</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="bi bi-calendar-range"></i> Período
                    </button>
                    <button class="btn btn-success btn-sm" onclick="exportarRelatorio()">
                        <i class="bi bi-download"></i> Exportar
                    </button>
                </div>
            </div>
            
            <!-- Filtros aplicados -->
            <div class="card mb-4">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center gap-3">
                        <span class="fw-bold text-muted">Período:</span>
                        <span class="badge bg-info fs-6">
                            <i class="bi bi-calendar me-1"></i>
                            <?php echo date('d/m/Y', strtotime($data_inicio)); ?> até <?php echo date('d/m/Y', strtotime($data_fim)); ?>
                        </span>
                        <span class="text-muted">|</span>
                        <span class="text-muted">
                            <?php 
                            $diff = (strtotime($data_fim) - strtotime($data_inicio)) / 86400 + 1;
                            echo $diff . ' dia' . ($diff > 1 ? 's' : '');
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Cards de resumo -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-primary mb-2">
                                <i class="bi bi-calendar-check display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $dados['total_agendamentos']; ?></h3>
                            <p class="text-muted mb-0">Total de Agendamentos</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-success mb-2">
                                <i class="bi bi-currency-dollar display-4"></i>
                            </div>
                            <h3 class="mb-1">R$ <?php echo number_format($dados['faturamento_total'], 2, ',', '.'); ?></h3>
                            <p class="text-muted mb-0">Faturamento</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-info mb-2">
                                <i class="bi bi-people display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $dados['novos_clientes']; ?></h3>
                            <p class="text-muted mb-0">Novos Clientes</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-warning mb-2">
                                <i class="bi bi-star display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $dados['media_avaliacoes']; ?></h3>
                            <p class="text-muted mb-0">Avaliação Média</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Navegação por abas -->
            <ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                        <i class="bi bi-graph-up me-1"></i> Visão Geral
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="services-tab" data-bs-toggle="tab" data-bs-target="#services" type="button" role="tab">
                        <i class="bi bi-scissors me-1"></i> Serviços
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="professionals-tab" data-bs-toggle="tab" data-bs-target="#professionals" type="button" role="tab">
                        <i class="bi bi-people me-1"></i> Profissionais
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button" role="tab">
                        <i class="bi bi-cash me-1"></i> Financeiro
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="reportTabsContent">
                <!-- Aba Visão Geral -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <div class="row">
                        <!-- Gráfico de agendamentos por status -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-gradient-dark text-white">
                                    <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Agendamentos por Status</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="statusChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Gráfico de agendamentos por dia -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-gradient-dark text-white">
                                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Agendamentos por Dia</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="dailyChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Aba Serviços -->
                <div class="tab-pane fade" id="services" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-scissors me-2"></i>Serviços Mais Procurados</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Posição</th>
                                            <th>Serviço</th>
                                            <th>Quantidade</th>
                                            <th>Receita</th>
                                            <th>Ticket Médio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($dados['servicos_populares'])): ?>
                                            <?php foreach ($dados['servicos_populares'] as $index => $servico): ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <span class="badge bg-primary"><?php echo $index + 1; ?>º</span>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($servico['nome']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $servico['total']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">R$ <?php echo number_format($servico['receita'], 2, ',', '.'); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning">R$ <?php echo number_format($servico['receita'] / $servico['total'], 2, ',', '.'); ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <p class="text-muted">Nenhum serviço encontrado no período</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Aba Profissionais -->
                <div class="tab-pane fade" id="professionals" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-people me-2"></i>Desempenho dos Profissionais</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Profissional</th>
                                            <th>Agendamentos</th>
                                            <th>Receita</th>
                                            <th>Ticket Médio</th>
                                            <th>Performance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($dados['profissionais_ativos'])): ?>
                                            <?php 
                                            $max_agendamentos = max(array_column($dados['profissionais_ativos'], 'agendamentos'));
                                            foreach ($dados['profissionais_ativos'] as $profissional): 
                                            $performance = $max_agendamentos > 0 ? ($profissional['agendamentos'] / $max_agendamentos) * 100 : 0;
                                            ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <strong><?php echo htmlspecialchars($profissional['nome']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $profissional['agendamentos']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">R$ <?php echo number_format($profissional['receita'], 2, ',', '.'); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning">R$ <?php echo number_format($profissional['receita'] / $profissional['agendamentos'], 2, ',', '.'); ?></span>
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-primary" role="progressbar" 
                                                             style="width: <?php echo $performance; ?>%" 
                                                             aria-valuenow="<?php echo $performance; ?>" aria-valuemin="0" aria-valuemax="100">
                                                            <?php echo round($performance); ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <p class="text-muted">Nenhum profissional ativo no período</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Aba Financeiro -->
                <div class="tab-pane fade" id="financial" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-gradient-dark text-white">
                                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Evolução da Receita</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="revenueChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-gradient-dark text-white">
                                    <h5 class="mb-0"><i class="bi bi-calculator me-2"></i>Resumo Financeiro</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-3">
                                        <span>Faturamento Total:</span>
                                        <strong class="text-success">R$ <?php echo number_format($dados['faturamento_total'], 2, ',', '.'); ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span>Ticket Médio:</span>
                                        <strong class="text-info">
                                            R$ <?php echo $dados['total_agendamentos'] > 0 ? number_format($dados['faturamento_total'] / $dados['total_agendamentos'], 2, ',', '.') : '0,00'; ?>
                                        </strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span>Faturamento/Dia:</span>
                                        <strong class="text-warning">
                                            R$ <?php 
                                            $dias = (strtotime($data_fim) - strtotime($data_inicio)) / 86400 + 1;
                                            echo number_format($dados['faturamento_total'] / $dias, 2, ',', '.'); 
                                            ?>
                                        </strong>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <span>Total de Avaliações:</span>
                                        <strong><?php echo $dados['total_avaliacoes']; ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para filtro de período -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title" id="filterModalLabel">
                    <i class="bi bi-calendar-range me-2"></i>Selecionar Período
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="get" action="admin_relatorios.php">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="data_inicio" class="form-label">Data Início</label>
                            <input type="date" class="form-control" id="data_inicio" name="data_inicio" 
                                   value="<?php echo $data_inicio; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="data_fim" class="form-label">Data Fim</label>
                            <input type="date" class="form-control" id="data_fim" name="data_fim" 
                                   value="<?php echo $data_fim; ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label">Períodos rápidos:</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickPeriod(7)">7 dias</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickPeriod(30)">30 dias</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickPeriod(90)">3 meses</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setCurrentMonth()">Mês atual</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-check-circle me-1"></i>Aplicar Período
                    </button>
                </div>
            </form>
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

.card {
    border: none;
    border-radius: 10px;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75em;
}

.nav-tabs .nav-link {
    border: none;
    color: #495057;
    font-weight: 500;
}

.nav-tabs .nav-link.active {
    color: #007bff;
    border-bottom: 2px solid #007bff;
    background-color: transparent;
}

.progress {
    border-radius: 10px;
}
</style>

<script>
// Dados para os gráficos
const statusData = <?php echo json_encode($dados['agendamentos_status']); ?>;
const dailyData = <?php echo json_encode($dados['agendamentos_diarios']); ?>;
const weeklyRevenueData = <?php echo json_encode($dados['faturamento_semanal']); ?>;
const weekdayData = <?php echo json_encode($dados['agendamentos_por_dia_semana']); ?>;

// Dados para variáveis JavaScript necessárias
const semanasDados = <?php echo json_encode(array_column($dados['faturamento_semanal'], 'label_semana')); ?>;
const valoresSemanaisDados = <?php echo json_encode(array_column($dados['faturamento_semanal'], 'faturamento')); ?>;
const servicosDados = <?php echo json_encode(array_column(array_slice($dados['servicos_populares'], 0, 5), 'nome')); ?>;
const contagemServicosDados = <?php echo json_encode(array_column(array_slice($dados['servicos_populares'], 0, 5), 'total')); ?>;
const diasDados = <?php echo json_encode(array_column($dados['agendamentos_por_dia_semana'], 'dia_semana')); ?>;
const contagemDiasDados = <?php echo json_encode(array_column($dados['agendamentos_por_dia_semana'], 'total')); ?>;
const profissionaisDados = <?php echo json_encode(array_column(array_slice($dados['profissionais_ativos'], 0, 4), 'nome')); ?>;
const atendimentosProfissionaisDados = <?php echo json_encode(array_column(array_slice($dados['profissionais_ativos'], 0, 4), 'agendamentos')); ?>;

// Gráfico de status
if (document.getElementById('statusChart')) {
    const statusChart = new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusData.map(item => {
                const labels = {'agendado': 'Agendado', 'confirmado': 'Confirmado', 'concluido': 'Concluído', 'cancelado': 'Cancelado'};
                return labels[item.status] || item.status;
            }),
            datasets: [{
                data: statusData.map(item => item.total),
                backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Gráfico diário
if (document.getElementById('dailyChart')) {
    const dailyChart = new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: {
            labels: dailyData.map(item => {
                const date = new Date(item.data + 'T00:00:00');
                return date.toLocaleDateString('pt-BR');
            }),
            datasets: [{
                label: 'Agendamentos',
                data: dailyData.map(item => item.total),
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Gráfico de receita
if (document.getElementById('revenueChart')) {
    const revenueChart = new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: weeklyRevenueData.map(item => item.label_semana),
            datasets: [{
                label: 'Faturamento (R$)',
                data: weeklyRevenueData.map(item => parseFloat(item.faturamento)),
                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                borderColor: '#28a745',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Faturamento: R$ ' + context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                        }
                    }
                }
            }
        }
    });
}

// Funções para períodos rápidos
function setQuickPeriod(days) {
    const hoje = new Date();
    const inicio = new Date(hoje.getTime() - (days * 24 * 60 * 60 * 1000));
    
    document.getElementById('data_inicio').value = inicio.toISOString().split('T')[0];
    document.getElementById('data_fim').value = hoje.toISOString().split('T')[0];
}

function setCurrentMonth() {
    const hoje = new Date();
    const inicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
    
    document.getElementById('data_inicio').value = inicio.toISOString().split('T')[0];
    document.getElementById('data_fim').value = hoje.toISOString().split('T')[0];
}

// Função para exportar relatório
function exportarRelatorio() {
    const dataInicio = '<?php echo $data_inicio; ?>';
    const dataFim = '<?php echo $data_fim; ?>';
    
    // Criar conteúdo do relatório
    let conteudo = `RELATÓRIO DE DESEMPENHO - BARBEARIA\n`;
    conteudo += `Período: ${dataInicio} até ${dataFim}\n`;
    conteudo += `Gerado em: ${new Date().toLocaleString('pt-BR')}\n\n`;
    
    conteudo += `RESUMO GERAL\n`;
    conteudo += `Total de Agendamentos: <?php echo $dados['total_agendamentos']; ?>\n`;
    conteudo += `Faturamento Total: R$ <?php echo number_format($dados['faturamento_total'], 2, ',', '.'); ?>\n`;
    conteudo += `Novos Clientes: <?php echo $dados['novos_clientes']; ?>\n`;
    conteudo += `Avaliação Média: <?php echo $dados['media_avaliacoes']; ?>\n\n`;
    
    conteudo += `SERVIÇOS MAIS PROCURADOS\n`;
    <?php foreach ($dados['servicos_populares'] as $index => $servico): ?>
    conteudo += `${<?php echo $index + 1; ?>}. <?php echo addslashes($servico['nome']); ?> - <?php echo $servico['total']; ?> agendamentos\n`;
    <?php endforeach; ?>
    
    conteudo += `\nPROFISSIONAIS MAIS ATIVOS\n`;
    <?php foreach ($dados['profissionais_ativos'] as $index => $profissional): ?>
    conteudo += `${<?php echo $index + 1; ?>}. <?php echo addslashes($profissional['nome']); ?> - <?php echo $profissional['agendamentos']; ?> agendamentos\n`;
    <?php endforeach; ?>
    
    // Criar e baixar arquivo
    const blob = new Blob([conteudo], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `relatorio_${dataInicio}_${dataFim}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Adiciona espaço no final da página -->
<div class="mb-5"></div>