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

// Filtros
$categoria_filtro = $_GET['categoria'] ?? 'todas';
$preco_min = $_GET['preco_min'] ?? '';
$preco_max = $_GET['preco_max'] ?? '';
$busca = $_GET['busca'] ?? '';
$ordenacao = $_GET['ordenacao'] ?? 'nome';

// Construir consulta com filtros
$sql_where = "WHERE s.ativo = 1";
$params = [];

if ($categoria_filtro !== 'todas') {
    $sql_where .= " AND s.categoria = ?";
    $params[] = $categoria_filtro;
}

if (!empty($preco_min)) {
    $sql_where .= " AND s.valor >= ?";
    $params[] = $preco_min;
}

if (!empty($preco_max)) {
    $sql_where .= " AND s.valor <= ?";
    $params[] = $preco_max;
}

if (!empty($busca)) {
    $sql_where .= " AND (s.nome LIKE ? OR s.descricao LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

// Definir ordenação
$order_by = "ORDER BY ";
switch ($ordenacao) {
    case 'preco_asc':
        $order_by .= "s.valor ASC";
        break;
    case 'preco_desc':
        $order_by .= "s.valor DESC";
        break;
    case 'duracao':
        $order_by .= "s.duracao ASC";
        break;
    case 'categoria':
        $order_by .= "s.categoria ASC, s.nome ASC";
        break;
    default:
        $order_by .= "s.nome ASC";
}

// Buscar serviços
$sql = "SELECT s.*, 
               COUNT(a.id_agendamento) as total_agendamentos,
               AVG(CASE WHEN f.avaliacao > 0 THEN f.avaliacao END) as media_avaliacao
        FROM servicos s
        LEFT JOIN agendamentos a ON s.id_servico = a.servico_id AND a.status = 'concluido'
        LEFT JOIN feedback f ON a.id_agendamento = f.agendamento_id
        {$sql_where}
        GROUP BY s.id_servico
        {$order_by}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar categorias disponíveis
$sql_categorias = "SELECT DISTINCT categoria FROM servicos WHERE ativo = 1 ORDER BY categoria";
$stmt_categorias = $pdo->prepare($sql_categorias);
$stmt_categorias->execute();
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_COLUMN);

// Estatísticas
$sql_stats = "SELECT 
                COUNT(*) as total_servicos,
                MIN(valor) as preco_min,
                MAX(valor) as preco_max,
                AVG(valor) as preco_medio,
                COUNT(DISTINCT categoria) as total_categorias
              FROM servicos WHERE ativo = 1";
$stmt_stats = $pdo->prepare($sql_stats);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

include_once('topo_sistema_cliente.php');
?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1"><i class="bi bi-scissors text-primary me-2"></i>Nossos Serviços</h4>
                    <small class="text-muted">Conheça todos os serviços disponíveis em nossa barbearia</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="cliente_agendar.php" class="btn btn-custom btn-sm">
                        <i class="bi bi-calendar-plus me-1"></i>Agendar Serviço
                    </a>
                    <span class="badge bg-success fs-6 px-3 py-2">
                        <i class="bi bi-list-check me-1"></i><?php echo count($servicos); ?> serviços
                    </span>
                </div>
            </div>

            <!-- Cards de estatísticas -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-primary mb-2">
                                <i class="bi bi-list-check display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['total_servicos']; ?></h3>
                            <p class="text-muted mb-0">Total de Serviços</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-success mb-2">
                                <i class="bi bi-tags display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['total_categorias']; ?></h3>
                            <p class="text-muted mb-0">Categorias</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-warning mb-2">
                                <i class="bi bi-currency-dollar display-4"></i>
                            </div>
                            <h3 class="mb-1">R$ <?php echo number_format($stats['preco_medio'], 0); ?></h3>
                            <p class="text-muted mb-0">Preço Médio</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-info mb-2">
                                <i class="bi bi-arrow-left-right display-4"></i>
                            </div>
                            <h3 class="mb-1">R$ <?php echo number_format($stats['preco_min'], 0); ?> - <?php echo number_format($stats['preco_max'], 0); ?></h3>
                            <p class="text-muted mb-0">Faixa de Preço</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros e Lista de Serviços -->
            <div class="card shadow-sm">
                <div class="card-header bg-gradient-dark text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtros e Serviços</h5>
                        <button class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#filtrosCollapse">
                            <i class="bi bi-funnel me-1"></i>Filtrar
                        </button>
                    </div>
                </div>
                
                <!-- Filtros colapsáveis -->
                <div class="collapse show" id="filtrosCollapse">
                    <div class="card-body border-bottom">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Categoria</label>
                                <select name="categoria" class="form-select">
                                    <option value="todas" <?php echo $categoria_filtro === 'todas' ? 'selected' : ''; ?>>Todas as Categorias</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo htmlspecialchars($categoria); ?>" 
                                                <?php echo $categoria_filtro === $categoria ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Preço Mín.</label>
                                <input type="number" name="preco_min" class="form-control" 
                                       value="<?php echo htmlspecialchars($preco_min); ?>" 
                                       placeholder="R$ 0,00" min="0" step="0.01">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Preço Máx.</label>
                                <input type="number" name="preco_max" class="form-control" 
                                       value="<?php echo htmlspecialchars($preco_max); ?>" 
                                       placeholder="R$ 999,00" min="0" step="0.01">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Ordenar por</label>
                                <select name="ordenacao" class="form-select">
                                    <option value="nome" <?php echo $ordenacao === 'nome' ? 'selected' : ''; ?>>Nome A-Z</option>
                                    <option value="preco_asc" <?php echo $ordenacao === 'preco_asc' ? 'selected' : ''; ?>>Menor Preço</option>
                                    <option value="preco_desc" <?php echo $ordenacao === 'preco_desc' ? 'selected' : ''; ?>>Maior Preço</option>
                                    <option value="duracao" <?php echo $ordenacao === 'duracao' ? 'selected' : ''; ?>>Duração</option>
                                    <option value="categoria" <?php echo $ordenacao === 'categoria' ? 'selected' : ''; ?>>Categoria</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Buscar</label>
                                <div class="input-group">
                                    <input type="text" name="busca" class="form-control" 
                                           value="<?php echo htmlspecialchars($busca); ?>" 
                                           placeholder="Nome ou descrição...">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search me-1"></i>Aplicar Filtros
                                    </button>
                                    <a href="cliente_servicos.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Limpar Filtros
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card-body p-3">
                    <?php if (!empty($servicos)): ?>
                    <div class="row">
                        <?php foreach ($servicos as $servico): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 service-card">
                                <div class="card-header bg-light border-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($servico['nome']); ?></h6>
                                            <small class="text-primary">
                                                <i class="bi bi-tag-fill me-1"></i>
                                                <?php echo htmlspecialchars($servico['categoria']); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div class="price-tag">
                                                R$ <?php echo number_format($servico['valor'], 2, ',', '.'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($servico['descricao'])): ?>
                                    <p class="card-text text-muted small mb-3">
                                        <?php echo htmlspecialchars($servico['descricao']); ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <div class="service-details mb-3">
                                        <div class="detail-item">
                                            <i class="bi bi-clock text-info"></i>
                                            <span><?php echo $servico['duracao']; ?> minutos</span>
                                        </div>
                                        
                                        <?php if ($servico['total_agendamentos'] > 0): ?>
                                        <div class="detail-item">
                                            <i class="bi bi-people text-success"></i>
                                            <span><?php echo $servico['total_agendamentos']; ?> agendamentos realizados</span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($servico['media_avaliacao']): ?>
                                        <div class="detail-item">
                                            <i class="bi bi-star-fill text-warning"></i>
                                            <span><?php echo number_format($servico['media_avaliacao'], 1); ?> estrelas</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0 pt-0">
                                    <div class="d-grid gap-2">
                                        <a href="cliente_agendar.php?servico=<?php echo $servico['id_servico']; ?>" 
                                           class="btn btn-custom">
                                            <i class="bi bi-calendar-plus me-2"></i>Agendar Serviço
                                        </a>
                                        <button class="btn btn-outline-secondary btn-sm" 
                                                data-bs-toggle="modal" data-bs-target="#detalhesModal"
                                                data-servico='<?php echo json_encode($servico); ?>'>
                                            <i class="bi bi-info-circle me-1"></i>Ver Detalhes
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Resumo dos resultados -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <p class="text-muted mb-0">
                                    Exibindo <strong><?php echo count($servicos); ?></strong> serviços
                                    <?php if ($categoria_filtro !== 'todas' || !empty($busca) || !empty($preco_min) || !empty($preco_max)): ?>
                                        filtrados
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <a href="cliente_agendar.php" class="btn btn-custom">
                                    <i class="bi bi-calendar-plus me-2"></i>Agendar Qualquer Serviço
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-search display-1 text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum serviço encontrado</h5>
                        <p class="text-muted mb-4">
                            <?php if ($categoria_filtro !== 'todas' || !empty($busca) || !empty($preco_min) || !empty($preco_max)): ?>
                                Tente ajustar os filtros para encontrar mais serviços.
                            <?php else: ?>
                                Não há serviços disponíveis no momento.
                            <?php endif; ?>
                        </p>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="cliente_servicos.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>Ver Todos os Serviços
                            </a>
                            <a href="cliente_agendar.php" class="btn btn-custom">
                                <i class="bi bi-calendar-plus me-2"></i>Agendar Serviço
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalhes do Serviço -->
<div class="modal fade" id="detalhesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-dark text-white">
                <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>Detalhes do Serviço</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalhes-content">
                    <!-- Conteúdo será preenchido via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <a href="#" id="btn-agendar-modal" class="btn btn-custom">
                    <i class="bi bi-calendar-plus me-1"></i>Agendar Este Serviço
                </a>
            </div>
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
    transition: transform 0.2s ease-in-out;
}

.service-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
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

.price-tag {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: bold;
    font-size: 0.9rem;
}

.service-details {
    border-left: 3px solid #e9ecef;
    padding-left: 1rem;
}

.detail-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.detail-item i {
    margin-right: 0.5rem;
    width: 16px;
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

.card-header.bg-light {
    background-color: #f8f9fa !important;
}

.input-group .btn {
    border-left: 0;
}
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal de detalhes do serviço
    const detalhesModal = document.getElementById('detalhesModal');
    if (detalhesModal) {
        detalhesModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const servico = JSON.parse(button.getAttribute('data-servico'));
            
            // Atualizar botão de agendar
            const btnAgendar = document.getElementById('btn-agendar-modal');
            btnAgendar.href = `cliente_agendar.php?servico=${servico.id_servico}`;
            
            // Criar conteúdo do modal
            const content = `
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="mb-3">${servico.nome}</h4>
                        <div class="mb-3">
                            <span class="badge bg-primary fs-6 px-3 py-2">
                                <i class="bi bi-tag-fill me-1"></i>${servico.categoria}
                            </span>
                        </div>
                        
                        ${servico.descricao ? `
                            <div class="mb-4">
                                <h6 class="fw-bold">Descrição:</h6>
                                <p class="text-muted">${servico.descricao}</p>
                            </div>
                        ` : ''}
                        
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="border rounded p-3 text-center">
                                    <i class="bi bi-clock text-info display-6"></i>
                                    <h6 class="mt-2 mb-1">Duração</h6>
                                    <p class="mb-0 fw-bold">${servico.duracao} minutos</p>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="border rounded p-3 text-center">
                                    <i class="bi bi-currency-dollar text-success display-6"></i>
                                    <h6 class="mt-2 mb-1">Preço</h6>
                                    <p class="mb-0 fw-bold">R$ ${parseFloat(servico.valor).toFixed(2).replace('.', ',')}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded p-3">
                            <h6 class="fw-bold mb-3">Estatísticas</h6>
                            
                            ${servico.total_agendamentos > 0 ? `
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Agendamentos realizados:</span>
                                        <strong>${servico.total_agendamentos}</strong>
                                    </div>
                                </div>
                            ` : ''}
                            
                            ${servico.media_avaliacao ? `
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>Avaliação média:</span>
                                        <div>
                                            <strong>${parseFloat(servico.media_avaliacao).toFixed(1)}</strong>
                                            <i class="bi bi-star-fill text-warning ms-1"></i>
                                        </div>
                                    </div>
                                </div>
                            ` : ''}
                            
                            <div class="mt-4">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Clique em "Agendar" para escolher data e horário
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('detalhes-content').innerHTML = content;
        });
    }

    // Melhorar UX dos filtros
    const form = document.querySelector('form');
    if (form) {
        // Auto-submit nos selects
        const selects = form.querySelectorAll('select[name="categoria"], select[name="ordenacao"]');
        selects.forEach(select => {
            select.addEventListener('change', function() {
                // Pequeno delay para melhor UX
                setTimeout(() => form.submit(), 100);
            });
        });
    }
});
</script>


<!-- Adiciona espaço no final da página -->
<div class="mb-5"></div>
