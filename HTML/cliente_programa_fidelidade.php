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

// Processar resgate de recompensa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resgatar_recompensa'])) {
    try {
        $recompensa_id = $_POST['recompensa_id'];
        
        // Buscar dados da recompensa
        $sql_recompensa = "SELECT * FROM recompensas WHERE id_recompensa = ? AND ativo = 1";
        $stmt_recompensa = $pdo->prepare($sql_recompensa);
        $stmt_recompensa->execute([$recompensa_id]);
        $recompensa = $stmt_recompensa->fetch(PDO::FETCH_ASSOC);
        
        if ($recompensa) {
            // Buscar pontos atuais do cliente
            $sql_pontos = "SELECT pontos_atuais FROM fidelidade WHERE usuario_id = ?";
            $stmt_pontos = $pdo->prepare($sql_pontos);
            $stmt_pontos->execute([$cliente_id]);
            $pontos_atuais = $stmt_pontos->fetchColumn() ?? 0;
            
            if ($pontos_atuais >= $recompensa['pontos_necessarios']) {
                // Deduzir pontos
                $novos_pontos = $pontos_atuais - $recompensa['pontos_necessarios'];
                
                $sql_update = "UPDATE fidelidade SET pontos_atuais = ?, pontos_resgatados = pontos_resgatados + ? WHERE usuario_id = ?";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([$novos_pontos, $recompensa['pontos_necessarios'], $cliente_id]);
                
                $mensagem = "Recompensa resgatada com sucesso! Você utilizou {$recompensa['pontos_necessarios']} pontos.";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Pontos insuficientes. Você precisa de {$recompensa['pontos_necessarios']} pontos.";
                $tipo_mensagem = "warning";
            }
        } else {
            $mensagem = "Recompensa não encontrada ou indisponível.";
            $tipo_mensagem = "danger";
        }
    } catch (Exception $e) {
        $mensagem = "Erro ao resgatar recompensa: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Buscar ou criar dados de fidelidade do cliente
$sql_fidelidade = "SELECT f.*, c.pontos_por_real, c.pontos_expiracao_dias 
                   FROM fidelidade f
                   JOIN config_fid c ON f.config_id = c.id_config
                   WHERE f.usuario_id = ?";
$stmt_fidelidade = $pdo->prepare($sql_fidelidade);
$stmt_fidelidade->execute([$cliente_id]);
$fidelidade = $stmt_fidelidade->fetch(PDO::FETCH_ASSOC);

// Se não existe registro de fidelidade, criar um
if (!$fidelidade) {
    $sql_config = "SELECT * FROM config_fid WHERE ativo = 1 LIMIT 1";
    $stmt_config = $pdo->prepare($sql_config);
    $stmt_config->execute();
    $config = $stmt_config->fetch(PDO::FETCH_ASSOC);
    
    if ($config) {
        $sql_insert = "INSERT INTO fidelidade (usuario_id, config_id, pontos_atuais, pontos_acumulados, pontos_resgatados) VALUES (?, ?, 0, 0, 0)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([$cliente_id, $config['id_config']]);
        
        // Buscar novamente os dados
        $stmt_fidelidade->execute([$cliente_id]);
        $fidelidade = $stmt_fidelidade->fetch(PDO::FETCH_ASSOC);
    }
}

// Buscar recompensas disponíveis
$sql_recompensas = "SELECT * FROM recompensas WHERE ativo = 1 ORDER BY pontos_necessarios ASC";
$stmt_recompensas = $pdo->prepare($sql_recompensas);
$stmt_recompensas->execute();
$recompensas = $stmt_recompensas->fetchAll(PDO::FETCH_ASSOC);

// Buscar histórico de pontos (baseado nos agendamentos concluídos)
$sql_historico = "SELECT a.data_agendamento, a.valor, s.nome as servico_nome, 
                         FLOOR(a.valor * ?) as pontos_ganhos
                  FROM agendamentos a
                  JOIN servicos s ON a.servico_id = s.id_servico
                  WHERE a.cliente_id = ? AND a.status = 'concluido'
                  ORDER BY a.data_agendamento DESC
                  LIMIT 10";
$stmt_historico = $pdo->prepare($sql_historico);
$stmt_historico->execute([$fidelidade['pontos_por_real'] ?? 1, $cliente_id]);
$historico_pontos = $stmt_historico->fetchAll(PDO::FETCH_ASSOC);

// Calcular estatísticas
$total_gasto = 0;
$total_pontos_ganhos = 0;
foreach ($historico_pontos as $item) {
    $total_gasto += $item['valor'];
    $total_pontos_ganhos += $item['pontos_ganhos'];
}

// Calcular próxima recompensa
$proxima_recompensa = null;
foreach ($recompensas as $recompensa) {
    if ($recompensa['pontos_necessarios'] > ($fidelidade['pontos_atuais'] ?? 0)) {
        $proxima_recompensa = $recompensa;
        break;
    }
}

include_once('topo_sistema_cliente.php');
?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1"><i class="bi bi-star text-warning me-2"></i>Programa de Fidelidade</h4>
                    <small class="text-muted">Acumule pontos e resgate recompensas incríveis</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="cliente_agendar.php" class="btn btn-custom btn-sm">
                        <i class="bi bi-calendar-plus me-1"></i>Agendar e Ganhar Pontos
                    </a>
                    <span class="badge bg-warning fs-6 px-3 py-2">
                        <i class="bi bi-star-fill me-1"></i><?php echo $fidelidade['pontos_atuais'] ?? 0; ?> pontos
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
                    <div class="card border-0 shadow-sm h-100 loyalty-card">
                        <div class="card-body text-center">
                            <div class="text-warning mb-2">
                                <i class="bi bi-star-fill display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $fidelidade['pontos_atuais'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Pontos Disponíveis</p>
                            <small class="text-warning">
                                <i class="bi bi-arrow-up"></i> 
                                Para usar agora
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-success mb-2">
                                <i class="bi bi-collection display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $fidelidade['pontos_acumulados'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Total Acumulado</p>
                            <small class="text-success">
                                <i class="bi bi-trophy"></i> 
                                Histórico completo
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-info mb-2">
                                <i class="bi bi-gift display-4"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $fidelidade['pontos_resgatados'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Pontos Resgatados</p>
                            <small class="text-info">
                                <i class="bi bi-check-circle"></i> 
                                Em recompensas
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-primary mb-2">
                                <i class="bi bi-currency-dollar display-4"></i>
                            </div>
                            <h3 class="mb-1">R$ <?php echo number_format($total_gasto, 2, ',', '.'); ?></h3>
                            <p class="text-muted mb-0">Total Investido</p>
                            <small class="text-primary">
                                <i class="bi bi-calculator"></i> 
                                Em serviços
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Próxima Recompensa -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-target me-2"></i>Próxima Recompensa</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($proxima_recompensa): ?>
                                <?php 
                                $pontos_necessarios = $proxima_recompensa['pontos_necessarios'] - ($fidelidade['pontos_atuais'] ?? 0);
                                $progresso = (($fidelidade['pontos_atuais'] ?? 0) / $proxima_recompensa['pontos_necessarios']) * 100;
                                $progresso = min(100, max(0, $progresso));
                                ?>
                                <div class="text-center mb-3">
                                    <h6 class="fw-bold"><?php echo htmlspecialchars($proxima_recompensa['nome']); ?></h6>
                                    <p class="text-muted small"><?php echo htmlspecialchars($proxima_recompensa['descricao']); ?></p>
                                </div>
                                
                                <div class="progress mb-3" style="height: 10px;">
                                    <div class="progress-bar bg-warning" role="progressbar" 
                                         style="width: <?php echo $progresso; ?>%"></div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <?php echo $fidelidade['pontos_atuais'] ?? 0; ?> / <?php echo $proxima_recompensa['pontos_necessarios']; ?> pontos
                                    </small>
                                    <span class="badge bg-warning">
                                        Faltam <?php echo $pontos_necessarios; ?> pontos
                                    </span>
                                </div>
                                
                                <div class="mt-3 text-center">
                                    <small class="text-muted">
                                        <i class="bi bi-lightbulb me-1"></i>
                                        Faça mais agendamentos para ganhar pontos!
                                    </small>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-check-circle display-1 text-success"></i>
                                    <h6 class="text-success mt-2">Parabéns!</h6>
                                    <p class="text-muted">Você pode resgatar todas as recompensas disponíveis!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Como Funciona -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Como Funciona</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <div class="feature-icon mb-3">
                                            <i class="bi bi-calendar-plus text-primary"></i>
                                        </div>
                                        <h6 class="fw-bold">1. Agende Serviços</h6>
                                        <p class="text-muted small">A cada agendamento concluído, você ganha pontos baseados no valor gasto.</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <div class="feature-icon mb-3">
                                            <i class="bi bi-star-fill text-warning"></i>
                                        </div>
                                        <h6 class="fw-bold">2. Acumule Pontos</h6>
                                        <p class="text-muted small">Ganhe <?php echo $fidelidade['pontos_por_real'] ?? 1; ?> ponto(s) para cada R$ 1,00 gasto em nossos serviços.</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <div class="feature-icon mb-3">
                                            <i class="bi bi-gift text-success"></i>
                                        </div>
                                        <h6 class="fw-bold">3. Resgate Recompensas</h6>
                                        <p class="text-muted small">Use seus pontos para resgatar descontos e serviços gratuitos.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recompensas Disponíveis -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-gradient-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-gift me-2"></i>Recompensas Disponíveis</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recompensas)): ?>
                    <div class="row">
                        <?php foreach ($recompensas as $recompensa): ?>
                        <div class="col-lg-6 col-xl-4 mb-3">
                            <div class="card h-100 reward-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($recompensa['nome']); ?></h6>
                                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($recompensa['descricao']); ?></p>
                                        </div>
                                        <div class="reward-points">
                                            <?php echo $recompensa['pontos_necessarios']; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="reward-type mb-3">
                                        <?php
                                        switch($recompensa['tipo_recompensa']) {
                                            case 'desconto':
                                                echo '<span class="badge bg-info"><i class="bi bi-percent me-1"></i>' . $recompensa['valor_desconto'] . '% de desconto</span>';
                                                break;
                                            case 'servico':
                                                echo '<span class="badge bg-success"><i class="bi bi-scissors me-1"></i>Serviço gratuito</span>';
                                                break;
                                            case 'combo':
                                                echo '<span class="badge bg-warning"><i class="bi bi-collection me-1"></i>Combo especial</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-secondary">' . ucfirst($recompensa['tipo_recompensa']) . '</span>';
                                        }
                                        ?>
                                    </div>
                                    
                                    <?php if (($fidelidade['pontos_atuais'] ?? 0) >= $recompensa['pontos_necessarios']): ?>
                                        <button class="btn btn-success btn-sm w-100" 
                                                data-bs-toggle="modal" data-bs-target="#resgatarModal"
                                                data-id="<?php echo $recompensa['id_recompensa']; ?>"
                                                data-nome="<?php echo htmlspecialchars($recompensa['nome']); ?>"
                                                data-pontos="<?php echo $recompensa['pontos_necessarios']; ?>">
                                            <i class="bi bi-check-circle me-1"></i>Resgatar Agora
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                                            <i class="bi bi-lock me-1"></i>
                                            Faltam <?php echo $recompensa['pontos_necessarios'] - ($fidelidade['pontos_atuais'] ?? 0); ?> pontos
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-gift display-1 text-muted"></i>
                        <h6 class="text-muted mt-2">Nenhuma recompensa disponível</h6>
                        <p class="text-muted">As recompensas aparecerão aqui em breve!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Histórico de Pontos -->
            <div class="card shadow-sm">
                <div class="card-header bg-gradient-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Histórico de Pontos</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($historico_pontos)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Data</th>
                                    <th>Serviço</th>
                                    <th>Valor Gasto</th>
                                    <th>Pontos Ganhos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historico_pontos as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo date('d/m/Y', strtotime($item['data_agendamento'])); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['servico_nome']); ?></td>
                                    <td>
                                        <span class="badge bg-success">R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">
                                            <i class="bi bi-star-fill me-1"></i><?php echo $item['pontos_ganhos']; ?> pontos
                                        </span>
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
                        <p class="text-muted">Complete seus primeiros agendamentos para ganhar pontos!</p>
                        <a href="cliente_agendar.php" class="btn btn-custom mt-2">
                            <i class="bi bi-calendar-plus me-2"></i>Fazer Primeiro Agendamento
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Resgate -->
<div class="modal fade" id="resgatarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-gift me-2"></i>Resgatar Recompensa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="recompensa_id" id="recompensa_id">
                    <div class="text-center mb-4">
                        <i class="bi bi-gift display-1 text-success mb-3"></i>
                        <h5 id="recompensa_nome"></h5>
                        <p class="text-muted">Você está prestes a resgatar esta recompensa</p>
                    </div>
                    
                    <div class="alert alert-success">
                        <h6><i class="bi bi-info-circle me-2"></i>Detalhes do resgate:</h6>
                        <ul class="mb-0">
                            <li>Pontos que serão utilizados: <strong id="pontos_necessarios"></strong></li>
                            <li>Pontos que você possui: <strong><?php echo $fidelidade['pontos_atuais'] ?? 0; ?></strong></li>
                            <li>Pontos restantes após resgate: <strong id="pontos_restantes"></strong></li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-warning">
                        <small>
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Esta ação não pode ser desfeita. Certifique-se de que deseja resgatar esta recompensa.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" name="resgatar_recompensa" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>Confirmar Resgate
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
    transition: transform 0.2s ease-in-out;
}

.loyalty-card {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #333;
}

.reward-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.reward-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
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

.feature-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.feature-icon i {
    font-size: 1.8rem;
}

.reward-points {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: bold;
    text-align: center;
    min-width: 60px;
    font-size: 0.9rem;
}

.reward-points::after {
    content: " pts";
    font-size: 0.7rem;
    opacity: 0.8;
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

.progress {
    border-radius: 10px;
}

.progress-bar {
    border-radius: 10px;
}
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal de resgate
    const resgatarModal = document.getElementById('resgatarModal');
    if (resgatarModal) {
        resgatarModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nome = button.getAttribute('data-nome');
            const pontos = parseInt(button.getAttribute('data-pontos'));
            const pontosAtuais = <?php echo $fidelidade['pontos_atuais'] ?? 0; ?>;
            
            document.getElementById('recompensa_id').value = id;
            document.getElementById('recompensa_nome').textContent = nome;
            document.getElementById('pontos_necessarios').textContent = pontos;
            document.getElementById('pontos_restantes').textContent = pontosAtuais - pontos;
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
