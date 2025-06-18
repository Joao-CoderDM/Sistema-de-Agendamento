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
    if (isset($_POST['atualizar_config'])) {
        $pontos_por_real = $_POST['pontos_por_real'];
        $pontos_expiracao_dias = $_POST['pontos_expiracao_dias'];
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        try {
            $sql = "UPDATE config_fid SET pontos_por_real = ?, pontos_expiracao_dias = ?, ativo = ? WHERE id_config = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$pontos_por_real, $pontos_expiracao_dias, $ativo]);
            
            $mensagem = "Configurações atualizadas com sucesso!";
            $tipo_mensagem = "success";
        } catch (PDOException $e) {
            $mensagem = "Erro ao atualizar configurações: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
    
    if (isset($_POST['adicionar_recompensa'])) {
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $pontos_necessarios = $_POST['pontos_necessarios'];
        $tipo_recompensa = $_POST['tipo_recompensa'];
        $valor_desconto = !empty($_POST['valor_desconto']) ? $_POST['valor_desconto'] : null;
        $servico_id = !empty($_POST['servico_id']) ? $_POST['servico_id'] : null;
        
        try {
            $sql = "INSERT INTO recompensas (nome, descricao, pontos_necessarios, tipo_recompensa, valor_desconto, servico_id) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $descricao, $pontos_necessarios, $tipo_recompensa, $valor_desconto, $servico_id]);
            
            $mensagem = "Recompensa adicionada com sucesso!";
            $tipo_mensagem = "success";
        } catch (PDOException $e) {
            $mensagem = "Erro ao adicionar recompensa: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
    
    if (isset($_POST['editar_recompensa'])) {
        $id_recompensa = $_POST['id_recompensa'];
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $pontos_necessarios = $_POST['pontos_necessarios'];
        $tipo_recompensa = $_POST['tipo_recompensa'];
        $valor_desconto = !empty($_POST['valor_desconto']) ? $_POST['valor_desconto'] : null;
        $servico_id = !empty($_POST['servico_id']) ? $_POST['servico_id'] : null;
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        try {
            $sql = "UPDATE recompensas SET nome = ?, descricao = ?, pontos_necessarios = ?, tipo_recompensa = ?, 
                    valor_desconto = ?, servico_id = ?, ativo = ? WHERE id_recompensa = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $descricao, $pontos_necessarios, $tipo_recompensa, $valor_desconto, $servico_id, $ativo, $id_recompensa]);
            
            $mensagem = "Recompensa atualizada com sucesso!";
            $tipo_mensagem = "success";
        } catch (PDOException $e) {
            $mensagem = "Erro ao atualizar recompensa: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
    
    if (isset($_POST['excluir_recompensa'])) {
        $id_recompensa = $_POST['id_recompensa'];
        
        try {
            $sql = "DELETE FROM recompensas WHERE id_recompensa = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_recompensa]);
            
            $mensagem = "Recompensa excluída com sucesso!";
            $tipo_mensagem = "success";
        } catch (PDOException $e) {
            $mensagem = "Erro ao excluir recompensa: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
    
    if (isset($_POST['ajustar_pontos'])) {
        $usuario_id = $_POST['usuario_id'];
        $tipo_ajuste = $_POST['tipo_ajuste'];
        $pontos = $_POST['pontos'];
        $motivo = $_POST['motivo'];
        
        try {
            if ($tipo_ajuste === 'adicionar') {
                $sql = "UPDATE fidelidade SET pontos_atuais = pontos_atuais + ?, pontos_acumulados = pontos_acumulados + ? 
                        WHERE usuario_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$pontos, $pontos, $usuario_id]);
            } else {
                $sql = "UPDATE fidelidade SET pontos_atuais = pontos_atuais - ?, pontos_resgatados = pontos_resgatados + ? 
                        WHERE usuario_id = ? AND pontos_atuais >= ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$pontos, $pontos, $usuario_id, $pontos]);
            }
            
            $mensagem = "Pontos ajustados com sucesso!";
            $tipo_mensagem = "success";
        } catch (PDOException $e) {
            $mensagem = "Erro ao ajustar pontos: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
}

// Buscar configurações do programa
$sql = "SELECT * FROM config_fid WHERE id_config = 1";
$stmt = $pdo->query($sql);
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar todas as recompensas
$sql = "SELECT r.*, s.nome as servico_nome FROM recompensas r 
        LEFT JOIN servicos s ON r.servico_id = s.id_servico 
        ORDER BY r.pontos_necessarios";
$stmt = $pdo->query($sql);
$recompensas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar clientes com programa de fidelidade
$sql = "SELECT u.id_usuario, u.nome, u.email, u.telefone, f.pontos_atuais, f.pontos_acumulados, f.pontos_resgatados
        FROM usuario u 
        LEFT JOIN fidelidade f ON u.id_usuario = f.usuario_id 
        WHERE u.tipo_usuario = 'Cliente' AND u.ativo = 1
        ORDER BY f.pontos_atuais DESC";
$stmt = $pdo->query($sql);
$clientes_fidelidade = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar serviços para formulário
$sql = "SELECT id_servico, nome FROM servicos WHERE ativo = 1 ORDER BY nome";
$stmt = $pdo->query($sql);
$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$total_clientes_fidelidade = count(array_filter($clientes_fidelidade, fn($c) => $c['pontos_atuais'] !== null));
$total_pontos_circulacao = array_sum(array_column($clientes_fidelidade, 'pontos_atuais'));
$total_recompensas_ativas = count(array_filter($recompensas, fn($r) => $r['ativo'] == 1));

include_once('topo_sistema_adm.php');
?>

<!-- Incluir CSS personalizado -->
<link rel="stylesheet" href="../../CSS/admin_fidelidade.css">

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Header com estatísticas -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0"><i class="bi bi-star text-primary me-2"></i>Programa de Fidelidade</h2>
                    <p class="text-muted mb-0">Gerencie o programa de pontos e recompensas</p>
                </div>
                <div class="d-flex gap-3">
                    <div class="text-center">
                        <div class="badge bg-primary fs-5 px-3 py-2"><?php echo $total_clientes_fidelidade; ?></div>
                        <small class="text-muted d-block">Clientes Ativos</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-success fs-5 px-3 py-2"><?php echo $total_pontos_circulacao; ?></div>
                        <small class="text-muted d-block">Pontos em Circulação</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-info fs-5 px-3 py-2"><?php echo $total_recompensas_ativas; ?></div>
                        <small class="text-muted d-block">Recompensas Ativas</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-<?php echo $config['ativo'] ? 'success' : 'danger'; ?> fs-5 px-3 py-2">
                            <?php echo $config['ativo'] ? 'ON' : 'OFF'; ?>
                        </div>
                        <small class="text-muted d-block">Status</small>
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
            
            <!-- Navegação por abas -->
            <ul class="nav nav-tabs mb-4" id="fidelityTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="config-tab" data-bs-toggle="tab" data-bs-target="#config" type="button" role="tab">
                        <i class="bi bi-gear me-1"></i> Configurações
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="rewards-tab" data-bs-toggle="tab" data-bs-target="#rewards" type="button" role="tab">
                        <i class="bi bi-gift me-1"></i> Recompensas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="clients-tab" data-bs-toggle="tab" data-bs-target="#clients" type="button" role="tab">
                        <i class="bi bi-people me-1"></i> Clientes
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="fidelityTabsContent">
                <!-- Aba Configurações -->
                <div class="tab-pane fade show active" id="config" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Configurações do Programa</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="pontos_por_real" class="form-label">Pontos por Real Gasto</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="pontos_por_real" name="pontos_por_real" 
                                                   value="<?php echo $config['pontos_por_real']; ?>" step="0.01" min="0" required>
                                            <span class="input-group-text">pontos/R$</span>
                                        </div>
                                        <div class="form-text">Quantos pontos o cliente ganha por cada real gasto</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="pontos_expiracao_dias" class="form-label">Expiração dos Pontos</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="pontos_expiracao_dias" name="pontos_expiracao_dias" 
                                                   value="<?php echo $config['pontos_expiracao_dias']; ?>" min="1" required>
                                            <span class="input-group-text">dias</span>
                                        </div>
                                        <div class="form-text">Após quantos dias os pontos expiram</div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="ativo" name="ativo" 
                                                   <?php echo $config['ativo'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="ativo">
                                                Programa de Fidelidade Ativo
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary" name="atualizar_config">
                                    <i class="bi bi-check-circle me-1"></i>Salvar Configurações
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Aba Recompensas -->
                <div class="tab-pane fade" id="rewards" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-gift me-2"></i>Recompensas Disponíveis</h5>
                                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addRewardModal">
                                    <i class="bi bi-plus-circle"></i> Nova Recompensa
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3"><i class="bi bi-hash me-1"></i>ID</th>
                                            <th><i class="bi bi-gift me-1"></i>Recompensa</th>
                                            <th><i class="bi bi-star me-1"></i>Pontos</th>
                                            <th><i class="bi bi-tag me-1"></i>Tipo</th>
                                            <th><i class="bi bi-toggle-on me-1"></i>Status</th>
                                            <th class="text-center"><i class="bi bi-gear me-1"></i>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recompensas)): ?>
                                            <?php foreach ($recompensas as $recompensa): ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <span class="badge bg-light text-dark">#<?php echo $recompensa['id_recompensa']; ?></span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($recompensa['nome']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($recompensa['descricao']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning fs-6"><?php echo $recompensa['pontos_necessarios']; ?></span>
                                                </td>
                                                <td>
                                                    <?php
                                                    switch($recompensa['tipo_recompensa']) {
                                                        case 'desconto':
                                                            echo '<span class="badge bg-info">Desconto ' . $recompensa['valor_desconto'] . '%</span>';
                                                            break;
                                                        case 'servico':
                                                            echo '<span class="badge bg-success">Serviço: ' . htmlspecialchars($recompensa['servico_nome']) . '</span>';
                                                            break;
                                                        case 'combo':
                                                            echo '<span class="badge bg-primary">Combo</span>';
                                                            break;
                                                        case 'produto':
                                                            echo '<span class="badge bg-secondary">Produto</span>';
                                                            break;
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $recompensa['ativo'] ? 'success' : 'secondary'; ?>">
                                                        <i class="bi bi-<?php echo $recompensa['ativo'] ? 'check-circle' : 'x-circle'; ?> me-1"></i>
                                                        <?php echo $recompensa['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button class="btn btn-sm btn-outline-primary" title="Editar"
                                                                data-bs-toggle="modal" data-bs-target="#editRewardModal"
                                                                data-id="<?php echo $recompensa['id_recompensa']; ?>"
                                                                data-nome="<?php echo htmlspecialchars($recompensa['nome']); ?>"
                                                                data-descricao="<?php echo htmlspecialchars($recompensa['descricao']); ?>"
                                                                data-pontos="<?php echo $recompensa['pontos_necessarios']; ?>"
                                                                data-tipo="<?php echo $recompensa['tipo_recompensa']; ?>"
                                                                data-valor="<?php echo $recompensa['valor_desconto']; ?>"
                                                                data-servico="<?php echo $recompensa['servico_id']; ?>"
                                                                data-ativo="<?php echo $recompensa['ativo']; ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" title="Excluir"
                                                                data-bs-toggle="modal" data-bs-target="#deleteRewardModal"
                                                                data-id="<?php echo $recompensa['id_recompensa']; ?>"
                                                                data-nome="<?php echo htmlspecialchars($recompensa['nome']); ?>">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
                                                    <i class="bi bi-gift display-1 text-muted"></i>
                                                    <h5 class="text-muted mt-3">Nenhuma recompensa cadastrada</h5>
                                                    <p class="text-muted">Adicione a primeira recompensa</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Aba Clientes -->
                <div class="tab-pane fade" id="clients" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-people me-2"></i>Clientes do Programa</h5>
                                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#adjustPointsModal">
                                    <i class="bi bi-plus-circle"></i> Ajustar Pontos
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3"><i class="bi bi-person me-1"></i>Cliente</th>
                                            <th><i class="bi bi-star me-1"></i>Pontos Atuais</th>
                                            <th><i class="bi bi-collection me-1"></i>Total Acumulado</th>
                                            <th><i class="bi bi-arrow-down me-1"></i>Resgatados</th>
                                            <th class="text-center"><i class="bi bi-gear me-1"></i>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($clientes_fidelidade)): ?>
                                            <?php foreach ($clientes_fidelidade as $cliente): ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($cliente['nome']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($cliente['email']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning fs-6"><?php echo $cliente['pontos_atuais'] ?? 0; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $cliente['pontos_acumulados'] ?? 0; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success"><?php echo $cliente['pontos_resgatados'] ?? 0; ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button class="btn btn-sm btn-outline-primary rounded-pill shadow-sm" title="Ajustar Pontos"
                                                                data-bs-toggle="modal" data-bs-target="#adjustPointsModal"
                                                                data-id="<?php echo $cliente['id_usuario']; ?>"
                                                                data-nome="<?php echo htmlspecialchars($cliente['nome']); ?>"
                                                                data-pontos="<?php echo $cliente['pontos_atuais'] ?? 0; ?>">
                                                            <i class="bi bi-plus-minus me-1"></i>Ajustar Pontos
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-5">
                                                    <i class="bi bi-people display-1 text-muted"></i>
                                                    <h5 class="text-muted mt-3">Nenhum cliente no programa</h5>
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
    </div>
</div>

<!-- Modal para adicionar recompensa -->
<div class="modal fade" id="addRewardModal" tabindex="-1" aria-labelledby="addRewardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="addRewardModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Nova Recompensa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome" class="form-label">Nome da Recompensa</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="pontos_necessarios" class="form-label">Pontos Necessários</label>
                            <input type="number" class="form-control" id="pontos_necessarios" name="pontos_necessarios" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tipo_recompensa" class="form-label">Tipo de Recompensa</label>
                            <select class="form-select" id="tipo_recompensa" name="tipo_recompensa" required onchange="toggleRewardFields()">
                                <option value="">Selecione...</option>
                                <option value="desconto">Desconto</option>
                                <option value="servico">Serviço Gratuito</option>
                                <option value="combo">Combo</option>
                                <option value="produto">Produto</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3" id="valor_desconto_field" style="display: none;">
                            <label for="valor_desconto" class="form-label">Valor do Desconto (%)</label>
                            <input type="number" class="form-control" id="valor_desconto" name="valor_desconto" min="1" max="100">
                        </div>
                    </div>
                    <div class="mb-3" id="servico_field" style="display: none;">
                        <label for="servico_id" class="form-label">Serviço</label>
                        <select class="form-select" id="servico_id" name="servico_id">
                            <option value="">Selecione um serviço</option>
                            <?php foreach ($servicos as $servico): ?>
                            <option value="<?php echo $servico['id_servico']; ?>">
                                <?php echo htmlspecialchars($servico['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" name="adicionar_recompensa">
                        <i class="bi bi-check-circle me-1"></i>Salvar Recompensa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para editar recompensa -->
<div class="modal fade" id="editRewardModal" tabindex="-1" aria-labelledby="editRewardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-warning text-dark">
                <h5 class="modal-title" id="editRewardModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Editar Recompensa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_id_recompensa" name="id_recompensa">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_nome" class="form-label">Nome da Recompensa</label>
                            <input type="text" class="form-control" id="edit_nome" name="nome" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_pontos_necessarios" class="form-label">Pontos Necessários</label>
                            <input type="number" class="form-control" id="edit_pontos_necessarios" name="pontos_necessarios" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="edit_descricao" name="descricao" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_tipo_recompensa" class="form-label">Tipo de Recompensa</label>
                            <select class="form-select" id="edit_tipo_recompensa" name="tipo_recompensa" required onchange="toggleEditRewardFields()">
                                <option value="">Selecione...</option>
                                <option value="desconto">Desconto</option>
                                <option value="servico">Serviço Gratuito</option>
                                <option value="combo">Combo</option>
                                <option value="produto">Produto</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3" id="edit_valor_desconto_field" style="display: none;">
                            <label for="edit_valor_desconto" class="form-label">Valor do Desconto (%)</label>
                            <input type="number" class="form-control" id="edit_valor_desconto" name="valor_desconto" min="1" max="100">
                        </div>
                    </div>
                    <div class="mb-3" id="edit_servico_field" style="display: none;">
                        <label for="edit_servico_id" class="form-label">Serviço</label>
                        <select class="form-select" id="edit_servico_id" name="servico_id">
                            <option value="">Selecione um serviço</option>
                            <?php foreach ($servicos as $servico): ?>
                            <option value="<?php echo $servico['id_servico']; ?>">
                                <?php echo htmlspecialchars($servico['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit_ativo" name="ativo">
                            <label class="form-check-label" for="edit_ativo">Recompensa Ativa</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning" name="editar_recompensa">
                        <i class="bi bi-check-circle me-1"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para excluir recompensa -->
<div class="modal fade" id="deleteRewardModal" tabindex="-1" aria-labelledby="deleteRewardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-danger text-white">
                <h5 class="modal-title" id="deleteRewardModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="delete_id_recompensa" name="id_recompensa">
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle display-1 text-warning"></i>
                        <h5 class="mt-3">Tem certeza que deseja excluir?</h5>
                        <p>A recompensa <strong id="delete_nome_recompensa"></strong> será removida permanentemente.</p>
                        <p class="text-muted">Esta ação não pode ser desfeita.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger" name="excluir_recompensa">
                        <i class="bi bi-trash me-1"></i>Excluir Recompensa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para ajustar pontos -->
<div class="modal fade" id="adjustPointsModal" tabindex="-1" aria-labelledby="adjustPointsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title" id="adjustPointsModalLabel">
                    <i class="bi bi-plus-minus me-2"></i>Ajustar Pontos
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="adjust_usuario_id" name="usuario_id">
                    
                    <div class="alert alert-info">
                        <strong>Cliente:</strong> <span id="adjust_nome_cliente"></span><br>
                        <strong>Pontos Atuais:</strong> <span id="adjust_pontos_atuais"></span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tipo_ajuste" class="form-label">Tipo de Ajuste</label>
                            <select class="form-select" id="tipo_ajuste" name="tipo_ajuste" required>
                                <option value="">Selecione...</option>
                                <option value="adicionar">Adicionar Pontos</option>
                                <option value="remover">Remover Pontos</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="pontos" class="form-label">Quantidade de Pontos</label>
                            <input type="number" class="form-control" id="pontos" name="pontos" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo do Ajuste</label>
                        <textarea class="form-control" id="motivo" name="motivo" rows="3" 
                                  placeholder="Descreva o motivo do ajuste de pontos..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-info" name="ajustar_pontos">
                        <i class="bi bi-check-circle me-1"></i>Aplicar Ajuste
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

/* Estilo melhorado para botões */
.btn.rounded-pill {
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.btn.rounded-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
}

.btn-outline-primary.rounded-pill {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    border: none;
}

.btn-outline-primary.rounded-pill:hover {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    border: none;
}

.btn-outline-primary.rounded-pill:focus {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
}

.btn-outline-primary.rounded-pill:active {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
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
// Funções para mostrar/ocultar campos específicos
function toggleRewardFields() {
    const tipo = document.getElementById('tipo_recompensa').value;
    const valorField = document.getElementById('valor_desconto_field');
    const servicoField = document.getElementById('servico_field');
    
    // Resetar campos
    valorField.style.display = 'none';
    servicoField.style.display = 'none';
    document.getElementById('valor_desconto').required = false;
    document.getElementById('servico_id').required = false;
    
    if (tipo === 'desconto') {
        valorField.style.display = 'block';
        document.getElementById('valor_desconto').required = true;
    } else if (tipo === 'servico') {
        servicoField.style.display = 'block';
        document.getElementById('servico_id').required = true;
    }
}

function toggleEditRewardFields() {
    const tipo = document.getElementById('edit_tipo_recompensa').value;
    const valorField = document.getElementById('edit_valor_desconto_field');
    const servicoField = document.getElementById('edit_servico_field');
    
    // Resetar campos
    valorField.style.display = 'none';
    servicoField.style.display = 'none';
    document.getElementById('edit_valor_desconto').required = false;
    document.getElementById('edit_servico_id').required = false;
    
    if (tipo === 'desconto') {
        valorField.style.display = 'block';
        document.getElementById('edit_valor_desconto').required = true;
    } else if (tipo === 'servico') {
        servicoField.style.display = 'block';
        document.getElementById('edit_servico_id').required = true;
    }
}

// Script para carregar dados nos modais
document.addEventListener('click', function(e) {
    if (e.target.closest('[data-bs-target="#editRewardModal"]')) {
        const btn = e.target.closest('[data-bs-target="#editRewardModal"]');
        document.getElementById('edit_id_recompensa').value = btn.dataset.id;
        document.getElementById('edit_nome').value = btn.dataset.nome;
        document.getElementById('edit_descricao').value = btn.dataset.descricao;
        document.getElementById('edit_pontos_necessarios').value = btn.dataset.pontos;
        document.getElementById('edit_tipo_recompensa').value = btn.dataset.tipo;
        document.getElementById('edit_valor_desconto').value = btn.dataset.valor;
        document.getElementById('edit_servico_id').value = btn.dataset.servico;
        document.getElementById('edit_ativo').checked = btn.dataset.ativo == '1';
        
        toggleEditRewardFields();
    }
    
    if (e.target.closest('[data-bs-target="#deleteRewardModal"]')) {
        const btn = e.target.closest('[data-bs-target="#deleteRewardModal"]');
        document.getElementById('delete_id_recompensa').value = btn.dataset.id;
        document.getElementById('delete_nome_recompensa').textContent = btn.dataset.nome;
    }
    
    if (e.target.closest('[data-bs-target="#adjustPointsModal"]')) {
        const btn = e.target.closest('[data-bs-target="#adjustPointsModal"]');
        document.getElementById('adjust_usuario_id').value = btn.dataset.id;
        document.getElementById('adjust_nome_cliente').textContent = btn.dataset.nome;
        document.getElementById('adjust_pontos_atuais').textContent = btn.dataset.pontos;
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Adiciona espaço no final da página -->
<div class="mb-5"></div>