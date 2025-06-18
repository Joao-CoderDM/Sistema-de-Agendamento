<?php
// Debug no início do arquivo
error_log("=== SISTEMA_CLIENTE.PHP DEBUG ===");

// Verificar se a sessão já foi iniciada ANTES de chamar session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    error_log("Sessão iniciada em sistema_cliente.php");
} else {
    error_log("Sessão já estava ativa - ID: " . session_id());
}

error_log("loggedin no sistema_cliente: " . (isset($_SESSION['loggedin']) ? ($_SESSION['loggedin'] ? 'true' : 'false') : 'não definido'));
error_log("id_usuario no sistema_cliente: " . (isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 'não definido'));
error_log("tipo_usuario no sistema_cliente: " . (isset($_SESSION['tipo_usuario']) ? $_SESSION['tipo_usuario'] : 'não definido'));

// Verifica se o usuário está logado e é cliente ANTES de incluir qualquer arquivo
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['tipo_usuario'] !== 'Cliente'){
    error_log("Redirecionando para login - usuário não autenticado ou não é cliente");
    header('Location: login.php');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
include_once('../Conexao/conexao.php');

$cliente_id = $_SESSION['id_usuario'];

// Buscar dados do dashboard diretamente neste arquivo
try {
    // Buscar estatísticas básicas do cliente
    $sql_stats = "SELECT 
        COUNT(*) as total_agendamentos,
        COUNT(CASE WHEN status = 'concluido' THEN 1 END) as agendamentos_concluidos,
        COUNT(CASE WHEN status = 'agendado' THEN 1 END) as agendamentos_pendentes,
        COALESCE(SUM(CASE WHEN status = 'concluido' THEN valor END), 0) as total_gasto
        FROM agendamentos 
        WHERE cliente_id = ?";
    $stmt_stats = $pdo->prepare($sql_stats);
    $stmt_stats->execute([$cliente_id]);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

    // Buscar próximos agendamentos
    $sql_proximos = "SELECT a.*, s.nome as servico_nome, u.nome as profissional_nome
                     FROM agendamentos a
                     LEFT JOIN servicos s ON a.servico_id = s.id_servico
                     LEFT JOIN usuario u ON a.profissional_id = u.id_usuario
                     WHERE a.cliente_id = ? AND a.status = 'agendado' 
                     AND a.data_agendamento >= CURDATE()
                     ORDER BY a.data_agendamento ASC, a.hora_agendamento ASC
                     LIMIT 3";
    $stmt_proximos = $pdo->prepare($sql_proximos);
    $stmt_proximos->execute([$cliente_id]);
    $proximos_agendamentos = $stmt_proximos->fetchAll(PDO::FETCH_ASSOC);

    // Buscar histórico recente
    $sql_historico = "SELECT a.*, s.nome as servico_nome, u.nome as profissional_nome
                      FROM agendamentos a
                      LEFT JOIN servicos s ON a.servico_id = s.id_servico
                      LEFT JOIN usuario u ON a.profissional_id = u.id_usuario
                      WHERE a.cliente_id = ? AND a.status = 'concluido'
                      ORDER BY a.data_agendamento DESC, a.hora_agendamento DESC
                      LIMIT 5";
    $stmt_historico = $pdo->prepare($sql_historico);
    $stmt_historico->execute([$cliente_id]);
    $historico_agendamentos = $stmt_historico->fetchAll(PDO::FETCH_ASSOC);

    // Buscar dados do cliente
    $sql_cliente = "SELECT nome, email, telefone FROM usuario WHERE id_usuario = ?";
    $stmt_cliente = $pdo->prepare($sql_cliente);
    $stmt_cliente->execute([$cliente_id]);
    $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

    // Buscar dados de fidelidade
    $sql_fidelidade = "SELECT f.*, c.pontos_por_real 
                       FROM fidelidade f 
                       LEFT JOIN config_fid c ON f.config_id = c.id_config 
                       WHERE f.usuario_id = ?";
    $stmt_fidelidade = $pdo->prepare($sql_fidelidade);
    $stmt_fidelidade->execute([$cliente_id]);
    $fidelidade = $stmt_fidelidade->fetch(PDO::FETCH_ASSOC);

    // **CONSULTA CORRIGIDA** - Buscar apenas os 3 serviços mais agendados do sistema
    $sql_servicos_populares = "SELECT s.id_servico, s.nome, s.valor, s.duracao, s.categoria,
                               COUNT(a.id_agendamento) as total_agendamentos,
                               COUNT(DISTINCT a.cliente_id) as clientes_diferentes
                               FROM servicos s
                               LEFT JOIN agendamentos a ON s.id_servico = a.servico_id 
                               WHERE s.ativo = 1 
                               GROUP BY s.id_servico, s.nome, s.valor, s.duracao, s.categoria
                               HAVING total_agendamentos > 0
                               ORDER BY total_agendamentos DESC, clientes_diferentes DESC, s.nome ASC
                               LIMIT 3";
    
    error_log("Executando consulta de serviços populares - TOP 3");
    $stmt_servicos_populares = $pdo->prepare($sql_servicos_populares);
    $stmt_servicos_populares->execute();
    $servicos_populares = $stmt_servicos_populares->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Serviços populares encontrados: " . count($servicos_populares));
    if (count($servicos_populares) > 0) {
        error_log("Primeiro serviço: " . print_r($servicos_populares[0], true));
    }

} catch (PDOException $e) {
    error_log("Erro na consulta do dashboard: " . $e->getMessage());
    $stats = ['total_agendamentos' => 0, 'agendamentos_concluidos' => 0, 'agendamentos_pendentes' => 0, 'total_gasto' => 0];
    $proximos_agendamentos = [];
    $historico_agendamentos = [];
    $cliente = ['nome' => 'Cliente', 'email' => '', 'telefone' => ''];
    $fidelidade = null;
    $servicos_populares = [];
}

// Debug final
error_log("=== DADOS CARREGADOS ===");
error_log("Stats: " . print_r($stats, true));
error_log("Próximos: " . count($proximos_agendamentos));
error_log("Histórico: " . count($historico_agendamentos));
error_log("Serviços populares: " . count($servicos_populares));

include_once('topo_sistema_cliente.php');
?>

<!-- CSS personalizado -->
<link rel="stylesheet" href="../CSS/Cliente/sistema_cliente.css">

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Header minimalista -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0"><i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard</h2>
                    <p class="text-muted mb-0">Olá, <?php echo htmlspecialchars($cliente['nome']); ?>! Bem-vindo de volta.</p>
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
                            <h3 class="mb-1"><?php echo $stats['total_agendamentos']; ?></h3>
                            <p class="text-muted mb-0">Total Agendamentos</p>
                            <small class="text-primary">
                                <i class="bi bi-calendar"></i> 
                                Histórico completo
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
                            <h3 class="mb-1"><?php echo $fidelidade['pontos_atuais'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Pontos Fidelidade</p>
                            <small class="text-warning">
                                <i class="bi bi-gift"></i> 
                                <?php echo isset($fidelidade['pontos_atuais']) ? (100 - ($fidelidade['pontos_atuais'] % 100)) : 100; ?> para recompensa
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100 card-hover">
                        <div class="card-body text-center">
                            <div class="text-success mb-2">
                                <i class="bi bi-wallet2 display-4"></i>
                            </div>
                            <h3 class="mb-1">R$ <?php echo number_format($stats['total_gasto'], 2, ',', '.'); ?></h3>
                            <p class="text-muted mb-0">Total Investido</p>
                            <small class="text-success">
                                <i class="bi bi-currency-dollar"></i> 
                                Em serviços
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100 card-hover">
                        <div class="card-body text-center">
                            <div class="text-info mb-2">
                                <i class="bi bi-clock-history display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo count($historico_agendamentos); ?></h3>
                            <p class="text-muted mb-0">Histórico</p>
                            <small class="text-info">
                                <i class="bi bi-calendar-month"></i> 
                                Agendamentos
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Próximo agendamento -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-clock me-2"></i>Próximo Agendamento</h5>
                                <a href="cliente_agendar.php" class="btn btn-light btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i>Agendar
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($proximos_agendamentos)): ?>
                                <?php foreach ($proximos_agendamentos as $agendamento): ?>
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($agendamento['servico_nome'] ?? 'Serviço'); ?></h6>
                                            <p class="mb-1">com <?php echo htmlspecialchars($agendamento['profissional_nome'] ?? 'Profissional'); ?></p>
                                            <small class="text-muted">
                                                <i class="bi bi-cash me-1"></i>R$ <?php echo number_format($agendamento['valor'] ?? 0, 2, ',', '.'); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-warning">
                                                <?php echo date('d/m', strtotime($agendamento['data_agendamento'])); ?>
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-calendar-x display-1 text-muted"></i>
                                    <h6 class="text-muted mt-2">Nenhum agendamento próximo</h6>
                                    <a href="cliente_agendar.php" class="btn btn-primary mt-2">
                                        <i class="bi bi-plus-circle me-1"></i>Agendar Serviço
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Serviços populares -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm servicos-populares">
                        <div class="card-header bg-gradient-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-fire me-2"></i>Top 3 Mais Procurados
                                    <?php if (!empty($servicos_populares)): ?>
                                    <span class="badge bg-warning text-dark ms-2"><?php echo count($servicos_populares); ?></span>
                                    <?php endif; ?>
                                </h5>
                                <a href="cliente_servicos.php" class="btn btn-light btn-sm">
                                    <i class="bi bi-grid me-1"></i>Ver todos
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($servicos_populares)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th><i class="bi bi-scissors me-1"></i>Serviço</th>
                                                <th class="text-center"><i class="bi bi-people me-1"></i>Agendamentos</th>
                                                <th class="text-center"><i class="bi bi-person-check me-1"></i>Clientes</th>
                                                <th class="text-center"><i class="bi bi-clock me-1"></i>Duração</th>
                                                <th class="text-center"><i class="bi bi-cash me-1"></i>Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($servicos_populares as $index => $servico): ?>
                                            <tr class="table-warning">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge bg-warning text-dark me-2">
                                                            <?php 
                                                            switch($index) {
                                                                case 0: echo '🥇 1º'; break;
                                                                case 1: echo '🥈 2º'; break;
                                                                case 2: echo '🥉 3º'; break;
                                                            }
                                                            ?>
                                                        </span>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($servico['nome']); ?></strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="bi bi-tag-fill me-1"></i><?php echo htmlspecialchars($servico['categoria']); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary fs-6">
                                                        <?php echo intval($servico['total_agendamentos']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info fs-6">
                                                        <?php echo intval($servico['clientes_diferentes']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary fs-6">
                                                        <?php echo $servico['duracao']; ?> min
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success fs-6">
                                                        R$ <?php echo number_format($servico['valor'], 2, ',', '.'); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Footer do card com ações -->
                                <div class="card-footer bg-light">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Ranking dos 3 serviços mais agendados
                                            </small>
                                        </div>
                                        <div class="col-md-6 text-md-end">
                                            <a href="cliente_agendar.php" class="btn btn-custom btn-sm">
                                                <i class="bi bi-calendar-plus me-1"></i>Agendar Serviço
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-scissors display-1 text-muted"></i>
                                    <h6 class="text-muted mt-3">Nenhum serviço agendado ainda</h6>
                                    <p class="text-muted mb-4">
                                        Os serviços mais procurados aparecerão aqui conforme os agendamentos forem realizados por todos os clientes.
                                    </p>
                                    <a href="cliente_agendar.php" class="btn btn-custom">
                                        <i class="bi bi-calendar-plus me-2"></i>Fazer Seu Agendamento
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações rápidas e histórico -->
            <div class="row">
                <!-- Ações rápidas -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Ações Rápidas</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="cliente_agendar.php" class="btn btn-custom">
                                    <i class="bi bi-calendar-plus me-2"></i>Agendar Serviço
                                </a>
                                <a href="meus_agendamentos.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-list-ul me-2"></i>Meus Agendamentos
                                </a>
                                <a href="cliente_perfil.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-person me-2"></i>Meu Perfil
                                </a>
                                <a href="cliente_programa_fidelidade.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-star me-2"></i>Programa Fidelidade
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Histórico recente -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Histórico Recente</h5>
                                <a href="meus_agendamentos.php" class="btn btn-light btn-sm">Ver tudo</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($historico_agendamentos)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Data</th>
                                                <th>Serviço</th>
                                                <th>Profissional</th>
                                                <th>Status</th>
                                                <th>Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($historico_agendamentos, 0, 5) as $agendamento): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <div class="fw-bold"><?php echo isset($agendamento['data_agendamento']) ? date('d/m/Y', strtotime($agendamento['data_agendamento'])) : '--/--/----'; ?></div>
                                                        <small class="text-muted"><?php echo isset($agendamento['hora_agendamento']) ? date('H:i', strtotime($agendamento['hora_agendamento'])) : '--:--'; ?></small>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($agendamento['nome_servico'] ?? 'Serviço'); ?></td>
                                                <td><?php echo htmlspecialchars($agendamento['nome_profissional'] ?? 'Profissional'); ?></td>
                                                <td>
                                                    <?php
                                                    $status = $agendamento['status'] ?? 'indefinido';
                                                    switch($status) {
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
                                                        default:
                                                            echo '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">R$ <?php echo number_format($agendamento['valor'] ?? 0, 2, ',', '.'); ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-clock-history display-1 text-muted"></i>
                                    <h6 class="text-muted mt-2">Nenhum histórico encontrado</h6>
                                    <p class="text-muted">Seus agendamentos aparecerão aqui!</p>
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

.card {
    border: none;
    border-radius: 10px;
    transition: transform 0.2s ease-in-out;
}

.card-hover:hover {
    transform: translateY(-5px);
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

.btn-custom:focus,
.btn-custom:active {
    background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
    color: white;
    box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
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

/* Remover estilos antigos - Classes não mais utilizadas */
/* 
.welcome-banner,
.overview-card,
.icon-container,
.appointment-card,
.feature-card,
.empty-state {
    Classes antigas removidas
}
*/
</style>

<!-- JavaScript personalizado -->
<script src="../JS/sistema_cliente.js"></script>

<!-- Adiciona espaço no final da página -->
<div class="mb-5"></div>
