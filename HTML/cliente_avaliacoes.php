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

// Processar envio de avaliação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_avaliacao'])) {
    try {
        $agendamento_id = $_POST['agendamento_id'];
        $avaliacao_servico = (int)$_POST['avaliacao_servico'];
        $avaliacao_profissional = (int)$_POST['avaliacao_profissional'];
        $mensagem_feedback = trim($_POST['mensagem']);
        $comentario_profissional = trim($_POST['comentario_profissional']);
        
        // Verificar se o agendamento pertence ao cliente e está concluído
        $sql_verificar = "SELECT a.*, u.nome as cliente_nome, u.email as cliente_email, 
                                s.nome as servico_nome, p.nome as profissional_nome
                         FROM agendamentos a
                         JOIN usuario u ON a.cliente_id = u.id_usuario
                         JOIN servicos s ON a.servico_id = s.id_servico
                         JOIN usuario p ON a.profissional_id = p.id_usuario
                         WHERE a.id_agendamento = ? AND a.cliente_id = ? AND a.status = 'concluido'";
        $stmt_verificar = $pdo->prepare($sql_verificar);
        $stmt_verificar->execute([$agendamento_id, $cliente_id]);
        $agendamento = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
        
        if ($agendamento) {
            // Verificar se já existe avaliação para este agendamento
            $sql_existe = "SELECT id_feedback FROM feedback WHERE agendamento_id = ?";
            $stmt_existe = $pdo->prepare($sql_existe);
            $stmt_existe->execute([$agendamento_id]);
            $avaliacao_existente = $stmt_existe->fetch();
            
            if ($avaliacao_existente) {
                // Atualizar avaliação existente
                $sql_update = "UPDATE feedback SET 
                              avaliacao = ?, 
                              avaliacao_profissional = ?,
                              mensagem = ?, 
                              comentario_profissional = ?,
                              data_criacao = CURRENT_TIMESTAMP 
                              WHERE agendamento_id = ?";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([$avaliacao_servico, $avaliacao_profissional, $mensagem_feedback, $comentario_profissional, $agendamento_id]);
                
                $mensagem = "Avaliação atualizada com sucesso!";
                $tipo_mensagem = "success";
            } else {
                // Inserir nova avaliação
                $sql_insert = "INSERT INTO feedback (nome, email, mensagem, avaliacao, avaliacao_profissional, comentario_profissional, usuario_id, agendamento_id) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert = $pdo->prepare($sql_insert);
                $stmt_insert->execute([
                    $agendamento['cliente_nome'],
                    $agendamento['cliente_email'],
                    $mensagem_feedback,
                    $avaliacao_servico,
                    $avaliacao_profissional,
                    $comentario_profissional,
                    $cliente_id,
                    $agendamento_id
                ]);
                
                $mensagem = "Avaliação enviada com sucesso! Obrigado pelo seu feedback.";
                $tipo_mensagem = "success";
            }
        } else {
            $mensagem = "Erro: Agendamento não encontrado ou não está concluído.";
            $tipo_mensagem = "danger";
        }
    } catch (Exception $e) {
        $mensagem = "Erro ao enviar avaliação: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Buscar agendamentos concluídos que podem ser avaliados
$sql_agendamentos = "SELECT a.*, s.nome as servico_nome, p.nome as profissional_nome,
                            f.id_feedback, f.avaliacao as avaliacao_atual, f.avaliacao_profissional, 
                            f.mensagem as feedback_atual, f.comentario_profissional
                     FROM agendamentos a
                     JOIN servicos s ON a.servico_id = s.id_servico
                     JOIN usuario p ON a.profissional_id = p.id_usuario
                     LEFT JOIN feedback f ON a.id_agendamento = f.agendamento_id
                     WHERE a.cliente_id = ? AND a.status = 'concluido'
                     ORDER BY a.data_agendamento DESC";
$stmt_agendamentos = $pdo->prepare($sql_agendamentos);
$stmt_agendamentos->execute([$cliente_id]);
$agendamentos_concluidos = $stmt_agendamentos->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as avaliações do cliente
$sql_avaliacoes = "SELECT f.*, a.data_agendamento, a.hora_agendamento, 
                          s.nome as servico_nome, p.nome as profissional_nome,
                          a.valor, f.resposta, f.data_resposta
                   FROM feedback f
                   JOIN agendamentos a ON f.agendamento_id = a.id_agendamento
                   JOIN servicos s ON a.servico_id = s.id_servico
                   JOIN usuario p ON a.profissional_id = p.id_usuario
                   WHERE f.usuario_id = ?
                   ORDER BY f.data_criacao DESC";
$stmt_avaliacoes = $pdo->prepare($sql_avaliacoes);
$stmt_avaliacoes->execute([$cliente_id]);
$minhas_avaliacoes = $stmt_avaliacoes->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas das avaliações
$total_avaliacoes = count($minhas_avaliacoes);
$media_avaliacoes_servico = $total_avaliacoes > 0 ? array_sum(array_column($minhas_avaliacoes, 'avaliacao')) / $total_avaliacoes : 0;
$media_avaliacoes_profissional = $total_avaliacoes > 0 ? array_sum(array_filter(array_column($minhas_avaliacoes, 'avaliacao_profissional'))) / max(1, count(array_filter(array_column($minhas_avaliacoes, 'avaliacao_profissional')))) : 0;
$avaliacoes_com_resposta = count(array_filter($minhas_avaliacoes, function($av) { return !empty($av['resposta']); }));
$agendamentos_sem_avaliacao = count(array_filter($agendamentos_concluidos, function($ag) { return empty($ag['id_feedback']); }));

include_once('topo_sistema_cliente.php');
?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1"><i class="bi bi-chat-square-heart text-primary me-2"></i>Minhas Avaliações</h4>
                    <small class="text-muted">Avalie nossos serviços e veja suas avaliações anteriores</small>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-success fs-6 px-3 py-2">
                        <i class="bi bi-star-fill me-1"></i><?php echo number_format($media_avaliacoes_servico, 1); ?> estrelas médias
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
                                <i class="bi bi-chat-square-text display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $total_avaliacoes; ?></h3>
                            <p class="text-muted mb-0">Total de Avaliações</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-warning mb-2">
                                <i class="bi bi-scissors display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo number_format($media_avaliacoes_servico, 1); ?></h3>
                            <p class="text-muted mb-0">Média dos Serviços</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-info mb-2">
                                <i class="bi bi-person-check display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo number_format($media_avaliacoes_profissional, 1); ?></h3>
                            <p class="text-muted mb-0">Média dos Profissionais</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-success mb-2">
                                <i class="bi bi-reply display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $avaliacoes_com_resposta; ?></h3>
                            <p class="text-muted mb-0">Com Resposta</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Serviços para Avaliar -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-star me-2"></i>Serviços para Avaliar</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($agendamentos_concluidos)): ?>
                                <?php 
                                $agendamentos_sem_avaliar = array_filter($agendamentos_concluidos, function($ag) { 
                                    return empty($ag['id_feedback']); 
                                });
                                ?>
                                
                                <?php if (!empty($agendamentos_sem_avaliar)): ?>
                                    <?php foreach (array_slice($agendamentos_sem_avaliar, 0, 3) as $agendamento): ?>
                                    <div class="evaluation-card mb-3">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($agendamento['servico_nome']); ?></h6>
                                                <p class="mb-1">com <?php echo htmlspecialchars($agendamento['profissional_nome']); ?></p>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar me-1"></i>
                                                    <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?> às 
                                                    <?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-success">R$ <?php echo number_format($agendamento['valor'], 2, ',', '.'); ?></span>
                                        </div>
                                        
                                        <button class="btn btn-custom btn-sm w-100" 
                                                data-bs-toggle="modal" data-bs-target="#avaliarModal"
                                                data-agendamento='<?php echo json_encode($agendamento); ?>'>
                                            <i class="bi bi-star me-1"></i>Avaliar Este Serviço
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($agendamentos_sem_avaliar) > 3): ?>
                                    <div class="text-center">
                                        <small class="text-muted">
                                            + <?php echo count($agendamentos_sem_avaliar) - 3; ?> serviços aguardando avaliação
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-check-circle display-1 text-success mb-3"></i>
                                        <h6 class="text-muted">Parabéns!</h6>
                                        <p class="text-muted">Você avaliou todos os seus serviços!</p>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-calendar-x display-1 text-muted mb-3"></i>
                                    <h6 class="text-muted">Nenhum serviço concluído</h6>
                                    <p class="text-muted">Complete um agendamento para avaliar nossos serviços!</p>
                                    <a href="cliente_agendar.php" class="btn btn-custom mt-2">
                                        <i class="bi bi-calendar-plus me-2"></i>Agendar Serviço
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Minhas Avaliações Recentes -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Avaliações Recentes</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($minhas_avaliacoes)): ?>
                                <?php foreach (array_slice($minhas_avaliacoes, 0, 3) as $avaliacao): ?>
                                <div class="evaluation-card mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($avaliacao['servico_nome']); ?></h6>
                                            <small class="text-muted">
                                                com <?php echo htmlspecialchars($avaliacao['profissional_nome']); ?> • 
                                                <?php echo date('d/m/Y', strtotime($avaliacao['data_agendamento'])); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div class="stars mb-1">
                                                <small class="text-muted">Serviço:</small>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?php echo $i <= $avaliacao['avaliacao'] ? '-fill text-warning' : ' text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <?php if (!empty($avaliacao['avaliacao_profissional'])): ?>
                                            <div class="stars">
                                                <small class="text-muted">Profissional:</small>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?php echo $i <= $avaliacao['avaliacao_profissional'] ? '-fill text-info' : ' text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($avaliacao['mensagem'])): ?>
                                    <p class="mb-2 text-muted small">
                                        <strong>Sobre o serviço:</strong> "<?php echo htmlspecialchars($avaliacao['mensagem']); ?>"
                                    </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($avaliacao['comentario_profissional'])): ?>
                                    <p class="mb-2 text-muted small">
                                        <strong>Sobre o profissional:</strong> "<?php echo htmlspecialchars($avaliacao['comentario_profissional']); ?>"
                                    </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($avaliacao['resposta'])): ?>
                                    <div class="response-box">
                                        <small class="text-muted">
                                            <strong>Resposta da Barbearia:</strong><br>
                                            <?php echo htmlspecialchars($avaliacao['resposta']); ?>
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                                
                                <?php if (count($minhas_avaliacoes) > 3): ?>
                                <div class="text-center">
                                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#todasAvaliacoes">
                                        Ver todas as avaliações
                                    </button>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-chat-square display-1 text-muted mb-3"></i>
                                    <h6 class="text-muted">Nenhuma avaliação ainda</h6>
                                    <p class="text-muted">Suas avaliações aparecerão aqui!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Todas as Avaliações (colapsável) -->
            <?php if (count($minhas_avaliacoes) > 3): ?>
            <div class="collapse" id="todasAvaliacoes">
                <div class="card shadow-sm">
                    <div class="card-header bg-gradient-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Todas as Minhas Avaliações</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Data</th>
                                        <th>Serviço</th>
                                        <th>Profissional</th>
                                        <th>Avaliação</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($minhas_avaliacoes as $avaliacao): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <div class="fw-bold"><?php echo date('d/m/Y', strtotime($avaliacao['data_agendamento'])); ?></div>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($avaliacao['hora_agendamento'])); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($avaliacao['servico_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($avaliacao['profissional_nome']); ?></td>
                                        <td>
                                            <div class="stars small">
                                                <div>Serviço: 
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="bi bi-star<?php echo $i <= $avaliacao['avaliacao'] ? '-fill text-warning' : ' text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <?php if (!empty($avaliacao['avaliacao_profissional'])): ?>
                                                <div>Profissional: 
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="bi bi-star<?php echo $i <= $avaliacao['avaliacao_profissional'] ? '-fill text-info' : ' text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($avaliacao['resposta'])): ?>
                                                <span class="badge bg-success"><i class="bi bi-reply me-1"></i>Respondida</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning"><i class="bi bi-clock me-1"></i>Pendente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#detalhesAvaliacaoModal"
                                                    data-avaliacao='<?php echo json_encode($avaliacao); ?>'>
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para Avaliar Serviço -->
<div class="modal fade" id="avaliarModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-star me-2"></i>Avaliar Serviço e Profissional</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="agendamento_id" id="agendamento_id">
                    
                    <div class="text-center mb-4">
                        <div id="info_servico" class="mb-3"></div>
                    </div>
                    
                    <!-- Avaliação do Serviço -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Como você avalia o serviço? *</label>
                        <div class="rating-stars text-center">
                            <input type="hidden" name="avaliacao_servico" id="avaliacao_servico_value" required>
                            <div id="star-rating-servico" class="star-rating">
                                <i class="bi bi-star star-servico" data-rating="1"></i>
                                <i class="bi bi-star star-servico" data-rating="2"></i>
                                <i class="bi bi-star star-servico" data-rating="3"></i>
                                <i class="bi bi-star star-servico" data-rating="4"></i>
                                <i class="bi bi-star star-servico" data-rating="5"></i>
                            </div>
                            <div class="rating-text mt-2">
                                <small id="rating-description-servico" class="text-muted">Clique nas estrelas para avaliar o serviço</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Comente sobre o serviço</label>
                        <textarea name="mensagem" class="form-control" rows="3" 
                                  placeholder="Como foi a qualidade do serviço? O resultado ficou como esperado?"></textarea>
                    </div>

                    <hr class="my-4">

                    <!-- Avaliação do Profissional -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Como você avalia o profissional? *</label>
                        <div class="rating-stars text-center">
                            <input type="hidden" name="avaliacao_profissional" id="avaliacao_profissional_value" required>
                            <div id="star-rating-profissional" class="star-rating">
                                <i class="bi bi-star star-profissional" data-rating="1"></i>
                                <i class="bi bi-star star-profissional" data-rating="2"></i>
                                <i class="bi bi-star star-profissional" data-rating="3"></i>
                                <i class="bi bi-star star-profissional" data-rating="4"></i>
                                <i class="bi bi-star star-profissional" data-rating="5"></i>
                            </div>
                            <div class="rating-text mt-2">
                                <small id="rating-description-profissional" class="text-muted">Clique nas estrelas para avaliar o profissional</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Comente sobre o profissional</label>
                        <textarea name="comentario_profissional" class="form-control" rows="3" 
                                  placeholder="Como foi o atendimento? O profissional foi atencioso e habilidoso?"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle me-2"></i>Sua avaliação é importante!</h6>
                        <ul class="mb-0">
                            <li>Avalie tanto o serviço quanto o profissional</li>
                            <li>Suas estrelas e comentários nos ajudam a melhorar</li>
                            <li>Você pode editar sua avaliação posteriormente</li>
                            <li>Outras pessoas poderão ver sua avaliação</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" name="enviar_avaliacao" class="btn btn-warning" id="btn-enviar-avaliacao" disabled>
                        <i class="bi bi-star me-1"></i>Enviar Avaliação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Detalhes da Avaliação -->
<div class="modal fade" id="detalhesAvaliacaoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-dark text-white">
                <h5 class="modal-title"><i class="bi bi-chat-square-text me-2"></i>Detalhes da Avaliação</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalhes-avaliacao-content">
                    <!-- Conteúdo será preenchido via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
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

.evaluation-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    border-left: 4px solid #007bff;
}

.response-box {
    background: #e8f5e8;
    border-radius: 6px;
    padding: 0.75rem;
    border-left: 3px solid #28a745;
    margin-top: 0.5rem;
}

.star-rating {
    font-size: 2rem;
}

.star-rating .star {
    cursor: pointer;
    transition: all 0.2s ease;
    color: #ddd;
}

.star-rating .star:hover,
.star-rating .star.active {
    color: #ffc107;
    transform: scale(1.1);
}

.stars {
    font-size: 1rem;
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
    // Modal para avaliar serviço
    const avaliarModal = document.getElementById('avaliarModal');
    if (avaliarModal) {
        avaliarModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const agendamento = JSON.parse(button.getAttribute('data-agendamento'));
            
            document.getElementById('agendamento_id').value = agendamento.id_agendamento;
            
            const infoServico = `
                <h6>${agendamento.servico_nome}</h6>
                <p class="mb-1">com ${agendamento.profissional_nome}</p>
                <small class="text-muted">
                    ${new Date(agendamento.data_agendamento + 'T00:00:00').toLocaleDateString('pt-BR')} às 
                    ${agendamento.hora_agendamento.substring(0, 5)}
                </small>
            `;
            document.getElementById('info_servico').innerHTML = infoServico;
            
            // Reset ratings
            resetRating();
        });
    }

    // Sistema de avaliação por estrelas
    const starsServico = document.querySelectorAll('.star-servico');
    const starsProfissional = document.querySelectorAll('.star-profissional');
    const ratingValueServico = document.getElementById('avaliacao_servico_value');
    const ratingValueProfissional = document.getElementById('avaliacao_profissional_value');
    const ratingDescriptionServico = document.getElementById('rating-description-servico');
    const ratingDescriptionProfissional = document.getElementById('rating-description-profissional');
    const btnEnviar = document.getElementById('btn-enviar-avaliacao');
    
    const descriptions = {
        1: '⭐ Muito Ruim - Não recomendo',
        2: '⭐⭐ Ruim - Abaixo do esperado',
        3: '⭐⭐⭐ Regular - Atendeu o básico',
        4: '⭐⭐⭐⭐ Bom - Recomendo',
        5: '⭐⭐⭐⭐⭐ Excelente - Superou expectativas!'
    };
    
    function resetRating() {
        starsServico.forEach(star => star.classList.remove('active'));
        starsProfissional.forEach(star => star.classList.remove('active'));
        ratingValueServico.value = '';
        ratingValueProfissional.value = '';
        ratingDescriptionServico.textContent = 'Clique nas estrelas para avaliar o serviço';
        ratingDescriptionProfissional.textContent = 'Clique nas estrelas para avaliar o profissional';
        updateSubmitButton();
    }
    
    function updateSubmitButton() {
        const servicoRating = ratingValueServico.value;
        const profissionalRating = ratingValueProfissional.value;
        btnEnviar.disabled = !(servicoRating && profissionalRating);
    }
    
    // Avaliação do serviço
    starsServico.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            ratingValueServico.value = rating;
            ratingDescriptionServico.textContent = descriptions[rating];
            
            starsServico.forEach((s, index) => {
                if (index < rating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
            
            updateSubmitButton();
        });
        
        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            starsServico.forEach((s, index) => {
                if (index < rating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    });
    
    // Avaliação do profissional
    starsProfissional.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            ratingValueProfissional.value = rating;
            ratingDescriptionProfissional.textContent = descriptions[rating];
            
            starsProfissional.forEach((s, index) => {
                if (index < rating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
            
            updateSubmitButton();
        });
        
        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            starsProfissional.forEach((s, index) => {
                if (index < rating) {
                    s.style.color = '#17a2b8';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    });
    
    // Reset hover effect when leaving rating areas
    document.getElementById('star-rating-servico').addEventListener('mouseleave', function() {
        const currentRating = parseInt(ratingValueServico.value) || 0;
        starsServico.forEach((star, index) => {
            if (index < currentRating) {
                star.style.color = '#ffc107';
            } else {
                star.style.color = '#ddd';
            }
        });
    });
    
    document.getElementById('star-rating-profissional').addEventListener('mouseleave', function() {
        const currentRating = parseInt(ratingValueProfissional.value) || 0;
        starsProfissional.forEach((star, index) => {
            if (index < currentRating) {
                star.style.color = '#17a2b8';
            } else {
                star.style.color = '#ddd';
            }
        });
    });

    // Modal de detalhes da avaliação
    const detalhesModal = document.getElementById('detalhesAvaliacaoModal');
    if (detalhesModal) {
        detalhesModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const avaliacao = JSON.parse(button.getAttribute('data-avaliacao'));
            
            const dataFormatada = new Date(avaliacao.data_agendamento + 'T00:00:00').toLocaleDateString('pt-BR');
            const horaFormatada = avaliacao.hora_agendamento.substring(0, 5);
            
            let starsServicoHtml = '';
            for (let i = 1; i <= 5; i++) {
                starsServicoHtml += `<i class="bi bi-star${i <= avaliacao.avaliacao ? '-fill text-warning' : ' text-muted'} me-1"></i>`;
            }
            
            let starsProfissionalHtml = '';
            if (avaliacao.avaliacao_profissional) {
                for (let i = 1; i <= 5; i++) {
                    starsProfissionalHtml += `<i class="bi bi-star${i <= avaliacao.avaliacao_profissional ? '-fill text-info' : ' text-muted'} me-1"></i>`;
                }
            }
            
            const content = `
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="mb-3">${avaliacao.servico_nome}</h5>
                        <div class="mb-3">
                            <strong>Profissional:</strong> ${avaliacao.profissional_nome}<br>
                            <strong>Data:</strong> ${dataFormatada} às ${horaFormatada}<br>
                            <strong>Valor:</strong> R$ ${parseFloat(avaliacao.valor).toFixed(2).replace('.', ',')}
                        </div>
                        
                        <div class="mb-3">
                            <strong>Avaliação do Serviço:</strong><br>
                            <div class="d-flex align-items-center mt-1">
                                ${starsServicoHtml}
                                <span class="ms-2">(${avaliacao.avaliacao}/5 estrelas)</span>
                            </div>
                        </div>
                        
                        ${avaliacao.avaliacao_profissional ? `
                            <div class="mb-3">
                                <strong>Avaliação do Profissional:</strong><br>
                                <div class="d-flex align-items-center mt-1">
                                    ${starsProfissionalHtml}
                                    <span class="ms-2">(${avaliacao.avaliacao_profissional}/5 estrelas)</span>
                                </div>
                            </div>
                        ` : ''}
                        
                        ${avaliacao.mensagem ? `
                            <div class="mb-3">
                                <strong>Comentário sobre o Serviço:</strong><br>
                                <div class="bg-light p-3 rounded mt-2">
                                    "${avaliacao.mensagem}"
                                </div>
                            </div>
                        ` : ''}
                        
                        ${avaliacao.comentario_profissional ? `
                            <div class="mb-3">
                                <strong>Comentário sobre o Profissional:</strong><br>
                                <div class="bg-light p-3 rounded mt-2">
                                    "${avaliacao.comentario_profissional}"
                                </div>
                            </div>
                        ` : ''}
                        
                        ${avaliacao.resposta ? `
                            <div class="mb-3">
                                <strong>Resposta da Barbearia:</strong><br>
                                <div class="bg-success bg-opacity-10 border-start border-success border-3 p-3 rounded mt-2">
                                    ${avaliacao.resposta}
                                    <br><small class="text-muted">
                                        Respondido em ${new Date(avaliacao.data_resposta).toLocaleDateString('pt-BR')}
                                    </small>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded p-3">
                            <h6 class="fw-bold mb-3">Informações</h6>
                            <small class="text-muted">
                                <strong>Avaliação enviada em:</strong><br>
                                ${new Date(avaliacao.data_criacao).toLocaleString('pt-BR')}
                            </small>
                            
                            ${!avaliacao.resposta ? `
                                <div class="mt-3">
                                    <small class="text-warning">
                                        <i class="bi bi-clock me-1"></i>
                                        Aguardando resposta da barbearia
                                    </small>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('detalhes-avaliacao-content').innerHTML = content;
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


<!-- Adiciona espaço no final da página -->
<div class="mb-5"></div>
