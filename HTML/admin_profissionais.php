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
    if (isset($_POST['adicionar_profissional'])) {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $telefone = $_POST['telefone'];
        $biografia = $_POST['biografia'];
        $anos_experiencia = $_POST['anos_experiencia'];
        $data_nascimento = $_POST['data_nascimento'];
        $cpf = $_POST['cpf'];
        $categoria_id = $_POST['categoria_id']; // Mudança: agora é ID da categoria
        $senha = password_hash('senha123', PASSWORD_DEFAULT); // Senha padrão
        
        try {
            // Verificar se a categoria existe
            $stmt_verifica_cat = $pdo->prepare("SELECT id_categoria FROM categorias WHERE id_categoria = ?");
            $stmt_verifica_cat->execute([$categoria_id]);
            
            if (!$stmt_verifica_cat->fetch()) {
                throw new Exception("Categoria selecionada não existe.");
            }
            
            // Inserir profissional
            $sql = "INSERT INTO usuario (tipo_usuario, nome, email, telefone, biografia, anos_experiencia, data_nascimento, cpf, senha, categorias_id_categoria, disponivel, ativo) 
                    VALUES ('Profissional', ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $email, $telefone, $biografia, $anos_experiencia, $data_nascimento, $cpf, $senha, $categoria_id]);
            
            $mensagem = "Profissional adicionado com sucesso!";
            $tipo_mensagem = "success";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $mensagem = "Erro: Email ou CPF já cadastrado no sistema.";
            } else {
                $mensagem = "Erro ao adicionar profissional: " . $e->getMessage();
            }
            $tipo_mensagem = "danger";
        } catch (Exception $e) {
            $mensagem = "Erro: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
    
    if (isset($_POST['editar_profissional'])) {
        $id_usuario = $_POST['id_usuario'];
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $telefone = $_POST['telefone'];
        $biografia = $_POST['biografia'];
        $anos_experiencia = $_POST['anos_experiencia'];
        $disponivel = isset($_POST['disponivel']) ? 1 : 0;
        $categoria_id = $_POST['categoria_id']; // Mudança: agora é ID da categoria
        
        try {
            // Atualizar profissional
            $sql = "UPDATE usuario SET nome = ?, email = ?, telefone = ?, biografia = ?, anos_experiencia = ?, disponivel = ?, categorias_id_categoria = ? 
                    WHERE id_usuario = ? AND tipo_usuario = 'Profissional'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $email, $telefone, $biografia, $anos_experiencia, $disponivel, $categoria_id, $id_usuario]);
            
            $mensagem = "Profissional atualizado com sucesso!";
            $tipo_mensagem = "success";
        } catch (PDOException $e) {
            $mensagem = "Erro ao atualizar profissional: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
    
    if (isset($_POST['alternar_status'])) {
        $id_usuario = $_POST['id_usuario'];
        $novo_status = $_POST['novo_status'];
        
        try {
            $sql = "UPDATE usuario SET ativo = ? WHERE id_usuario = ? AND tipo_usuario = 'Profissional'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$novo_status, $id_usuario]);
            
            $status_texto = $novo_status ? "ativado" : "desativado";
            $mensagem = "Profissional {$status_texto} com sucesso!";
            $tipo_mensagem = "success";
        } catch (PDOException $e) {
            $mensagem = "Erro ao alterar status: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
}

// Filtros
$filtro_status = isset($_GET['filtro_status']) ? $_GET['filtro_status'] : 'todos';
$filtro_busca = isset($_GET['busca']) ? $_GET['busca'] : '';
$filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';

// Buscar todos os profissionais com JOIN para obter o nome da categoria
$sql = "SELECT u.id_usuario, u.nome, u.email, u.telefone, u.biografia, u.anos_experiencia, u.disponivel, u.ativo, u.data_cadastro, u.categorias_id_categoria, c.nome as categoria_nome
        FROM usuario u 
        LEFT JOIN categorias c ON u.categorias_id_categoria = c.id_categoria 
        WHERE u.tipo_usuario = 'Profissional'";

$params = [];

// Aplicar filtros
if ($filtro_status === 'ativo') {
    $sql .= " AND u.ativo = 1";
} elseif ($filtro_status === 'inativo') {
    $sql .= " AND u.ativo = 0";
}

if (!empty($filtro_busca)) {
    $sql .= " AND (u.nome LIKE ? OR u.email LIKE ?)";
    $params[] = "%$filtro_busca%";
    $params[] = "%$filtro_busca%";
}

if (!empty($filtro_categoria)) {
    $sql .= " AND c.id_categoria = ?";
    $params[] = $filtro_categoria;
}

$sql .= " ORDER BY u.nome";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$profissionais = $stmt->fetchAll(PDO::FETCH_ASSOC);
$num_resultados = count($profissionais);

// Obter todas as categorias para os formulários e filtros
$sql_categorias = "SELECT id_categoria, nome FROM categorias WHERE ativo = 1 ORDER BY nome";
$stmt_categorias = $pdo->query($sql_categorias);
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

include_once('topo_sistema_adm.php');
?>

<!-- Incluir CSS personalizado -->
<link rel="stylesheet" href="../../CSS/admin_profissionais.css">

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Header com estatísticas -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0"><i class="bi bi-people-fill text-primary me-2"></i>Gerenciamento de Profissionais</h2>
                    <p class="text-muted mb-0">Gerencie os profissionais da barbearia</p>
                </div>
                <div class="d-flex gap-3">
                    <div class="text-center">
                        <div class="badge bg-primary fs-5 px-3 py-2"><?php echo count($profissionais); ?></div>
                        <small class="text-muted d-block">Total</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-success fs-5 px-3 py-2">
                            <?php echo count(array_filter($profissionais, fn($p) => $p['ativo'] == 1)); ?>
                        </div>
                        <small class="text-muted d-block">Ativos</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-warning fs-5 px-3 py-2">
                            <?php echo count(array_filter($profissionais, fn($p) => $p['disponivel'] == 1 && $p['ativo'] == 1)); ?>
                        </div>
                        <small class="text-muted d-block">Disponíveis</small>
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
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Lista de Profissionais</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#filterProfessionalModal">
                                <i class="bi bi-funnel"></i> Filtrar
                            </button>
                            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addProfessionalModal">
                                <i class="bi bi-plus-circle"></i> Novo Profissional
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if ($filtro_status !== 'todos' || !empty($filtro_busca) || !empty($filtro_categoria)): ?>
                    <div class="p-3 bg-light border-bottom">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="fw-bold text-muted">Filtros aplicados:</span>
                            
                            <?php if (!empty($filtro_busca)): ?>
                            <span class="badge bg-info">
                                <i class="bi bi-search me-1"></i>Busca: <?php echo htmlspecialchars($filtro_busca); ?>
                                <a href="?<?php echo (!empty($filtro_categoria) ? 'categoria='.$filtro_categoria.'&' : '') . ($filtro_status !== 'todos' ? 'filtro_status='.$filtro_status : ''); ?>" 
                                   class="text-white text-decoration-none ms-1">×</a>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($filtro_categoria)): ?>
                            <?php 
                            // Buscar nome da categoria para exibição
                            $stmt_cat_nome = $pdo->prepare("SELECT nome FROM categorias WHERE id_categoria = ?");
                            $stmt_cat_nome->execute([$filtro_categoria]);
                            $categoria_nome = $stmt_cat_nome->fetchColumn();
                            ?>
                            <span class="badge bg-warning">
                                <i class="bi bi-star me-1"></i>Categoria: <?php echo htmlspecialchars($categoria_nome); ?>
                                <a href="?<?php echo (!empty($filtro_busca) ? 'busca='.$filtro_busca.'&' : '') . ($filtro_status !== 'todos' ? 'filtro_status='.$filtro_status : ''); ?>" 
                                   class="text-white text-decoration-none ms-1">×</a>
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($filtro_status !== 'todos'): ?>
                            <span class="badge bg-secondary">
                                <i class="bi bi-toggle-on me-1"></i>Status: <?php echo ucfirst($filtro_status); ?>
                                <a href="?<?php echo (!empty($filtro_busca) ? 'busca='.$filtro_busca.'&' : '') . (!empty($filtro_categoria) ? 'categoria='.$filtro_categoria : ''); ?>" 
                                   class="text-white text-decoration-none ms-1">×</a>
                            </span>
                            <?php endif; ?>
                            
                            <a href="admin_profissionais.php" class="btn btn-sm btn-outline-secondary ms-2">
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
                                    <th><i class="bi bi-person me-1"></i>Profissional</th>
                                    <th><i class="bi bi-envelope me-1"></i>Contato</th>
                                    <th><i class="bi bi-star me-1"></i>Categoria</th>
                                    <th><i class="bi bi-clock-history me-1"></i>Experiência</th>
                                    <th><i class="bi bi-toggle-on me-1"></i>Status</th>
                                    <th><i class="bi bi-calendar me-1"></i>Cadastro</th>
                                    <th class="text-center"><i class="bi bi-gear me-1"></i>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($num_resultados > 0): ?>
                                    <?php foreach ($profissionais as $profissional): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge bg-light text-dark">#<?php echo $profissional['id_usuario']; ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-3">
                                                    <?php echo strtoupper(substr($profissional['nome'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($profissional['nome']); ?></div>
                                                    <?php if (!empty($profissional['biografia'])): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars(substr($profissional['biografia'], 0, 50)) . (strlen($profissional['biografia']) > 50 ? '...' : ''); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <small class="text-muted d-block">
                                                    <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($profissional['email']); ?>
                                                </small>
                                                <small class="text-muted">
                                                    <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($profissional['telefone']); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($profissional['categoria_nome'])): ?>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($profissional['categoria_nome']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Não informado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($profissional['anos_experiencia']): ?>
                                                <span class="badge bg-info"><?php echo $profissional['anos_experiencia']; ?> ano<?php echo $profissional['anos_experiencia'] > 1 ? 's' : ''; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <?php if ($profissional['ativo']): ?>
                                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Ativo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Inativo</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($profissional['ativo']): ?>
                                                    <?php if ($profissional['disponivel']): ?>
                                                        <span class="badge bg-success"><i class="bi bi-person-check me-1"></i>Disponível</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning"><i class="bi bi-person-dash me-1"></i>Indisponível</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($profissional['data_cadastro'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <button class="btn btn-sm btn-outline-primary" title="Editar" 
                                                        data-bs-toggle="modal" data-bs-target="#editProfessionalModal"
                                                        data-id="<?php echo $profissional['id_usuario']; ?>"
                                                        data-nome="<?php echo htmlspecialchars($profissional['nome']); ?>"
                                                        data-email="<?php echo htmlspecialchars($profissional['email']); ?>"
                                                        data-telefone="<?php echo htmlspecialchars($profissional['telefone']); ?>"
                                                        data-categoria="<?php echo $profissional['categorias_id_categoria']; ?>"
                                                        data-biografia="<?php echo htmlspecialchars($profissional['biografia'] ?? ''); ?>"
                                                        data-anos="<?php echo $profissional['anos_experiencia'] ?? ''; ?>"
                                                        data-disponivel="<?php echo $profissional['disponivel']; ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                
                                                <?php if ($profissional['ativo']): ?>
                                                <form action="" method="post" style="display: inline;">
                                                    <input type="hidden" name="id_usuario" value="<?php echo $profissional['id_usuario']; ?>">
                                                    <input type="hidden" name="novo_status" value="0">
                                                    <input type="hidden" name="alternar_status" value="1">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Desativar"
                                                            onclick="return confirm('Tem certeza que deseja desativar este profissional?')">
                                                        <i class="bi bi-person-x"></i>
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <form action="" method="post" style="display: inline;">
                                                    <input type="hidden" name="id_usuario" value="<?php echo $profissional['id_usuario']; ?>">
                                                    <input type="hidden" name="novo_status" value="1">
                                                    <input type="hidden" name="alternar_status" value="1">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Ativar">
                                                        <i class="bi bi-person-check"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <i class="bi bi-people display-1 text-muted"></i>
                                            <h5 class="text-muted mt-3">Nenhum profissional encontrado</h5>
                                            <p class="text-muted">Adicione o primeiro profissional ou ajuste os filtros</p>
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

<!-- Modal para adicionar profissional -->
<div class="modal fade" id="addProfessionalModal" tabindex="-1" aria-labelledby="addProfessionalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="addProfessionalModalLabel">
                    <i class="bi bi-person-plus me-2"></i>Adicionar Novo Profissional
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addProfessionalForm" method="post" action="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="cpf" class="form-label">CPF</label>
                            <input type="text" class="form-control" id="cpf" name="cpf">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                            <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="categoria_id" class="form-label">Categoria</label>
                            <select class="form-select" id="categoria_id" name="categoria_id" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id_categoria']; ?>">
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="anos_experiencia" class="form-label">Anos de Experiência</label>
                            <input type="number" class="form-control" id="anos_experiencia" name="anos_experiencia" min="0" max="50">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="biografia" class="form-label">Biografia</label>
                        <textarea class="form-control" id="biografia" name="biografia" rows="3" 
                                  placeholder="Conte um pouco sobre a experiência e habilidades do profissional..."></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Informação:</strong> A senha inicial será "senha123". O profissional deverá alterá-la no primeiro acesso.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" name="adicionar_profissional">
                        <i class="bi bi-check-circle me-1"></i>Salvar Profissional
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para editar profissional -->
<div class="modal fade" id="editProfessionalModal" tabindex="-1" aria-labelledby="editProfessionalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-warning text-dark">
                <h5 class="modal-title" id="editProfessionalModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Editar Profissional
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editProfessionalForm" method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_id_usuario" name="id_usuario">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_nome" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="edit_nome" name="nome" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_email" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_telefone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="edit_telefone" name="telefone" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_categoria_id" class="form-label">Categoria</label>
                            <select class="form-select" id="edit_categoria_id" name="categoria_id" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id_categoria']; ?>">
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_anos_experiencia" class="form-label">Anos de Experiência</label>
                            <input type="number" class="form-control" id="edit_anos_experiencia" name="anos_experiencia" min="0" max="50">
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="edit_disponivel" name="disponivel" value="1">
                                <label class="form-check-label" for="edit_disponivel">
                                    <i class="bi bi-person-check me-1"></i>Profissional Disponível
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_biografia" class="form-label">Biografia</label>
                        <textarea class="form-control" id="edit_biografia" name="biografia" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning" name="editar_profissional">
                        <i class="bi bi-check-circle me-1"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para filtrar profissionais -->
<div class="modal fade" id="filterProfessionalModal" tabindex="-1" aria-labelledby="filterProfessionalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title" id="filterProfessionalModalLabel">
                    <i class="bi bi-funnel me-2"></i>Filtrar Profissionais
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="filterForm" method="get" action="admin_profissionais.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="busca" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="busca" name="busca" 
                               placeholder="Nome ou email" value="<?php echo htmlspecialchars($filtro_busca); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="filter_categoria" class="form-label">Categoria</label>
                        <select class="form-select" id="filter_categoria" name="categoria">
                            <option value="">Todas as categorias</option>
                            <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id_categoria']; ?>" 
                                    <?php echo $filtro_categoria == $categoria['id_categoria'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="filter_status" class="form-label">Status</label>
                        <select class="form-select" id="filter_status" name="filtro_status">
                            <option value="todos" <?php echo $filtro_status == 'todos' ? 'selected' : ''; ?>>Todos os status</option>
                            <option value="ativo" <?php echo $filtro_status == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                            <option value="inativo" <?php echo $filtro_status == 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="admin_profissionais.php" class="btn btn-secondary">
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
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 16px;
}

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

.especialidades-container {
    background-color: #f8f9fa;
}

.especialidades-container .form-check {
    margin-bottom: 0.5rem;
}

.especialidades-container .form-check:last-child {
    margin-bottom: 0;
}
</style>

<script>
// Script para carregar dados no modal de edição
document.addEventListener('click', function(e) {
    if (e.target.closest('[data-bs-target="#editProfessionalModal"]')) {
        const btn = e.target.closest('[data-bs-target="#editProfessionalModal"]');
        document.getElementById('edit_id_usuario').value = btn.dataset.id;
        document.getElementById('edit_nome').value = btn.dataset.nome;
        document.getElementById('edit_email').value = btn.dataset.email;
        document.getElementById('edit_telefone').value = btn.dataset.telefone;
        document.getElementById('edit_biografia').value = btn.dataset.biografia;
        document.getElementById('edit_anos_experiencia').value = btn.dataset.anos;
        document.getElementById('edit_disponivel').checked = btn.dataset.disponivel == '1';
        document.getElementById('edit_categoria_id').value = btn.dataset.categoria;
    }
});

// Limpar formulários ao fechar modais
document.getElementById('addProfessionalModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('addProfessionalForm').reset();
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Adiciona espaço no final da página -->
<div class="mb-5"></div>