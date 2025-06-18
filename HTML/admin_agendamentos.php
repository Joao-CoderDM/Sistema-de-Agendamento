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
    if (isset($_POST['adicionar_agendamento'])) {
        $data_agendamento = $_POST['data_agendamento'];
        $hora_agendamento = $_POST['hora_agendamento'];
        $observacoes = $_POST['observacoes'];
        $valor = $_POST['valor'];
        $cliente_id = $_POST['cliente_id'];
        $profissional_id = $_POST['profissional_id'];
        $servico_id = $_POST['servico_id'];
        
        try {
            $sql = "INSERT INTO agendamentos (data_agendamento, hora_agendamento, observacoes, valor, cliente_id, profissional_id, servico_id, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'agendado')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data_agendamento, $hora_agendamento, $observacoes, $valor, $cliente_id, $profissional_id, $servico_id]);
            
            $mensagem = "Agendamento criado com sucesso!";
            $tipo_mensagem = "success";
        } catch (PDOException $e) {
            $mensagem = "Erro ao criar agendamento: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
    
    if (isset($_POST['editar_agendamento'])) {
        $id_agendamento = $_POST['id_agendamento'];
        $data_agendamento = $_POST['data_agendamento'];
        $hora_agendamento = $_POST['hora_agendamento'];
        $observacoes = $_POST['observacoes'];
        $valor = $_POST['valor'];
        $status = $_POST['status'];
        $motivo_cancelamento = $_POST['motivo_cancelamento'];
        
        try {
            $sql = "UPDATE agendamentos SET data_agendamento = ?, hora_agendamento = ?, observacoes = ?, valor = ?, status = ?, motivo_cancelamento = ? 
                    WHERE id_agendamento = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data_agendamento, $hora_agendamento, $observacoes, $valor, $status, $motivo_cancelamento, $id_agendamento]);
            
            $mensagem = "Agendamento atualizado com sucesso!";
            $tipo_mensagem = "success";
        } catch (PDOException $e) {
            $mensagem = "Erro ao atualizar agendamento: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
    
    if (isset($_POST['excluir_agendamento'])) {
        $id_agendamento = $_POST['id_agendamento'];
        
        try {
            $sql = "DELETE FROM agendamentos WHERE id_agendamento = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_agendamento]);
            
            $mensagem = "Agendamento excluído com sucesso!";
            $tipo_mensagem = "success";
        } catch (PDOException $e) {
            $mensagem = "Erro ao excluir agendamento: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
}

// Filtros
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_data = isset($_GET['data']) ? $_GET['data'] : '';
$filtro_profissional = isset($_GET['profissional']) ? $_GET['profissional'] : '';

// Buscar todos os agendamentos
$sql = "SELECT a.*, 
               c.nome as cliente_nome, c.telefone as cliente_telefone,
               p.nome as profissional_nome,
               s.nome as servico_nome
        FROM agendamentos a
        LEFT JOIN usuario c ON a.cliente_id = c.id_usuario
        LEFT JOIN usuario p ON a.profissional_id = p.id_usuario
        LEFT JOIN servicos s ON a.servico_id = s.id_servico
        WHERE 1=1";

$params = [];

// Aplicar filtros
if (!empty($filtro_status)) {
    $sql .= " AND a.status = ?";
    $params[] = $filtro_status;
}

if (!empty($filtro_data)) {
    $sql .= " AND a.data_agendamento = ?";
    $params[] = $filtro_data;
}

if (!empty($filtro_profissional)) {
    $sql .= " AND a.profissional_id = ?";
    $params[] = $filtro_profissional;
}

$sql .= " ORDER BY a.data_agendamento DESC, a.hora_agendamento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$num_resultados = count($agendamentos);

// Obter clientes para formulário
$sql_clientes = "SELECT id_usuario, nome FROM usuario WHERE tipo_usuario = 'Cliente' AND ativo = 1 ORDER BY nome";
$stmt_clientes = $pdo->query($sql_clientes);
$clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

// Obter profissionais para formulário
$sql_profissionais = "SELECT id_usuario, nome FROM usuario WHERE tipo_usuario = 'Profissional' AND ativo = 1 ORDER BY nome";
$stmt_profissionais = $pdo->query($sql_profissionais);
$profissionais = $stmt_profissionais->fetchAll(PDO::FETCH_ASSOC);

// Obter serviços para formulário
$sql_servicos = "SELECT id_servico, nome, valor FROM servicos WHERE ativo = 1 ORDER BY nome";
$stmt_servicos = $pdo->query($sql_servicos);
$servicos = $stmt_servicos->fetchAll(PDO::FETCH_ASSOC);

include_once('topo_sistema_adm.php');
?>

<!-- Incluir CSS personalizado -->
<link rel="stylesheet" href="../../CSS/admin_agendamentos.css">

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Header com estatísticas -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0"><i class="bi bi-calendar-check text-primary me-2"></i>Gerenciamento de Agendamentos</h2>
                    <p class="text-muted mb-0">Gerencie todos os agendamentos da barbearia</p>
                </div>
                <div class="d-flex gap-3">
                    <div class="text-center">
                        <div class="badge bg-primary fs-5 px-3 py-2"><?php echo $num_resultados; ?></div>
                        <small class="text-muted d-block">Total</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-warning fs-5 px-3 py-2">
                            <?php echo count(array_filter($agendamentos, fn($a) => $a['status'] == 'agendado')); ?>
                        </div>
                        <small class="text-muted d-block">Agendados</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-success fs-5 px-3 py-2">
                            <?php echo count(array_filter($agendamentos, fn($a) => $a['status'] == 'concluido')); ?>
                        </div>
                        <small class="text-muted d-block">Concluídos</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-danger fs-5 px-3 py-2">
                            <?php echo count(array_filter($agendamentos, fn($a) => $a['status'] == 'cancelado')); ?>
                        </div>
                        <small class="text-muted d-block">Cancelados</small>
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
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Lista de Agendamentos</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal">
                                <i class="bi bi-funnel"></i> Filtrar
                            </button>
                            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                                <i class="bi bi-plus-circle"></i> Novo Agendamento
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($filtro_status) || !empty($filtro_data) || !empty($filtro_profissional)): ?>
                    <div class="p-3 bg-light border-bottom">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="fw-bold text-muted">Filtros aplicados:</span>
                            
                            <?php if (!empty($filtro_status)): ?>
                            <span class="badge bg-info">
                                <i class="bi bi-funnel me-1"></i>Status: <?php echo ucfirst($filtro_status); ?>
                                <a href="?<?php echo (!empty($filtro_data) ? 'data='.$filtro_data.'&' : '') . (!empty($filtro_profissional) ? 'profissional='.$filtro_profissional : ''); ?>" 
                                   class="text-white text-decoration-none ms-1">×</a>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($filtro_data)): ?>
                            <span class="badge bg-warning">
                                <i class="bi bi-calendar me-1"></i>Data: <?php echo date('d/m/Y', strtotime($filtro_data)); ?>
                                <a href="?<?php echo (!empty($filtro_status) ? 'status='.$filtro_status.'&' : '') . (!empty($filtro_profissional) ? 'profissional='.$filtro_profissional : ''); ?>" 
                                   class="text-white text-decoration-none ms-1">×</a>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($filtro_profissional)): ?>
                            <span class="badge bg-secondary">
                                <i class="bi bi-person me-1"></i>Profissional: 
                                <?php 
                                $prof_nome = array_filter($profissionais, fn($p) => $p['id_usuario'] == $filtro_profissional);
                                echo !empty($prof_nome) ? reset($prof_nome)['nome'] : 'ID '.$filtro_profissional;
                                ?>
                                <a href="?<?php echo (!empty($filtro_status) ? 'status='.$filtro_status.'&' : '') . (!empty($filtro_data) ? 'data='.$filtro_data : ''); ?>" 
                                   class="text-white text-decoration-none ms-1">×</a>
                            </span>
                            <?php endif; ?>
                            
                            <a href="admin_agendamentos.php" class="btn btn-sm btn-outline-secondary ms-2">
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
                                    <th><i class="bi bi-calendar me-1"></i>Data/Hora</th>
                                    <th><i class="bi bi-person me-1"></i>Cliente</th>
                                    <th><i class="bi bi-scissors me-1"></i>Profissional</th>
                                    <th><i class="bi bi-gear me-1"></i>Serviço</th>
                                    <th><i class="bi bi-cash me-1"></i>Valor</th>
                                    <th><i class="bi bi-circle me-1"></i>Status</th>
                                    <th class="text-center"><i class="bi bi-tools me-1"></i>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($num_resultados > 0): ?>
                                    <?php foreach ($agendamentos as $agendamento): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge bg-light text-dark">#<?php echo $agendamento['id_agendamento']; ?></span>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-bold"><?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?></div>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($agendamento['cliente_nome']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($agendamento['cliente_telefone']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($agendamento['profissional_nome']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($agendamento['servico_nome']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success fs-6">R$ <?php echo number_format($agendamento['valor'], 2, ',', '.'); ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            switch($agendamento['status']) {
                                                case 'agendado':
                                                    echo '<span class="badge bg-warning"><i class="bi bi-clock me-1"></i>Agendado</span>';
                                                    break;
                                                case 'concluido':
                                                    echo '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Concluído</span>';
                                                    break;
                                                case 'cancelado':
                                                    echo '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Cancelado</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <button class="btn btn-sm btn-outline-primary" title="Editar"
                                                        data-bs-toggle="modal" data-bs-target="#editModal"
                                                        data-id="<?php echo $agendamento['id_agendamento']; ?>"
                                                        data-data="<?php echo $agendamento['data_agendamento']; ?>"
                                                        data-hora="<?php echo $agendamento['hora_agendamento']; ?>"
                                                        data-observacoes="<?php echo htmlspecialchars($agendamento['observacoes']); ?>"
                                                        data-valor="<?php echo $agendamento['valor']; ?>"
                                                        data-status="<?php echo $agendamento['status']; ?>"
                                                        data-motivo="<?php echo htmlspecialchars($agendamento['motivo_cancelamento']); ?>"
                                                        data-cliente="<?php echo htmlspecialchars($agendamento['cliente_nome']); ?>"
                                                        data-profissional="<?php echo htmlspecialchars($agendamento['profissional_nome']); ?>"
                                                        data-servico="<?php echo htmlspecialchars($agendamento['servico_nome']); ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary" title="Excluir"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                        data-id="<?php echo $agendamento['id_agendamento']; ?>"
                                                        data-cliente="<?php echo htmlspecialchars($agendamento['cliente_nome']); ?>"
                                                        data-data="<?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?>"
                                                        data-hora="<?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <i class="bi bi-calendar-x display-1 text-muted"></i>
                                            <h5 class="text-muted mt-3">Nenhum agendamento encontrado</h5>
                                            <p class="text-muted">Crie o primeiro agendamento ou ajuste os filtros</p>
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

<!-- Modal para adicionar agendamento -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="addModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Novo Agendamento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="data_agendamento" class="form-label">Data</label>
                            <input type="date" class="form-control" id="data_agendamento" name="data_agendamento" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="hora_agendamento" class="form-label">Hora</label>
                            <input type="time" class="form-control" id="hora_agendamento" name="hora_agendamento" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cliente_id" class="form-label">Cliente</label>
                            <select class="form-select" id="cliente_id" name="cliente_id" required>
                                <option value="">Selecione um cliente</option>
                                <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['id_usuario']; ?>">
                                    <?php echo htmlspecialchars($cliente['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="profissional_id" class="form-label">Profissional</label>
                            <select class="form-select" id="profissional_id" name="profissional_id" required>
                                <option value="">Selecione um profissional</option>
                                <?php foreach ($profissionais as $profissional): ?>
                                <option value="<?php echo $profissional['id_usuario']; ?>">
                                    <?php echo htmlspecialchars($profissional['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="servico_id" class="form-label">Serviço</label>
                            <select class="form-select" id="servico_id" name="servico_id" required onchange="updateValor()">
                                <option value="">Selecione um serviço</option>
                                <?php foreach ($servicos as $servico): ?>
                                <option value="<?php echo $servico['id_servico']; ?>" data-valor="<?php echo $servico['valor']; ?>">
                                    <?php echo htmlspecialchars($servico['nome']); ?> - R$ <?php echo number_format($servico['valor'], 2, ',', '.'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="valor" class="form-label">Valor (R$)</label>
                            <input type="number" class="form-control" id="valor" name="valor" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3" placeholder="Observações sobre o agendamento..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" name="adicionar_agendamento">
                        <i class="bi bi-check-circle me-1"></i>Criar Agendamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para editar agendamento -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-warning text-dark">
                <h5 class="modal-title" id="editModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Editar Agendamento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_id_agendamento" name="id_agendamento">
                    
                    <div class="alert alert-info">
                        <strong>Cliente:</strong> <span id="edit_cliente_nome"></span><br>
                        <strong>Profissional:</strong> <span id="edit_profissional_nome"></span><br>
                        <strong>Serviço:</strong> <span id="edit_servico_nome"></span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_data_agendamento" class="form-label">Data</label>
                            <input type="date" class="form-control" id="edit_data_agendamento" name="data_agendamento" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_hora_agendamento" class="form-label">Hora</label>
                            <input type="time" class="form-control" id="edit_hora_agendamento" name="hora_agendamento" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_valor" class="form-label">Valor (R$)</label>
                            <input type="number" class="form-control" id="edit_valor" name="valor" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required onchange="toggleMotivoField()">
                                <option value="agendado">Agendado</option>
                                <option value="concluido">Concluído</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="edit_observacoes" name="observacoes" rows="3"></textarea>
                    </div>
                    <div class="mb-3" id="motivo_cancelamento_field" style="display: none;">
                        <label for="edit_motivo_cancelamento" class="form-label">Motivo do Cancelamento</label>
                        <textarea class="form-control" id="edit_motivo_cancelamento" name="motivo_cancelamento" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning" name="editar_agendamento">
                        <i class="bi bi-check-circle me-1"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para excluir agendamento -->
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
                    <input type="hidden" id="delete_id_agendamento" name="id_agendamento">
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle display-1 text-warning"></i>
                        <h5 class="mt-3">Tem certeza que deseja excluir?</h5>
                        <p>O agendamento de <strong id="delete_cliente_nome"></strong> para o dia <strong id="delete_data_hora"></strong> será removido permanentemente.</p>
                        <p class="text-muted">Esta ação não pode ser desfeita.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger" name="excluir_agendamento">
                        <i class="bi bi-trash me-1"></i>Excluir Agendamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para filtrar agendamentos -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title" id="filterModalLabel">
                    <i class="bi bi-funnel me-2"></i>Filtrar Agendamentos
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="get" action="admin_agendamentos.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="filter_status" class="form-label">Status</label>
                        <select class="form-select" id="filter_status" name="status">
                            <option value="">Todos os status</option>
                            <option value="agendado" <?php echo $filtro_status == 'agendado' ? 'selected' : ''; ?>>Agendado</option>
                            <option value="concluido" <?php echo $filtro_status == 'concluido' ? 'selected' : ''; ?>>Concluído</option>
                            <option value="cancelado" <?php echo $filtro_status == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="filter_data" class="form-label">Data</label>
                        <input type="date" class="form-control" id="filter_data" name="data" value="<?php echo htmlspecialchars($filtro_data); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="filter_profissional" class="form-label">Profissional</label>
                        <select class="form-select" id="filter_profissional" name="profissional">
                            <option value="">Todos os profissionais</option>
                            <?php foreach ($profissionais as $profissional): ?>
                            <option value="<?php echo $profissional['id_usuario']; ?>" <?php echo $filtro_profissional == $profissional['id_usuario'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($profissional['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="admin_agendamentos.php" class="btn btn-secondary">
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
.bg-gradient-warning {
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

/* Estilo melhorado para botões */
.btn.rounded-pill {
    padding: 0.5rem 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.btn.rounded-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
}

.btn-outline-primary.rounded-pill {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
}

.btn-outline-primary.rounded-pill:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    color: white;
    border: none;
}

.btn-outline-danger.rounded-pill {
    background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
    color: white;
    border: none;
}

.btn-outline-danger.rounded-pill:hover {
    background: linear-gradient(135deg, #ff4b2b 0%, #ff416c 100%);
    color: white;
    border: none;
}

.btn.rounded-pill:focus {
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn.rounded-pill:active {
    transform: translateY(0);
}

/* Efeito hover para ícones dentro dos botões */
.btn.rounded-pill i {
    transition: transform 0.2s ease;
}

.btn.rounded-pill:hover i {
    transform: scale(1.1);
}
</style>

<script>
// Função para atualizar o valor quando selecionar um serviço
function updateValor() {
    const servicoSelect = document.getElementById('servico_id');
    const valorInput = document.getElementById('valor');
    const selectedOption = servicoSelect.options[servicoSelect.selectedIndex];
    
    if (selectedOption.dataset.valor) {
        valorInput.value = selectedOption.dataset.valor;
    }
}

// Função para mostrar/ocultar campo de motivo de cancelamento
function toggleMotivoField() {
    const statusSelect = document.getElementById('edit_status');
    const motivoField = document.getElementById('motivo_cancelamento_field');
    
    if (statusSelect.value === 'cancelado') {
        motivoField.style.display = 'block';
        document.getElementById('edit_motivo_cancelamento').required = true;
    } else {
        motivoField.style.display = 'none';
        document.getElementById('edit_motivo_cancelamento').required = false;
    }
}

// Script para carregar dados nos modais
document.addEventListener('click', function(e) {
    if (e.target.closest('[data-bs-target="#editModal"]')) {
        const btn = e.target.closest('[data-bs-target="#editModal"]');
        document.getElementById('edit_id_agendamento').value = btn.dataset.id;
        document.getElementById('edit_data_agendamento').value = btn.dataset.data;
        document.getElementById('edit_hora_agendamento').value = btn.dataset.hora;
        document.getElementById('edit_observacoes').value = btn.dataset.observacoes;
        document.getElementById('edit_valor').value = btn.dataset.valor;
        document.getElementById('edit_status').value = btn.dataset.status;
        document.getElementById('edit_motivo_cancelamento').value = btn.dataset.motivo;
        document.getElementById('edit_cliente_nome').textContent = btn.dataset.cliente;
        document.getElementById('edit_profissional_nome').textContent = btn.dataset.profissional;
        document.getElementById('edit_servico_nome').textContent = btn.dataset.servico;
        
        // Mostrar/ocultar campo de motivo
        toggleMotivoField();
    }
    
    if (e.target.closest('[data-bs-target="#deleteModal"]')) {
        const btn = e.target.closest('[data-bs-target="#deleteModal"]');
        document.getElementById('delete_id_agendamento').value = btn.dataset.id;
        document.getElementById('delete_cliente_nome').textContent = btn.dataset.cliente;
        document.getElementById('delete_data_hora').textContent = btn.dataset.data + ' às ' + btn.dataset.hora;
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Adiciona espaço no final da página -->
<div class="mb-5"></div>