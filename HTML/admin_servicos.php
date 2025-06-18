<?php
session_start();

// Verifica se o usuário está logado e é admin
if(!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'Administrador'){
    header('Location: login.php');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
include_once('../Conexao/conexao.php');

// Processar operações de CRUD
$mensagem = '';
$tipo_mensagem = '';

// CRUD para categorias
// 1. Adicionar nova categoria
if (isset($_POST['adicionar_categoria'])) {
    $nome = $_POST['nome_categoria'];
    $descricao = $_POST['descricao_categoria'];
    
    $sql = "INSERT INTO categorias (nome, descricao, ativo) VALUES (?, ?, 1)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$nome, $descricao])) {
        $mensagem = "Categoria adicionada com sucesso!";
        $tipo_mensagem = "success";
    } else {
        $mensagem = "Erro ao adicionar categoria: " . implode(" ", $stmt->errorInfo());
        $tipo_mensagem = "danger";
    }
}

// 2. Editar categoria
if (isset($_POST['editar_categoria'])) {
    $id = $_POST['id_categoria'];
    $nome = $_POST['nome_categoria'];
    $descricao = $_POST['descricao_categoria'];
    
    $sql = "UPDATE categorias SET nome=?, descricao=? WHERE id_categoria=?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$nome, $descricao, $id])) {
        $mensagem = "Categoria atualizada com sucesso!";
        $tipo_mensagem = "success";
    } else {
        $mensagem = "Erro ao atualizar categoria: " . implode(" ", $stmt->errorInfo());
        $tipo_mensagem = "danger";
    }
}

// 3. Excluir categoria
if (isset($_POST['excluir_categoria'])) {
    $id = $_POST['id_categoria'];
    
    // Verificar se existe algum serviço usando esta categoria
    $sql_check = "SELECT COUNT(*) FROM servicos WHERE categoria = (SELECT nome FROM categorias WHERE id_categoria = ?)";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$id]);
    $count = $stmt_check->fetchColumn();
    
    if ($count > 0) {
        // Se houver serviços, apenas desativar a categoria
        $sql = "UPDATE categorias SET ativo = 0 WHERE id_categoria = ?";
        $mensagem_tipo = "desativada";
    } else {
        // Se não houver serviços, excluir a categoria
        $sql = "DELETE FROM categorias WHERE id_categoria = ?";
        $mensagem_tipo = "excluída";
    }
    
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$id])) {
        $mensagem = "Categoria $mensagem_tipo com sucesso!";
        $tipo_mensagem = "success";
    } else {
        $mensagem = "Erro ao excluir categoria: " . implode(" ", $stmt->errorInfo());
        $tipo_mensagem = "danger";
    }
}

// 1. Adicionar novo serviço
if (isset($_POST['adicionar_servico'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $valor = $_POST['valor'];
    $duracao = $_POST['duracao'];
    $categoria = $_POST['categoria'];
    
    $sql = "INSERT INTO servicos (nome, descricao, valor, duracao, categoria, ativo) VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$nome, $descricao, $valor, $duracao, $categoria])) {
        $mensagem = "Serviço adicionado com sucesso!";
        $tipo_mensagem = "success";
    } else {
        $mensagem = "Erro ao adicionar serviço: " . implode(" ", $stmt->errorInfo());
        $tipo_mensagem = "danger";
    }
}

// 2. Editar serviço
if (isset($_POST['editar_servico'])) {
    $id = $_POST['id_servico'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $valor = $_POST['valor'];
    $duracao = $_POST['duracao'];
    $categoria = $_POST['categoria'];
    
    $sql = "UPDATE servicos SET nome=?, descricao=?, valor=?, duracao=?, categoria=? WHERE id_servico=?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$nome, $descricao, $valor, $duracao, $categoria, $id])) {
        $mensagem = "Serviço atualizado com sucesso!";
        $tipo_mensagem = "success";
    } else {
        $mensagem = "Erro ao atualizar serviço: " . implode(" ", $stmt->errorInfo());
        $tipo_mensagem = "danger";
    }
}

// 3. Excluir serviço
if (isset($_POST['excluir_servico'])) {
    $id = $_POST['id_servico'];
    
    // Verificar se existe algum agendamento usando este serviço
    $sql_check = "SELECT COUNT(*) FROM agendamentos WHERE id_servico = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$id]);
    $count = $stmt_check->fetchColumn();
    
    if ($count > 0) {
        // Se houver agendamentos, apenas desativar o serviço
        $sql = "UPDATE servicos SET ativo = 0 WHERE id_servico = ?";
        $mensagem_tipo = "desativado";
    } else {
        // Se não houver agendamentos, excluir o serviço
        $sql = "DELETE FROM servicos WHERE id_servico = ?";
        $mensagem_tipo = "excluído";
    }
    
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$id])) {
        $mensagem = "Serviço $mensagem_tipo com sucesso!";
        $tipo_mensagem = "success";
    } else {
        $mensagem = "Erro ao excluir serviço: " . implode(" ", $stmt->errorInfo());
        $tipo_mensagem = "danger";
    }
}

// Verificar se existem filtros aplicados
$filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';

// Consultar todos os serviços (com filtros se aplicáveis)
$sql = "SELECT id_servico, nome, descricao, valor, duracao, categoria, ativo FROM servicos WHERE 1=1";
$params = [];

// Adicionar condições de filtro se existirem
if (!empty($filtro_categoria)) {
    $sql .= " AND categoria = ?";
    $params[] = $filtro_categoria;
}

if ($filtro_status !== '') {
    $sql .= " AND ativo = ?";
    $params[] = ($filtro_status == 'ativo' ? 1 : 0);
}

if (!empty($filtro_pesquisa)) {
    $sql .= " AND (nome LIKE ? OR descricao LIKE ?)";
    $params[] = '%' . $filtro_pesquisa . '%';
    $params[] = '%' . $filtro_pesquisa . '%';
}

$sql .= " ORDER BY id_servico DESC";
// Executar a consulta com os filtros
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resultado = $stmt;
$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$num_resultados = count($servicos);

// Obter todas as categorias para o filtro e formulários
$sql_categorias = "SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome";
$stmt_categorias = $pdo->query($sql_categorias);
$categorias = [];
while ($row = $stmt_categorias->fetch(PDO::FETCH_ASSOC)) {
    $categorias[] = $row;
}

// Obter todas as categorias (incluindo inativas) para gerenciamento com filtro de pesquisa
$sql_todas_categorias = "SELECT * FROM categorias";
$params_categorias = [];

if (!empty($filtro_pesquisa)) {
    $sql_todas_categorias .= " WHERE nome LIKE ? OR descricao LIKE ?";
    $params_categorias[] = '%' . $filtro_pesquisa . '%';
    $params_categorias[] = '%' . $filtro_pesquisa . '%';
}

$sql_todas_categorias .= " ORDER BY id_categoria DESC";
$stmt_todas_categorias = $pdo->prepare($sql_todas_categorias);
$stmt_todas_categorias->execute($params_categorias);
$todas_categorias = $stmt_todas_categorias;

include_once('topo_sistema_adm.php');
?>

<!-- Incluir arquivo CSS personalizado -->
<link rel="stylesheet" href="../CSS/admin_servicos.css">

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Header com estatísticas -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0"><i class="bi bi-scissors text-primary me-2"></i>Gerenciamento de Serviços</h2>
                    <p class="text-muted mb-0">Gerencie serviços e categorias da barbearia</p>
                </div>
                <div class="d-flex gap-3">
                    <div class="text-center">
                        <div class="badge bg-primary fs-5 px-3 py-2"><?php echo $num_resultados; ?></div>
                        <small class="text-muted d-block">Total</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-success fs-5 px-3 py-2">
                            <?php echo count(array_filter($servicos, fn($s) => $s['ativo'] == 1)); ?>
                        </div>
                        <small class="text-muted d-block">Ativos</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-info fs-5 px-3 py-2"><?php echo count($categorias); ?></div>
                        <small class="text-muted d-block">Categorias</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-warning fs-5 px-3 py-2">
                            <?php echo count(array_filter($servicos, fn($s) => $s['ativo'] == 0)); ?>
                        </div>
                        <small class="text-muted d-block">Inativos</small>
                    </div>
                </div>
            </div>
            
            <!-- Barra de Pesquisa -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="get" action="admin_servicos.php" class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="pesquisa" class="form-label">
                                <i class="bi bi-search me-1"></i>Pesquisar
                            </label>
                            <input type="text" class="form-control" id="pesquisa" name="pesquisa" 
                                   placeholder="Digite o nome do serviço ou categoria..." 
                                   value="<?php echo htmlspecialchars($filtro_pesquisa); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="categoria_filter" class="form-label">Categoria</label>
                            <select class="form-select" id="categoria_filter" name="categoria">
                                <option value="">Todas</option>
                                <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo htmlspecialchars($categoria['nome']); ?>" 
                                        <?php echo $filtro_categoria == $categoria['nome'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status_filter" class="form-label">Status</label>
                            <select class="form-select" id="status_filter" name="status">
                                <option value="">Todos</option>
                                <option value="ativo" <?php echo $filtro_status == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                <option value="inativo" <?php echo $filtro_status == 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <div class="d-flex gap-1">
                                <button type="submit" class="btn btn-primary" title="Pesquisar">
                                    <i class="bi bi-search"></i>
                                </button>
                                <?php if (!empty($filtro_pesquisa) || !empty($filtro_categoria) || $filtro_status !== ''): ?>
                                <a href="admin_servicos.php" class="btn btn-outline-secondary" title="Limpar">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Navegação por abas -->
            <ul class="nav nav-tabs mb-4" id="managementTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="services-tab" data-bs-toggle="tab" data-bs-target="#services" type="button" role="tab" aria-controls="services" aria-selected="true">
                        <i class="bi bi-scissors me-1"></i> Serviços
                        <?php if ($num_resultados > 0): ?>
                        <span class="badge bg-secondary ms-1"><?php echo $num_resultados; ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab" aria-controls="categories" aria-selected="false">
                        <i class="bi bi-tags me-1"></i> Categorias
                        <?php if ($todas_categorias->rowCount() > 0): ?>
                        <span class="badge bg-secondary ms-1"><?php echo $todas_categorias->rowCount(); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>

            <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="tab-content" id="managementTabsContent">
                <!-- Aba de Serviços -->
                <div class="tab-pane fade show active" id="services" role="tabpanel" aria-labelledby="services-tab">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-list-ul me-2"></i>Lista de Serviços
                                    <?php if (!empty($filtro_pesquisa) || !empty($filtro_categoria) || $filtro_status !== ''): ?>
                                    <small class="text-white-50">
                                        (<?php echo $num_resultados; ?> resultado<?php echo $num_resultados != 1 ? 's' : ''; ?> encontrado<?php echo $num_resultados != 1 ? 's' : ''; ?>)
                                    </small>
                                    <?php endif; ?>
                                </h5>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#filterServiceModal">
                                        <i class="bi bi-funnel"></i> Filtros Avançados
                                    </button>
                                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                                        <i class="bi bi-plus-circle"></i> Novo Serviço
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($filtro_categoria) || $filtro_status !== '' || !empty($filtro_pesquisa)): ?>
                            <div class="p-3 bg-light border-bottom">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="fw-bold text-muted">Filtros aplicados:</span>
                                    
                                    <?php if (!empty($filtro_pesquisa)): ?>
                                    <span class="badge bg-primary">
                                        <i class="bi bi-search me-1"></i>Pesquisa: "<?php echo htmlspecialchars($filtro_pesquisa); ?>"
                                        <a href="?<?php echo http_build_query(array_filter(['categoria' => $filtro_categoria, 'status' => $filtro_status])); ?>" class="text-white text-decoration-none ms-1">×</a>
                                    </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($filtro_categoria)): ?>
                                    <span class="badge bg-info">
                                        <i class="bi bi-tag me-1"></i>Categoria: <?php echo htmlspecialchars($filtro_categoria); ?>
                                        <a href="?<?php echo http_build_query(array_filter(['pesquisa' => $filtro_pesquisa, 'status' => $filtro_status])); ?>" class="text-white text-decoration-none ms-1">×</a>
                                    </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($filtro_status !== ''): ?>
                                    <span class="badge bg-warning">
                                        <i class="bi bi-toggle-on me-1"></i>Status: <?php echo $filtro_status == 'ativo' ? 'Ativo' : 'Inativo'; ?>
                                        <a href="?<?php echo http_build_query(array_filter(['pesquisa' => $filtro_pesquisa, 'categoria' => $filtro_categoria])); ?>" class="text-white text-decoration-none ms-1">×</a>
                                    </span>
                                    <?php endif; ?>
                                    
                                    <a href="admin_servicos.php" class="btn btn-sm btn-outline-secondary ms-2">
                                        <i class="bi bi-x-circle me-1"></i>Limpar todos
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3"><i class="bi bi-hash me-1"></i>ID</th>
                                            <th><i class="bi bi-scissors me-1"></i>Serviço</th>
                                            <th><i class="bi bi-cash me-1"></i>Preço</th>
                                            <th><i class="bi bi-clock me-1"></i>Duração</th>
                                            <th><i class="bi bi-tag me-1"></i>Categoria</th>
                                            <th><i class="bi bi-toggle-on me-1"></i>Status</th>
                                            <th class="text-center"><i class="bi bi-gear me-1"></i>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($num_resultados > 0): ?>
                                            <?php foreach ($servicos as $servico): ?>
                                            <tr<?php echo $servico['ativo'] ? '' : ' class="table-secondary"'; ?>>
                                                <td class="ps-3">
                                                    <span class="badge bg-light text-dark">#<?php echo $servico['id_servico']; ?></span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($servico['nome']); ?></div>
                                                        <?php if (!empty($servico['descricao'])): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars(substr($servico['descricao'], 0, 60)) . (strlen($servico['descricao']) > 60 ? '...' : ''); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success fs-6">R$ <?php echo number_format($servico['valor'], 2, ',', '.'); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $servico['duracao']; ?> min</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($servico['categoria']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $servico['ativo'] ? 'success' : 'secondary'; ?>">
                                                        <i class="bi bi-<?php echo $servico['ativo'] ? 'check-circle' : 'x-circle'; ?> me-1"></i>
                                                        <?php echo $servico['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button class="btn btn-sm btn-outline-primary" title="Editar"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editServiceModal" 
                                                                data-id="<?php echo $servico['id_servico']; ?>"
                                                                data-nome="<?php echo htmlspecialchars($servico['nome']); ?>"
                                                                data-descricao="<?php echo htmlspecialchars($servico['descricao']); ?>"
                                                                data-valor="<?php echo $servico['valor']; ?>"
                                                                data-duracao="<?php echo $servico['duracao']; ?>"
                                                                data-categoria="<?php echo htmlspecialchars($servico['categoria']); ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" title="Excluir"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteServiceModal" 
                                                                data-id="<?php echo $servico['id_servico']; ?>"
                                                                data-nome="<?php echo htmlspecialchars($servico['nome']); ?>">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-5">
                                                    <i class="bi bi-scissors display-1 text-muted"></i>
                                                    <h5 class="text-muted mt-3">Nenhum serviço encontrado</h5>
                                                    <p class="text-muted">Adicione o primeiro serviço ou ajuste os filtros</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Aba de Categorias -->
                <div class="tab-pane fade" id="categories" role="tabpanel" aria-labelledby="categories-tab">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-tags me-2"></i>Lista de Categorias
                                    <?php if (!empty($filtro_pesquisa)): ?>
                                    <small class="text-white-50">
                                        (<?php echo $todas_categorias->rowCount(); ?> resultado<?php echo $todas_categorias->rowCount() != 1 ? 's' : ''; ?> encontrado<?php echo $todas_categorias->rowCount() != 1 ? 's' : ''; ?>)
                                    </small>
                                    <?php endif; ?>
                                </h5>
                                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                    <i class="bi bi-plus-circle"></i> Nova Categoria
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3"><i class="bi bi-hash me-1"></i>ID</th>
                                            <th><i class="bi bi-tag me-1"></i>Nome</th>
                                            <th><i class="bi bi-card-text me-1"></i>Descrição</th>
                                            <th><i class="bi bi-toggle-on me-1"></i>Status</th>
                                            <th><i class="bi bi-calendar me-1"></i>Data Criação</th>
                                            <th class="text-center"><i class="bi bi-gear me-1"></i>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($todas_categorias->rowCount() > 0): ?>
                                            <?php while ($categoria = $todas_categorias->fetch(PDO::FETCH_ASSOC)): ?>
                                            <tr<?php echo $categoria['ativo'] ? '' : ' class="table-secondary"'; ?>>
                                                <td class="ps-3">
                                                    <span class="badge bg-light text-dark">#<?php echo $categoria['id_categoria']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary fs-6">
                                                        <?php 
                                                        $nome = htmlspecialchars($categoria['nome']);
                                                        if (!empty($filtro_pesquisa)) {
                                                            $nome = str_ireplace($filtro_pesquisa, '<mark class="bg-warning text-dark">' . $filtro_pesquisa . '</mark>', $nome);
                                                        }
                                                        echo $nome;
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($categoria['descricao'])): ?>
                                                        <small class="text-muted">
                                                            <?php 
                                                            $descricao = htmlspecialchars(substr($categoria['descricao'], 0, 60)) . (strlen($categoria['descricao']) > 60 ? '...' : '');
                                                            if (!empty($filtro_pesquisa)) {
                                                                $descricao = str_ireplace($filtro_pesquisa, '<mark class="bg-warning text-dark">' . $filtro_pesquisa . '</mark>', $descricao);
                                                            }
                                                            echo $descricao;
                                                            ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $categoria['ativo'] ? 'success' : 'secondary'; ?>">
                                                        <i class="bi bi-<?php echo $categoria['ativo'] ? 'check-circle' : 'x-circle'; ?> me-1"></i>
                                                        <?php echo $categoria['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($categoria['data_criacao'])); ?></small>
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button class="btn btn-sm btn-outline-primary" title="Editar"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editCategoryModal" 
                                                                data-id="<?php echo $categoria['id_categoria']; ?>"
                                                                data-nome="<?php echo htmlspecialchars($categoria['nome']); ?>"
                                                                data-descricao="<?php echo htmlspecialchars($categoria['descricao']); ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" title="Excluir"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteCategoryModal" 
                                                                data-id="<?php echo $categoria['id_categoria']; ?>"
                                                                data-nome="<?php echo htmlspecialchars($categoria['nome']); ?>">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
                                                    <i class="bi bi-<?php echo !empty($filtro_pesquisa) ? 'search' : 'tags'; ?> display-1 text-muted"></i>
                                                    <h5 class="text-muted mt-3">
                                                        <?php echo !empty($filtro_pesquisa) ? 'Nenhuma categoria encontrada' : 'Nenhuma categoria cadastrada'; ?>
                                                    </h5>
                                                    <?php if (!empty($filtro_pesquisa)): ?>
                                                    <p class="text-muted">Tente ajustar os termos de pesquisa</p>
                                                    <a href="admin_servicos.php" class="btn btn-outline-primary">
                                                        <i class="bi bi-arrow-left me-1"></i>Voltar à lista completa
                                                    </a>
                                                    <?php else: ?>
                                                    <p class="text-muted">Adicione a primeira categoria</p>
                                                    <?php endif; ?>
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

<!-- Modal para adicionar serviço -->
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="addServiceModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Adicionar Novo Serviço
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addServiceForm" method="post" action="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome" class="form-label">Nome do Serviço</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="categoria" class="form-label">Categoria</label>
                            <select class="form-select" id="categoria" name="categoria" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo htmlspecialchars($categoria['nome']); ?>">
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="valor" class="form-label">Preço (R$)</label>
                            <input type="number" class="form-control" id="valor" name="valor" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="duracao" class="form-label">Duração (minutos)</label>
                            <input type="number" class="form-control" id="duracao" name="duracao" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3" placeholder="Descreva o serviço oferecido..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" name="adicionar_servico">
                        <i class="bi bi-check-circle me-1"></i>Salvar Serviço
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para editar serviço -->
<div class="modal fade" id="editServiceModal" tabindex="-1" aria-labelledby="editServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-warning text-dark">
                <h5 class="modal-title" id="editServiceModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Editar Serviço
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editServiceForm" method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_id_servico" name="id_servico">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_nome" class="form-label">Nome do Serviço</label>
                            <input type="text" class="form-control" id="edit_nome" name="nome" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_categoria" class="form-label">Categoria</label>
                            <select class="form-select" id="edit_categoria" name="categoria" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo htmlspecialchars($categoria['nome']); ?>">
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_valor" class="form-label">Preço (R$)</label>
                            <input type="number" class="form-control" id="edit_valor" name="valor" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_duracao" class="form-label">Duração (minutos)</label>
                            <input type="number" class="form-control" id="edit_duracao" name="duracao" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="edit_descricao" name="descricao" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning" name="editar_servico">
                        <i class="bi bi-check-circle me-1"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para excluir serviço -->
<div class="modal fade" id="deleteServiceModal" tabindex="-1" aria-labelledby="deleteServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-danger text-white">
                <h5 class="modal-title" id="deleteServiceModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteServiceForm" method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="delete_id_servico" name="id_servico">
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle display-1 text-warning"></i>
                        <h5 class="mt-3">Tem certeza que deseja excluir?</h5>
                        <p>O serviço <strong id="delete_nome_servico"></strong> será removido.</p>
                        <p class="text-muted">Se este serviço estiver vinculado a agendamentos, ele será apenas desativado, não removido.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger" name="excluir_servico">
                        <i class="bi bi-trash me-1"></i>Excluir Serviço
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para adicionar categoria -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="addCategoryModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Adicionar Nova Categoria
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCategoryForm" method="post" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nome_categoria" class="form-label">Nome da Categoria</label>
                        <input type="text" class="form-control" id="nome_categoria" name="nome_categoria" required>
                    </div>
                    <div class="mb-3">
                        <label for="descricao_categoria" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao_categoria" name="descricao_categoria" rows="3" placeholder="Descreva esta categoria de serviços..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" name="adicionar_categoria">
                        <i class="bi bi-check-circle me-1"></i>Salvar Categoria
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para editar categoria -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-warning text-dark">
                <h5 class="modal-title" id="editCategoryModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Editar Categoria
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCategoryForm" method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_id_categoria" name="id_categoria">
                    <div class="mb-3">
                        <label for="edit_nome_categoria" class="form-label">Nome da Categoria</label>
                        <input type="text" class="form-control" id="edit_nome_categoria" name="nome_categoria" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_descricao_categoria" class="form-label">Descrição</label>
                        <textarea class="form-control" id="edit_descricao_categoria" name="descricao_categoria" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning" name="editar_categoria">
                        <i class="bi bi-check-circle me-1"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para excluir categoria -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-danger text-white">
                <h5 class="modal-title" id="deleteCategoryModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteCategoryForm" method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="delete_id_categoria" name="id_categoria">
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle display-1 text-warning"></i>
                        <h5 class="mt-3">Tem certeza que deseja excluir?</h5>
                        <p>A categoria <strong id="delete_nome_categoria"></strong> será removida.</p>
                        <p class="text-muted">Se esta categoria estiver vinculada a serviços, ela será apenas desativada, não removida.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger" name="excluir_categoria">
                        <i class="bi bi-trash me-1"></i>Excluir Categoria
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para filtrar serviços -->
<div class="modal fade" id="filterServiceModal" tabindex="-1" aria-labelledby="filterServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title" id="filterServiceModalLabel">
                    <i class="bi bi-funnel me-2"></i>Filtrar Serviços
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="filterForm" method="get" action="admin_servicos.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="filter_categoria" class="form-label">Categoria</label>
                        <select class="form-select" id="filter_categoria" name="categoria">
                            <option value="">Todas as categorias</option>
                            <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo htmlspecialchars($categoria['nome']); ?>" <?php echo $filtro_categoria == $categoria['nome'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="filter_status" class="form-label">Status</label>
                        <select class="form-select" id="filter_status" name="status">
                            <option value="">Todos os status</option>
                            <option value="ativo" <?php echo $filtro_status == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                            <option value="inativo" <?php echo $filtro_status == 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="admin_servicos.php" class="btn btn-secondary">
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

mark {
    padding: 0.1em 0.2em;
    border-radius: 0.2em;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
</style>

<script>
// Função para carregar dados no modal de edição de categoria
document.addEventListener('click', function(e) {
    if (e.target.closest('[data-bs-target="#editServiceModal"]')) {
        const btn = e.target.closest('[data-bs-target="#editServiceModal"]');
        document.getElementById('edit_id_servico').value = btn.dataset.id;
        document.getElementById('edit_nome').value = btn.dataset.nome;
        document.getElementById('edit_descricao').value = btn.dataset.descricao;
        document.getElementById('edit_valor').value = btn.dataset.valor;
        document.getElementById('edit_duracao').value = btn.dataset.duracao;
        document.getElementById('edit_categoria').value = btn.dataset.categoria;
    }
    
    if (e.target.closest('[data-bs-target="#deleteServiceModal"]')) {
        const btn = e.target.closest('[data-bs-target="#deleteServiceModal"]');
        document.getElementById('delete_id_servico').value = btn.dataset.id;
        document.getElementById('delete_nome_servico').textContent = btn.dataset.nome;
    }
    
    if (e.target.closest('[data-bs-target="#editCategoryModal"]')) {
        const btn = e.target.closest('[data-bs-target="#editCategoryModal"]');
        document.getElementById('edit_id_categoria').value = btn.dataset.id;
        document.getElementById('edit_nome_categoria').value = btn.dataset.nome;
        document.getElementById('edit_descricao_categoria').value = btn.dataset.descricao;
    }
    
    if (e.target.closest('[data-bs-target="#deleteCategoryModal"]')) {
        const btn = e.target.closest('[data-bs-target="#deleteCategoryModal"]');
        document.getElementById('delete_id_categoria').value = btn.dataset.id;
        document.getElementById('delete_nome_categoria').textContent = btn.dataset.nome;
    }
});

// Pesquisa em tempo real (opcional)
document.getElementById('pesquisa').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    // Debounce para melhor performance
    clearTimeout(this.searchTimeout);
    this.searchTimeout = setTimeout(() => {
        if (searchTerm.length > 0 && searchTerm.length < 3) return; // Só pesquisa com 3+ caracteres
        
        // Se a pesquisa estiver vazia, mostra todas as linhas
        if (searchTerm === '') {
            rows.forEach(row => row.style.display = '');
            return;
        }
        
        // Pesquisa nas linhas da tabela ativa
        const activeTab = document.querySelector('.nav-link.active').getAttribute('aria-controls');
        const tableRows = document.querySelectorAll(`#${activeTab} tbody tr`);
        
        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    }, 300);
});

// Highlight dos termos de pesquisa
function highlightSearchTerms() {
    const searchTerm = new URLSearchParams(window.location.search).get('pesquisa');
    if (!searchTerm) return;
    
    const regex = new RegExp(`(${searchTerm})`, 'gi');
    const elements = document.querySelectorAll('td, .badge');
    
    elements.forEach(element => {
        if (element.children.length === 0) { // Só texto, sem elementos filhos
            element.innerHTML = element.innerHTML.replace(regex, '<mark class="bg-warning text-dark">$1</mark>');
        }
    });
}

// Executar highlight quando a página carregar
document.addEventListener('DOMContentLoaded', highlightSearchTerms);
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Adiciona espaço no final da página -->
<div class="mb-5"></div>