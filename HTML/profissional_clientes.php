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

// Filtros
$filtro_busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$filtro_periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'todos';

// Montar a query base
$sql_base = "SELECT DISTINCT u.id_usuario, u.nome, u.email, u.telefone, u.data_cadastro,
             COUNT(a.id_agendamento) as total_agendamentos,
             MAX(a.data_agendamento) as ultimo_agendamento,
             SUM(CASE WHEN a.status = 'concluido' THEN a.valor ELSE 0 END) as total_gasto,
             AVG(CASE WHEN f.avaliacao > 0 THEN f.avaliacao ELSE NULL END) as media_avaliacao,
             COUNT(CASE WHEN a.status = 'concluido' THEN 1 END) as servicos_concluidos
             FROM usuario u
             INNER JOIN agendamentos a ON u.id_usuario = a.cliente_id
             LEFT JOIN feedback f ON a.id_agendamento = f.agendamento_id
             WHERE a.profissional_id = ? AND u.tipo_usuario = 'Cliente'";

$params = [$profissional_id];

// Aplicar filtro de busca
if (!empty($filtro_busca)) {
    $sql_base .= " AND (u.nome LIKE ? OR u.email LIKE ? OR u.telefone LIKE ?)";
    $busca_param = "%{$filtro_busca}%";
    $params[] = $busca_param;
    $params[] = $busca_param;
    $params[] = $busca_param;
}

// Aplicar filtro de período
switch ($filtro_periodo) {
    case 'mes':
        $sql_base .= " AND a.data_agendamento >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        break;
    case 'trimestre':
        $sql_base .= " AND a.data_agendamento >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        break;
    case 'ano':
        $sql_base .= " AND a.data_agendamento >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        break;
}

$sql_base .= " GROUP BY u.id_usuario, u.nome, u.email, u.telefone, u.data_cadastro
               ORDER BY ultimo_agendamento DESC, total_agendamentos DESC";

$stmt = $pdo->prepare($sql_base);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas gerais
$sql_stats = "SELECT 
              COUNT(DISTINCT a.cliente_id) as total_clientes,
              COUNT(DISTINCT CASE WHEN a.data_agendamento >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN a.cliente_id END) as novos_mes,
              COUNT(DISTINCT CASE WHEN a.data_agendamento >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK) THEN a.cliente_id END) as novos_semana,
              AVG(agendamentos_por_cliente.total) as media_agendamentos
              FROM agendamentos a
              INNER JOIN (
                  SELECT cliente_id, COUNT(*) as total 
                  FROM agendamentos 
                  WHERE profissional_id = ? 
                  GROUP BY cliente_id
              ) agendamentos_por_cliente ON a.cliente_id = agendamentos_por_cliente.cliente_id
              WHERE a.profissional_id = ?";

$stmt_stats = $pdo->prepare($sql_stats);
$stmt_stats->execute([$profissional_id, $profissional_id]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

include_once('topo_sistema_profissional.php');
?>

<!-- CSS personalizado -->
<link rel="stylesheet" href="../CSS/Profissional/profissional_clientes.css">

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1"><i class="bi bi-people text-primary me-2"></i>Meus Clientes</h4>
                    <small class="text-muted">Gerencie o relacionamento com seus clientes</small>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-success fs-6 px-3 py-2">
                        <i class="bi bi-person-check me-1"></i><?php echo count($clientes); ?> cliente(s)
                    </span>
                </div>
            </div>

            <!-- Mensagem -->
            <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Estatísticas resumidas -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center card-hover">
                        <div class="card-body">
                            <div class="text-primary mb-2">
                                <i class="bi bi-people display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['total_clientes'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Total de Clientes</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center card-hover">
                        <div class="card-body">
                            <div class="text-success mb-2">
                                <i class="bi bi-person-plus display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['novos_mes'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Novos este Mês</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center card-hover">
                        <div class="card-body">
                            <div class="text-info mb-2">
                                <i class="bi bi-calendar-week display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['novos_semana'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Novos esta Semana</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center card-hover">
                        <div class="card-body">
                            <div class="text-warning mb-2">
                                <i class="bi bi-graph-up display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo number_format($stats['media_agendamentos'] ?? 0, 1); ?></h3>
                            <p class="text-muted mb-0">Média de Agendamentos</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-6">
                                    <label for="busca" class="form-label">Buscar Cliente</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" class="form-control" id="busca" name="busca" 
                                               placeholder="Nome, email ou telefone..." 
                                               value="<?php echo htmlspecialchars($filtro_busca); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="periodo" class="form-label">Período de Atendimento</label>
                                    <select class="form-select" id="periodo" name="periodo">
                                        <option value="todos" <?php echo $filtro_periodo === 'todos' ? 'selected' : ''; ?>>Todos os períodos</option>
                                        <option value="semana" <?php echo $filtro_periodo === 'semana' ? 'selected' : ''; ?>>Última semana</option>
                                        <option value="mes" <?php echo $filtro_periodo === 'mes' ? 'selected' : ''; ?>>Último mês</option>
                                        <option value="trimestre" <?php echo $filtro_periodo === 'trimestre' ? 'selected' : ''; ?>>Último trimestre</option>
                                        <option value="ano" <?php echo $filtro_periodo === 'ano' ? 'selected' : ''; ?>>Último ano</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-custom">
                                            <i class="bi bi-funnel me-1"></i>Filtrar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de clientes -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Lista de Clientes</h5>
                                <span class="badge bg-light text-dark"><?php echo count($clientes); ?> resultado(s)</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($clientes)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Contato</th>
                                            <th>Agendamentos</th>
                                            <th>Último Atendimento</th>
                                            <th>Total Gasto</th>
                                            <th>Avaliação</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($clientes as $cliente): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-inicial me-3">
                                                        <?php echo strtoupper(substr($cliente['nome'], 0, 2)); ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($cliente['nome']); ?></h6>
                                                        <small class="text-muted">
                                                            Cliente desde <?php echo date('d/m/Y', strtotime($cliente['data_cadastro'])); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <small class="d-block">
                                                        <i class="bi bi-envelope me-1"></i>
                                                        <?php echo htmlspecialchars($cliente['email']); ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="bi bi-telephone me-1"></i>
                                                        <?php echo htmlspecialchars($cliente['telefone']); ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-center">
                                                    <span class="badge bg-primary fs-6"><?php echo $cliente['total_agendamentos']; ?></span>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo $cliente['servicos_concluidos']; ?> concluído(s)
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($cliente['ultimo_agendamento']): ?>
                                                    <span class="badge bg-info">
                                                        <?php echo date('d/m/Y', strtotime($cliente['ultimo_agendamento'])); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">
                                                    R$ <?php echo number_format($cliente['total_gasto'], 2, ',', '.'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($cliente['media_avaliacao']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <span class="me-1"><?php echo number_format($cliente['media_avaliacao'], 1); ?></span>
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="bi bi-star<?php echo $i <= $cliente['media_avaliacao'] ? '-fill text-warning' : ' text-muted'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Sem avaliação</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" title="Ver Histórico"
                                                            data-bs-toggle="modal" data-bs-target="#historicoModal"
                                                            data-cliente-id="<?php echo $cliente['id_usuario']; ?>"
                                                            data-cliente-nome="<?php echo htmlspecialchars($cliente['nome']); ?>">
                                                        <i class="bi bi-clock-history"></i>
                                                    </button>
                                                    <a href="profissional_agendar.php?cliente=<?php echo $cliente['id_usuario']; ?>" 
                                                       class="btn btn-outline-success" title="Novo Agendamento">
                                                        <i class="bi bi-plus-circle"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-people display-1 text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhum cliente encontrado</h5>
                                <?php if (!empty($filtro_busca) || $filtro_periodo !== 'todos'): ?>
                                    <p class="text-muted mb-4">Tente ajustar os filtros de busca.</p>
                                    <a href="profissional_clientes.php" class="btn btn-outline-primary">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Limpar Filtros
                                    </a>
                                <?php else: ?>
                                    <p class="text-muted mb-4">Você ainda não possui clientes atendidos.</p>
                                    <a href="profissional_agendar.php" class="btn btn-custom">
                                        <i class="bi bi-plus-circle me-1"></i>Criar Primeiro Agendamento
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Histórico do Cliente -->
<div class="modal fade" id="historicoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-dark text-white">
                <h5 class="modal-title">
                    <i class="bi bi-clock-history me-2"></i>
                    Histórico de <span id="nomeClienteModal"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="historicoContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilos adicionais -->
<style>
.bg-gradient-dark {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
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

.card-hover:hover {
    transform: translateY(-5px);
    transition: transform 0.2s ease-in-out;
}

.avatar-inicial {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 14px;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    background-color: #f8f9fa;
}

.badge.fs-6 {
    font-size: 0.9rem !important;
}
</style>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal de histórico
    const historicoModal = document.getElementById('historicoModal');
    
    historicoModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const clienteId = button.getAttribute('data-cliente-id');
        const clienteNome = button.getAttribute('data-cliente-nome');
        
        document.getElementById('nomeClienteModal').textContent = clienteNome;
        
        // Carregar histórico via AJAX
        carregarHistorico(clienteId);
    });
    
    function carregarHistorico(clienteId) {
        const content = document.getElementById('historicoContent');
        
        // Mostrar loading
        content.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
            </div>
        `;
        
        // Simular carregamento (você pode implementar uma chamada AJAX real aqui)
        setTimeout(() => {
            fetch(`ajax/buscar_historico_cliente.php?cliente_id=${clienteId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarHistorico(data.historico);
                    } else {
                        content.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Erro ao carregar histórico do cliente.
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle me-2"></i>
                            Erro na comunicação com o servidor.
                        </div>
                    `;
                });
        }, 500);
    }
    
    function mostrarHistorico(historico) {
        const content = document.getElementById('historicoContent');
        
        if (!historico || historico.length === 0) {
            content.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-calendar-x display-4 text-muted mb-3"></i>
                    <h6 class="text-muted">Nenhum histórico encontrado</h6>
                </div>
            `;
            return;
        }
        
        let html = '<div class="timeline">';
        
        historico.forEach(item => {
            const statusClass = {
                'agendado': 'warning',
                'confirmado': 'info',
                'concluido': 'success',
                'cancelado': 'danger'
            }[item.status] || 'secondary';
            
            html += `
                <div class="timeline-item mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">${item.servico_nome}</h6>
                                    <p class="mb-1">${item.data_agendamento} às ${item.hora_agendamento}</p>
                                    <small class="text-muted">R$ ${item.valor}</small>
                                </div>
                                <span class="badge bg-${statusClass}">${item.status}</span>
                            </div>
                            ${item.observacoes ? `<div class="mt-2"><small class="text-muted"><i class="bi bi-chat-left-text me-1"></i>${item.observacoes}</small></div>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        content.innerHTML = html;
    }
});
</script>

<div class="mb-5"></div>
