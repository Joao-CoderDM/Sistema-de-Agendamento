<?php
session_start();

// Verifica se o usuário está logado e é admin
if(!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'Administrador'){
    header('Location: login.php');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
include_once('../Conexao/conexao.php');

// Mensagem de feedback para o usuário
$mensagem = "";
$tipo_mensagem = "";

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['responder_feedback'])) {
        $id_feedback = $_POST['id_feedback'];
        $resposta = $_POST['resposta'];
        $data_resposta = date('Y-m-d H:i:s');
        
        try {
            $sql = "UPDATE feedback SET resposta = ?, data_resposta = ? WHERE id_feedback = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$resposta, $data_resposta, $id_feedback]);
            
            $mensagem = "Resposta enviada com sucesso!";
            $tipo_mensagem = "success";
        } catch (PDOException $e) {
            $mensagem = "Erro ao enviar resposta: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
    
    if (isset($_POST['excluir_feedback'])) {
        $id_feedback = $_POST['id_feedback'];
        
        try {
            $sql = "DELETE FROM feedback WHERE id_feedback = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_feedback]);
            
            $mensagem = "Feedback excluído com sucesso!";
            $tipo_mensagem = "success";
        } catch (PDOException $e) {
            $mensagem = "Erro ao excluir feedback: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
}

// Filtros
$filtro_avaliacao = isset($_GET['avaliacao']) ? $_GET['avaliacao'] : '';
$filtro_respondido = isset($_GET['respondido']) ? $_GET['respondido'] : '';
$filtro_data = isset($_GET['data']) ? $_GET['data'] : '';

// Buscar todos os feedbacks
$sql = "SELECT f.*, 
               u.nome as usuario_nome, u.email as usuario_email,
               a.data_agendamento, a.hora_agendamento
        FROM feedback f
        LEFT JOIN usuario u ON f.usuario_id = u.id_usuario
        LEFT JOIN agendamentos a ON f.agendamento_id = a.id_agendamento
        WHERE 1=1";

$params = [];

// Aplicar filtros
if (!empty($filtro_avaliacao)) {
    $sql .= " AND f.avaliacao = ?";
    $params[] = $filtro_avaliacao;
}

if ($filtro_respondido !== '') {
    if ($filtro_respondido === 'sim') {
        $sql .= " AND f.resposta IS NOT NULL";
    } else {
        $sql .= " AND f.resposta IS NULL";
    }
}

if (!empty($filtro_data)) {
    $sql .= " AND DATE(f.data_criacao) = ?";
    $params[] = $filtro_data;
}

$sql .= " ORDER BY f.data_criacao DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
$num_resultados = count($feedbacks);

include_once('topo_sistema_adm.php');
?>

<!-- Incluir CSS personalizado -->
<link rel="stylesheet" href="../../CSS/admin_feedbacks.css">

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Header com estatísticas -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0"><i class="bi bi-chat-dots text-primary me-2"></i>Gerenciamento de Feedbacks</h2>
                    <p class="text-muted mb-0">Gerencie avaliações e comentários dos clientes</p>
                </div>
                <div class="d-flex gap-3">
                    <div class="text-center">
                        <div class="badge bg-primary fs-5 px-3 py-2"><?php echo $num_resultados; ?></div>
                        <small class="text-muted d-block">Total</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-warning fs-5 px-3 py-2">
                            <?php echo count(array_filter($feedbacks, fn($f) => empty($f['resposta']))); ?>
                        </div>
                        <small class="text-muted d-block">Pendentes</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-success fs-5 px-3 py-2">
                            <?php echo count(array_filter($feedbacks, fn($f) => !empty($f['resposta']))); ?>
                        </div>
                        <small class="text-muted d-block">Respondidos</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-info fs-5 px-3 py-2">
                            <?php 
                            $soma_avaliacoes = array_sum(array_column($feedbacks, 'avaliacao'));
                            $media = $num_resultados > 0 ? round($soma_avaliacoes / $num_resultados, 1) : 0;
                            echo $media;
                            ?>
                        </div>
                        <small class="text-muted d-block">Média</small>
                    </div>
                </div>
            </div>
            
            <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card shadow-sm">
                <div class="card-header bg-gradient-dark text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Lista de Feedbacks</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal">
                                <i class="bi bi-funnel"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($filtro_avaliacao) || $filtro_respondido !== '' || !empty($filtro_data)): ?>
                    <div class="p-3 bg-light border-bottom">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="fw-bold text-muted">Filtros aplicados:</span>
                            
                            <?php if (!empty($filtro_avaliacao)): ?>
                            <span class="badge bg-info">
                                <i class="bi bi-star me-1"></i>Avaliação: <?php echo $filtro_avaliacao; ?> estrela<?php echo $filtro_avaliacao > 1 ? 's' : ''; ?>
                                <a href="?<?php echo ($filtro_respondido !== '' ? 'respondido='.$filtro_respondido.'&' : '') . (!empty($filtro_data) ? 'data='.$filtro_data : ''); ?>" 
                                   class="text-white text-decoration-none ms-1">×</a>
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($filtro_respondido !== ''): ?>
                            <span class="badge bg-warning">
                                <i class="bi bi-chat me-1"></i>Status: <?php echo $filtro_respondido === 'sim' ? 'Respondido' : 'Pendente'; ?>
                                <a href="?<?php echo (!empty($filtro_avaliacao) ? 'avaliacao='.$filtro_avaliacao.'&' : '') . (!empty($filtro_data) ? 'data='.$filtro_data : ''); ?>" 
                                   class="text-white text-decoration-none ms-1">×</a>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($filtro_data)): ?>
                            <span class="badge bg-secondary">
                                <i class="bi bi-calendar me-1"></i>Data: <?php echo date('d/m/Y', strtotime($filtro_data)); ?>
                                <a href="?<?php echo (!empty($filtro_avaliacao) ? 'avaliacao='.$filtro_avaliacao.'&' : '') . ($filtro_respondido !== '' ? 'respondido='.$filtro_respondido : ''); ?>" 
                                   class="text-white text-decoration-none ms-1">×</a>
                            </span>
                            <?php endif; ?>
                            
                            <a href="admin_feedbacks.php" class="btn btn-sm btn-outline-secondary ms-2">
                                <i class="bi bi-x-circle me-1"></i>Limpar filtros
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3"><i class="bi bi-hash me-1"></i>ID</th>
                                    <th><i class="bi bi-person me-1"></i>Cliente</th>
                                    <th><i class="bi bi-star me-1"></i>Avaliação</th>
                                    <th><i class="bi bi-chat me-1"></i>Comentário</th>
                                    <th><i class="bi bi-calendar me-1"></i>Data</th>
                                    <th><i class="bi bi-reply me-1"></i>Status</th>
                                    <th class="text-center"><i class="bi bi-gear me-1"></i>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($num_resultados > 0): ?>
                                    <?php foreach ($feedbacks as $feedback): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge bg-light text-dark">#<?php echo $feedback['id_feedback']; ?></span>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($feedback['nome']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($feedback['email']); ?></small>
                                                <?php if (!empty($feedback['data_agendamento'])): ?>
                                                <br><small class="text-info">Agendamento: <?php echo date('d/m/Y', strtotime($feedback['data_agendamento'])); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?php echo $i <= $feedback['avaliacao'] ? '-fill text-warning' : ' text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                                <span class="ms-2 badge bg-warning"><?php echo $feedback['avaliacao']; ?>/5</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="feedback-message">
                                                <?php 
                                                $mensagem = htmlspecialchars($feedback['mensagem']);
                                                if (strlen($mensagem) > 80) {
                                                    echo '<span class="message-preview">' . substr($mensagem, 0, 80) . '...</span>';
                                                    echo '<span class="message-full d-none">' . $mensagem . '</span>';
                                                    echo '<br><button class="btn btn-sm btn-link p-0 toggle-message">Ver mais</button>';
                                                } else {
                                                    echo $mensagem;
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($feedback['data_criacao'])); ?></small>
                                        </td>
                                        <td>
                                            <?php if (!empty($feedback['resposta'])): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle me-1"></i>Respondido
                                                </span>
                                                <br><small class="text-muted"><?php echo date('d/m/Y', strtotime($feedback['data_resposta'])); ?></small>
                                            <?php else: ?>
                                                <span class="badge bg-warning">
                                                    <i class="bi bi-clock me-1"></i>Pendente
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <button class="btn btn-sm btn-outline-info" title="Visualizar"
                                                        data-bs-toggle="modal" data-bs-target="#viewModal"
                                                        data-id="<?php echo $feedback['id_feedback']; ?>"
                                                        data-nome="<?php echo htmlspecialchars($feedback['nome']); ?>"
                                                        data-email="<?php echo htmlspecialchars($feedback['email']); ?>"
                                                        data-avaliacao="<?php echo $feedback['avaliacao']; ?>"
                                                        data-mensagem="<?php echo htmlspecialchars($feedback['mensagem']); ?>"
                                                        data-resposta="<?php echo htmlspecialchars($feedback['resposta']); ?>"
                                                        data-data="<?php echo date('d/m/Y H:i', strtotime($feedback['data_criacao'])); ?>"
                                                        data-data-resposta="<?php echo !empty($feedback['data_resposta']) ? date('d/m/Y H:i', strtotime($feedback['data_resposta'])) : ''; ?>">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <?php if (empty($feedback['resposta'])): ?>
                                                <button class="btn btn-sm btn-outline-primary" title="Responder"
                                                        data-bs-toggle="modal" data-bs-target="#responseModal"
                                                        data-id="<?php echo $feedback['id_feedback']; ?>"
                                                        data-nome="<?php echo htmlspecialchars($feedback['nome']); ?>"
                                                        data-mensagem="<?php echo htmlspecialchars($feedback['mensagem']); ?>">
                                                    <i class="bi bi-reply"></i>
                                                </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-danger" title="Excluir"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                        data-id="<?php echo $feedback['id_feedback']; ?>"
                                                        data-nome="<?php echo htmlspecialchars($feedback['nome']); ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="bi bi-chat-dots display-1 text-muted"></i>
                                            <h5 class="text-muted mt-3">Nenhum feedback encontrado</h5>
                                            <p class="text-muted">Ainda não há avaliações ou ajuste os filtros</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para visualizar feedback completo -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title" id="viewModalLabel">
                    <i class="bi bi-eye me-2"></i>Visualizar Feedback
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Cliente:</strong> <span id="view_nome"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>E-mail:</strong> <span id="view_email"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Data:</strong> <span id="view_data"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Avaliação:</strong> 
                        <span id="view_avaliacao_stars"></span>
                        <span id="view_avaliacao_number" class="badge bg-warning ms-2"></span>
                    </div>
                </div>
                <div class="mb-3">
                    <strong>Comentário:</strong>
                    <div class="border rounded p-3 bg-light mt-2">
                        <span id="view_mensagem"></span>
                    </div>
                </div>
                <div id="view_resposta_section" style="display: none;">
                    <strong>Resposta:</strong>
                    <div class="border rounded p-3 bg-success bg-opacity-10 mt-2">
                        <span id="view_resposta"></span>
                        <br><small class="text-muted">Respondido em: <span id="view_data_resposta"></span></small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para responder feedback -->
<div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="responseModalLabel">
                    <i class="bi bi-reply me-2"></i>Responder Feedback
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="response_id_feedback" name="id_feedback">
                    
                    <div class="alert alert-info">
                        <strong>Cliente:</strong> <span id="response_nome"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Comentário original:</strong></label>
                        <div class="border rounded p-3 bg-light">
                            <span id="response_mensagem_original"></span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="resposta" class="form-label">Sua resposta:</label>
                        <textarea class="form-control" id="resposta" name="resposta" rows="4" required 
                                  placeholder="Digite sua resposta para o cliente..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" name="responder_feedback">
                        <i class="bi bi-send me-1"></i>Enviar Resposta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para excluir feedback -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="delete_id_feedback" name="id_feedback">
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle display-1 text-warning"></i>
                        <h5 class="mt-3">Tem certeza que deseja excluir?</h5>
                        <p>O feedback de <strong id="delete_nome_cliente"></strong> será removido permanentemente.</p>
                        <p class="text-muted">Esta ação não pode ser desfeita.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger" name="excluir_feedback">
                        <i class="bi bi-trash me-1"></i>Excluir Feedback
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para filtrar feedbacks -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title" id="filterModalLabel">
                    <i class="bi bi-funnel me-2"></i>Filtrar Feedbacks
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="get" action="admin_feedbacks.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="filter_avaliacao" class="form-label">Avaliação</label>
                        <select class="form-select" id="filter_avaliacao" name="avaliacao">
                            <option value="">Todas as avaliações</option>
                            <option value="5" <?php echo $filtro_avaliacao == '5' ? 'selected' : ''; ?>>5 estrelas</option>
                            <option value="4" <?php echo $filtro_avaliacao == '4' ? 'selected' : ''; ?>>4 estrelas</option>
                            <option value="3" <?php echo $filtro_avaliacao == '3' ? 'selected' : ''; ?>>3 estrelas</option>
                            <option value="2" <?php echo $filtro_avaliacao == '2' ? 'selected' : ''; ?>>2 estrelas</option>
                            <option value="1" <?php echo $filtro_avaliacao == '1' ? 'selected' : ''; ?>>1 estrela</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="filter_respondido" class="form-label">Status da Resposta</label>
                        <select class="form-select" id="filter_respondido" name="respondido">
                            <option value="">Todos os status</option>
                            <option value="sim" <?php echo $filtro_respondido === 'sim' ? 'selected' : ''; ?>>Respondido</option>
                            <option value="nao" <?php echo $filtro_respondido === 'nao' ? 'selected' : ''; ?>>Pendente</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="filter_data" class="form-label">Data</label>
                        <input type="date" class="form-control" id="filter_data" name="data" value="<?php echo htmlspecialchars($filtro_data); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="admin_feedbacks.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Limpar filtros
                    </a>
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-check-circle me-1"></i>Aplicar filtros
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

.bg-gradient-primary {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
}

.bg-gradient-info {
   background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
}

.bg-gradient-danger {
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

.btn-sm {
    padding: 0.375rem 0.75rem;
}

.rating-stars {
    display: flex;
    align-items: center;
}

.feedback-message {
    max-width: 300px;
}

.toggle-message {
    font-size: 0.875rem;
}
</style>

<script>
// Script para carregar dados nos modais
document.addEventListener('click', function(e) {
    if (e.target.closest('[data-bs-target="#viewModal"]')) {
        const btn = e.target.closest('[data-bs-target="#viewModal"]');
        document.getElementById('view_nome').textContent = btn.dataset.nome;
        document.getElementById('view_email').textContent = btn.dataset.email;
        document.getElementById('view_data').textContent = btn.dataset.data;
        document.getElementById('view_mensagem').textContent = btn.dataset.mensagem;
        
        // Exibir estrelas
        const stars = document.getElementById('view_avaliacao_stars');
        const avaliacao = parseInt(btn.dataset.avaliacao);
        stars.innerHTML = '';
        for (let i = 1; i <= 5; i++) {
            const star = document.createElement('i');
            star.className = i <= avaliacao ? 'bi bi-star-fill text-warning' : 'bi bi-star text-muted';
            stars.appendChild(star);
        }
        document.getElementById('view_avaliacao_number').textContent = avaliacao + '/5';
        
        // Mostrar resposta se existir
        if (btn.dataset.resposta) {
            document.getElementById('view_resposta').textContent = btn.dataset.resposta;
            document.getElementById('view_data_resposta').textContent = btn.dataset.dataResposta;
            document.getElementById('view_resposta_section').style.display = 'block';
        } else {
            document.getElementById('view_resposta_section').style.display = 'none';
        }
    }
    
    if (e.target.closest('[data-bs-target="#responseModal"]')) {
        const btn = e.target.closest('[data-bs-target="#responseModal"]');
        document.getElementById('response_id_feedback').value = btn.dataset.id;
        document.getElementById('response_nome').textContent = btn.dataset.nome;
        document.getElementById('response_mensagem_original').textContent = btn.dataset.mensagem;
    }
    
    if (e.target.closest('[data-bs-target="#deleteModal"]')) {
        const btn = e.target.closest('[data-bs-target="#deleteModal"]');
        document.getElementById('delete_id_feedback').value = btn.dataset.id;
        document.getElementById('delete_nome_cliente').textContent = btn.dataset.nome;
    }
    
    // Toggle para mostrar/ocultar mensagem completa
    if (e.target.classList.contains('toggle-message')) {
        e.preventDefault();
        const container = e.target.closest('.feedback-message');
        const preview = container.querySelector('.message-preview');
        const full = container.querySelector('.message-full');
        
        if (full.classList.contains('d-none')) {
            preview.classList.add('d-none');
            full.classList.remove('d-none');
            e.target.textContent = 'Ver menos';
        } else {
            preview.classList.remove('d-none');
            full.classList.add('d-none');
            e.target.textContent = 'Ver mais';
        }
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Adiciona espaço no final da página -->
<div class="mb-5"></div>