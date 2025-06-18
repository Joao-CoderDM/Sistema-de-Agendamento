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

// Processar resposta a feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responder_feedback'])) {
    try {
        $id_feedback = $_POST['id_feedback'];
        $resposta = trim($_POST['resposta']);
        
        if (!empty($resposta)) {
            // Verificar se o feedback existe e é de um cliente que foi atendido por este profissional
            $sql_verificar = "SELECT f.* FROM feedback f 
                             INNER JOIN agendamentos a ON f.agendamento_id = a.id_agendamento 
                             WHERE f.id_feedback = ? AND a.profissional_id = ?";
            $stmt_verificar = $pdo->prepare($sql_verificar);
            $stmt_verificar->execute([$id_feedback, $profissional_id]);
            
            if ($stmt_verificar->rowCount() > 0) {
                $sql_resposta = "UPDATE feedback SET resposta = ?, data_resposta = NOW() WHERE id_feedback = ?";
                $stmt_resposta = $pdo->prepare($sql_resposta);
                $stmt_resposta->execute([$resposta, $id_feedback]);
                
                $mensagem = "Resposta enviada com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Erro: Feedback não encontrado ou você não tem permissão para respondê-lo.";
                $tipo_mensagem = "danger";
            }
        } else {
            $mensagem = "Por favor, digite uma resposta válida.";
            $tipo_mensagem = "warning";
        }
    } catch (Exception $e) {
        $mensagem = "Erro ao enviar resposta: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Filtros
$filtro_avaliacao = isset($_GET['avaliacao']) ? $_GET['avaliacao'] : 'todas';
$filtro_respondidas = isset($_GET['respondidas']) ? $_GET['respondidas'] : 'todas';
$filtro_periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'todos';

// Montar a query base
$sql_base = "SELECT f.*, u.nome as cliente_nome, a.data_agendamento, s.nome as servico_nome
             FROM feedback f
             INNER JOIN usuario u ON f.usuario_id = u.id_usuario
             INNER JOIN agendamentos a ON f.agendamento_id = a.id_agendamento
             INNER JOIN servicos s ON a.servico_id = s.id_servico
             WHERE a.profissional_id = ?";

$params = [$profissional_id];

// Aplicar filtros
if ($filtro_avaliacao !== 'todas') {
    $sql_base .= " AND f.avaliacao = ?";
    $params[] = $filtro_avaliacao;
}

if ($filtro_respondidas === 'respondidas') {
    $sql_base .= " AND f.resposta IS NOT NULL";
} elseif ($filtro_respondidas === 'nao_respondidas') {
    $sql_base .= " AND f.resposta IS NULL";
}

switch ($filtro_periodo) {
    case 'semana':
        $sql_base .= " AND f.data_criacao >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
        break;
    case 'mes':
        $sql_base .= " AND f.data_criacao >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        break;
    case 'trimestre':
        $sql_base .= " AND f.data_criacao >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        break;
}

$sql_base .= " ORDER BY f.data_criacao DESC";

$stmt = $pdo->prepare($sql_base);
$stmt->execute($params);
$avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar estatísticas das avaliações
$sql_stats = "SELECT 
              COUNT(*) as total_avaliacoes,
              AVG(f.avaliacao) as media_geral,
              COUNT(CASE WHEN f.avaliacao = 5 THEN 1 END) as cinco_estrelas,
              COUNT(CASE WHEN f.avaliacao = 4 THEN 1 END) as quatro_estrelas,
              COUNT(CASE WHEN f.avaliacao = 3 THEN 1 END) as tres_estrelas,
              COUNT(CASE WHEN f.avaliacao = 2 THEN 1 END) as duas_estrelas,
              COUNT(CASE WHEN f.avaliacao = 1 THEN 1 END) as uma_estrela,
              COUNT(CASE WHEN f.resposta IS NOT NULL THEN 1 END) as respondidas,
              COUNT(CASE WHEN f.resposta IS NULL THEN 1 END) as nao_respondidas
              FROM feedback f
              INNER JOIN agendamentos a ON f.agendamento_id = a.id_agendamento
              WHERE a.profissional_id = ?";

$stmt_stats = $pdo->prepare($sql_stats);
$stmt_stats->execute([$profissional_id]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

include_once('topo_sistema_profissional.php');
?>

<!-- CSS personalizado -->
<link rel="stylesheet" href="../CSS/Profissional/profissional_avaliacoes.css">

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1"><i class="bi bi-star text-warning me-2"></i>Minhas Avaliações</h4>
                    <small class="text-muted">Veja o que seus clientes estão dizendo sobre você</small>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-warning fs-6 px-3 py-2">
                        <i class="bi bi-star-fill me-1"></i><?php echo number_format($stats['media_geral'] ?? 0, 1); ?> de 5.0
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

            <!-- Estatísticas resumidas -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center card-hover">
                        <div class="card-body">
                            <div class="text-warning mb-2">
                                <i class="bi bi-star-fill display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['total_avaliacoes'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Total de Avaliações</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center card-hover">
                        <div class="card-body">
                            <div class="text-success mb-2">
                                <i class="bi bi-graph-up display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo number_format($stats['media_geral'] ?? 0, 1); ?></h3>
                            <p class="text-muted mb-0">Média Geral</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center card-hover">
                        <div class="card-body">
                            <div class="text-info mb-2">
                                <i class="bi bi-chat-square-dots display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['respondidas'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Respondidas</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center card-hover">
                        <div class="card-body">
                            <div class="text-danger mb-2">
                                <i class="bi bi-clock display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['nao_respondidas'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Pendentes</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico de distribuição das estrelas -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Distribuição das Avaliações</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $total = $stats['total_avaliacoes'] > 0 ? $stats['total_avaliacoes'] : 1;
                            $distribuicao = [
                                5 => $stats['cinco_estrelas'],
                                4 => $stats['quatro_estrelas'],
                                3 => $stats['tres_estrelas'],
                                2 => $stats['duas_estrelas'],
                                1 => $stats['uma_estrela']
                            ];
                            ?>
                            <?php foreach ($distribuicao as $estrelas => $quantidade): ?>
                            <div class="row align-items-center mb-2">
                                <div class="col-2">
                                    <span class="fw-bold"><?php echo $estrelas; ?> estrelas</span>
                                </div>
                                <div class="col-8">
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: <?php echo ($quantidade / $total) * 100; ?>%">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-2 text-end">
                                    <span class="text-muted"><?php echo $quantidade; ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
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
                                <div class="col-md-3">
                                    <label for="avaliacao" class="form-label">Filtrar por Estrelas</label>
                                    <select class="form-select" id="avaliacao" name="avaliacao">
                                        <option value="todas" <?php echo $filtro_avaliacao === 'todas' ? 'selected' : ''; ?>>Todas as avaliações</option>
                                        <option value="5" <?php echo $filtro_avaliacao === '5' ? 'selected' : ''; ?>>5 estrelas</option>
                                        <option value="4" <?php echo $filtro_avaliacao === '4' ? 'selected' : ''; ?>>4 estrelas</option>
                                        <option value="3" <?php echo $filtro_avaliacao === '3' ? 'selected' : ''; ?>>3 estrelas</option>
                                        <option value="2" <?php echo $filtro_avaliacao === '2' ? 'selected' : ''; ?>>2 estrelas</option>
                                        <option value="1" <?php echo $filtro_avaliacao === '1' ? 'selected' : ''; ?>>1 estrela</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="respondidas" class="form-label">Status da Resposta</label>
                                    <select class="form-select" id="respondidas" name="respondidas">
                                        <option value="todas" <?php echo $filtro_respondidas === 'todas' ? 'selected' : ''; ?>>Todas</option>
                                        <option value="respondidas" <?php echo $filtro_respondidas === 'respondidas' ? 'selected' : ''; ?>>Respondidas</option>
                                        <option value="nao_respondidas" <?php echo $filtro_respondidas === 'nao_respondidas' ? 'selected' : ''; ?>>Não respondidas</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="periodo" class="form-label">Período</label>
                                    <select class="form-select" id="periodo" name="periodo">
                                        <option value="todos" <?php echo $filtro_periodo === 'todos' ? 'selected' : ''; ?>>Todos os períodos</option>
                                        <option value="semana" <?php echo $filtro_periodo === 'semana' ? 'selected' : ''; ?>>Última semana</option>
                                        <option value="mes" <?php echo $filtro_periodo === 'mes' ? 'selected' : ''; ?>>Último mês</option>
                                        <option value="trimestre" <?php echo $filtro_periodo === 'trimestre' ? 'selected' : ''; ?>>Último trimestre</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
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

            <!-- Lista de avaliações -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Avaliações dos Clientes</h5>
                                <span class="badge bg-light text-dark"><?php echo count($avaliacoes); ?> resultado(s)</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($avaliacoes)): ?>
                            <div class="reviews-container">
                                <?php foreach ($avaliacoes as $avaliacao): ?>
                                <div class="review-item border-bottom p-4">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <!-- Cabeçalho da avaliação -->
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="avatar-inicial me-3">
                                                    <?php echo strtoupper(substr($avaliacao['cliente_nome'], 0, 2)); ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($avaliacao['cliente_nome']); ?></h6>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="stars">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <i class="bi bi-star<?php echo $i <= $avaliacao['avaliacao'] ? '-fill text-warning' : ' text-muted'; ?>"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            <?php echo date('d/m/Y', strtotime($avaliacao['data_criacao'])); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Detalhes do agendamento -->
                                            <div class="mb-3">
                                                <small class="text-muted">
                                                    <i class="bi bi-scissors me-1"></i><?php echo htmlspecialchars($avaliacao['servico_nome']); ?>
                                                    <span class="mx-2">|</span>
                                                    <i class="bi bi-calendar me-1"></i><?php echo date('d/m/Y', strtotime($avaliacao['data_agendamento'])); ?>
                                                </small>
                                            </div>

                                            <!-- Mensagem da avaliação -->
                                            <div class="mb-3">
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($avaliacao['mensagem'])); ?></p>
                                            </div>

                                            <!-- Resposta existente -->
                                            <?php if (!empty($avaliacao['resposta'])): ?>
                                            <div class="response-box bg-light p-3 rounded">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="bi bi-reply text-primary me-2"></i>
                                                    <strong class="text-primary">Sua resposta:</strong>
                                                    <small class="text-muted ms-auto">
                                                        <?php echo date('d/m/Y H:i', strtotime($avaliacao['data_resposta'])); ?>
                                                    </small>
                                                </div>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($avaliacao['resposta'])); ?></p>
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-4">
                                            <?php if (empty($avaliacao['resposta'])): ?>
                                            <!-- Formulário de resposta -->
                                            <div class="response-form">
                                                <h6 class="mb-3">Responder avaliação</h6>
                                                <form method="POST">
                                                    <input type="hidden" name="id_feedback" value="<?php echo $avaliacao['id_feedback']; ?>">
                                                    <div class="mb-3">
                                                        <textarea name="resposta" class="form-control" rows="4" 
                                                                  placeholder="Digite sua resposta..." required></textarea>
                                                    </div>
                                                    <button type="submit" name="responder_feedback" class="btn btn-custom btn-sm w-100">
                                                        <i class="bi bi-send me-1"></i>Enviar Resposta
                                                    </button>
                                                </form>
                                            </div>
                                            <?php else: ?>
                                            <!-- Status respondido -->
                                            <div class="text-center">
                                                <div class="text-success mb-2">
                                                    <i class="bi bi-check-circle display-4"></i>
                                                </div>
                                                <h6 class="text-success">Respondido</h6>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y', strtotime($avaliacao['data_resposta'])); ?>
                                                </small>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-star display-1 text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhuma avaliação encontrada</h5>
                                <?php if ($filtro_avaliacao !== 'todas' || $filtro_respondidas !== 'todas' || $filtro_periodo !== 'todos'): ?>
                                    <p class="text-muted mb-4">Tente ajustar os filtros de busca.</p>
                                    <a href="profissional_avaliacoes.php" class="btn btn-outline-primary">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Limpar Filtros
                                    </a>
                                <?php else: ?>
                                    <p class="text-muted mb-4">Você ainda não possui avaliações de clientes.</p>
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
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 16px;
    flex-shrink: 0;
}

.stars i {
    font-size: 1.1rem;
}

.response-box {
    border-left: 4px solid #007bff;
}

.response-form {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    border: 1px solid #e9ecef;
}

.review-item:last-child {
    border-bottom: none !important;
}

.progress {
    height: 8px;
}

.badge.fs-6 {
    font-size: 0.9rem !important;
}
</style>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-fechar alertas após 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Confirmação antes de enviar resposta
    const forms = document.querySelectorAll('form[method="POST"]');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const textarea = form.querySelector('textarea[name="resposta"]');
            if (textarea && textarea.value.trim().length < 10) {
                e.preventDefault();
                alert('Por favor, digite uma resposta mais detalhada (mínimo 10 caracteres).');
                textarea.focus();
                return false;
            }
        });
    });

    // Contador de caracteres para textarea
    const textareas = document.querySelectorAll('textarea[name="resposta"]');
    textareas.forEach(function(textarea) {
        const maxLength = 500;
        const counter = document.createElement('small');
        counter.className = 'text-muted';
        textarea.parentNode.appendChild(counter);
        
        function updateCounter() {
            const remaining = maxLength - textarea.value.length;
            counter.textContent = `${textarea.value.length}/${maxLength} caracteres`;
            
            if (remaining < 50) {
                counter.className = 'text-warning';
            } else if (remaining < 0) {
                counter.className = 'text-danger';
            } else {
                counter.className = 'text-muted';
            }
        }
        
        textarea.addEventListener('input', updateCounter);
        textarea.setAttribute('maxlength', maxLength);
        updateCounter();
    });
});
</script>

<div class="mb-5"></div>
