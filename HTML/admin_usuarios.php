<?php
session_start();

// Verifica se o usuário está logado e é admin
if(!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'Administrador'){
    header('Location: login.php');
    exit;
}

include_once('topo_sistema_adm.php');

// Inicializar variáveis de filtro
$filtro_nome = isset($_GET['nome']) ? $_GET['nome'] : '';
$filtro_email = isset($_GET['email']) ? $_GET['email'] : '';
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// Construir a consulta SQL com filtros
$sql = "SELECT * FROM usuario WHERE 1=1";
$params = [];

if (!empty($filtro_nome)) {
    $sql .= " AND nome LIKE ?";
    $params[] = "%$filtro_nome%";
}

if (!empty($filtro_email)) {
    $sql .= " AND email LIKE ?";
    $params[] = "%$filtro_email%";
}

if (!empty($filtro_tipo)) {
    $sql .= " AND tipo_usuario = ?";
    $params[] = $filtro_tipo;
}

$sql .= " ORDER BY id_usuario DESC";

// Processar o formulário de cadastro de usuário
if (isset($_POST['cadastrar_usuario'])) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $tipo_usuario = $_POST['tipo_usuario'];
    $cpf = !empty($_POST['cpf']) ? preg_replace("/[^0-9]/", "", $_POST['cpf']) : '';
    $telefone = $_POST['telefone'];
    $data_nascimento = $_POST['data_nascimento'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    
    // Definir categoria baseada no tipo de usuário
    $categorias_id_categoria = 1; // Categoria padrão
    
    // Campos específicos para profissionais
    $especialidade = ($tipo_usuario === 'Profissional' && !empty($_POST['especialidade'])) ? $_POST['especialidade'] : null;
    $biografia = ($tipo_usuario === 'Profissional' && !empty($_POST['biografia'])) ? $_POST['biografia'] : null;
    $anos_experiencia = ($tipo_usuario === 'Profissional' && !empty($_POST['anos_experiencia'])) ? $_POST['anos_experiencia'] : null;
    $disponivel = ($tipo_usuario === 'Profissional') ? 1 : null;
    
    // Verificar se a categoria existe
    $stmt_categoria = $pdo->prepare("SELECT id_categoria FROM categorias WHERE id_categoria = ?");
    $stmt_categoria->execute([$categorias_id_categoria]);
    
    if (!$stmt_categoria->fetch()) {
        // Se categoria não existir, usar categoria 1 como padrão
        $categorias_id_categoria = 1;
    }
    
    $query = "INSERT INTO usuario (tipo_usuario, nome, email, cpf, telefone, data_nascimento, senha, especialidade, biografia, anos_experiencia, disponivel, categorias_id_categoria) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($query);
    
    if ($stmt->execute([$tipo_usuario, $nome, $email, $cpf, $telefone, $data_nascimento, $senha, $especialidade, $biografia, $anos_experiencia, $disponivel, $categorias_id_categoria])) {
        echo "<script>alert('Usuário cadastrado com sucesso!');</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar usuário: " . implode(" ", $stmt->errorInfo()) . "');</script>";
    }
}

// Processar o formulário de edição de usuário
if (isset($_POST['editar_usuario'])) {
    $id_usuario = $_POST['id_usuario'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $tipo_usuario = $_POST['tipo_usuario'];
    $telefone = $_POST['telefone'];
    
    // Verifica se uma nova senha foi fornecida
    if (!empty($_POST['senha'])) {
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $query = "UPDATE usuario SET tipo_usuario = ?, nome = ?, email = ?, telefone = ?, senha = ? WHERE id_usuario = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$tipo_usuario, $nome, $email, $telefone, $senha, $id_usuario]);
    } else {
        // Mantém a senha atual
        $query = "UPDATE usuario SET tipo_usuario = ?, nome = ?, email = ?, telefone = ? WHERE id_usuario = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$tipo_usuario, $nome, $email, $telefone, $id_usuario]);
    }
    
    if ($stmt->rowCount() > 0) {
        echo "<script>alert('Usuário atualizado com sucesso!');</script>";
    } else {
        echo "<script>alert('Erro ao atualizar usuário ou nenhum dado foi alterado.');</script>";
    }
}

// Processar a exclusão de usuário
if (isset($_POST['excluir_usuario'])) {
    $id_usuario = $_POST['id_usuario'];
    
    $query = "DELETE FROM usuario WHERE id_usuario = ?";
    $stmt = $pdo->prepare($query);
    
    if ($stmt->execute([$id_usuario])) {
        echo "<script>alert('Usuário excluído com sucesso!');</script>";
    } else {
        echo "<script>alert('Erro ao excluir usuário: " . implode(" ", $stmt->errorInfo()) . "');</script>";
    }
}

// Executar a consulta com os filtros
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$result = $stmt;
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
$num_resultados = count($usuarios);

?>

<!-- Incluir CSS personalizado -->
<link rel="stylesheet" href="../CSS/admin_usuarios.css">

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Header com estatísticas -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0"><i class="bi bi-people-fill text-primary me-2"></i>Gerenciamento de Usuários</h2>
                    <p class="text-muted mb-0">Gerencie todos os usuários do sistema</p>
                </div>
                <div class="d-flex gap-3">
                    <div class="text-center">
                        <div class="badge bg-primary fs-5 px-3 py-2"><?php echo $num_resultados; ?></div>
                        <small class="text-muted d-block">Total</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-success fs-5 px-3 py-2">
                            <?php 
                            $clientes = array_filter($usuarios, fn($u) => $u['tipo_usuario'] == 'Cliente');
                            echo count($clientes); 
                            ?>
                        </div>
                        <small class="text-muted d-block">Clientes</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-info fs-5 px-3 py-2">
                            <?php 
                            $profissionais = array_filter($usuarios, fn($u) => $u['tipo_usuario'] == 'Profissional');
                            echo count($profissionais); 
                            ?>
                        </div>
                        <small class="text-muted d-block">Profissionais</small>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-danger fs-5 px-3 py-2">
                            <?php 
                            $admins = array_filter($usuarios, fn($u) => $u['tipo_usuario'] == 'Administrador');
                            echo count($admins); 
                            ?>
                        </div>
                        <small class="text-muted d-block">Admins</small>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-gradient-dark text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Lista de Usuários</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#filterUserModal">
                                <i class="bi bi-funnel"></i> Filtrar
                            </button>
                            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="bi bi-plus-circle"></i> Novo Usuário
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($filtro_nome) || !empty($filtro_email) || !empty($filtro_tipo)): ?>
                    <div class="p-3 bg-light border-bottom">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="fw-bold text-muted">Filtros aplicados:</span>
                            <?php if (!empty($filtro_nome)): ?>
                            <span class="badge bg-info">
                                <i class="bi bi-search me-1"></i>Nome: <?php echo htmlspecialchars($filtro_nome); ?>
                                <a href="?<?php echo (!empty($filtro_email) ? 'email='.$filtro_email.'&' : '') . (!empty($filtro_tipo) ? 'tipo='.$filtro_tipo : ''); ?>" class="text-white text-decoration-none ms-1">×</a>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($filtro_email)): ?>
                            <span class="badge bg-warning">
                                <i class="bi bi-envelope me-1"></i>Email: <?php echo htmlspecialchars($filtro_email); ?>
                                <a href="?<?php echo (!empty($filtro_nome) ? 'nome='.$filtro_nome.'&' : '') . (!empty($filtro_tipo) ? 'tipo='.$filtro_tipo : ''); ?>" class="text-white text-decoration-none ms-1">×</a>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($filtro_tipo)): ?>
                            <span class="badge bg-secondary">
                                <i class="bi bi-person-badge me-1"></i>Tipo: <?php 
                                    switch($filtro_tipo) {
                                        case 'Cliente': echo 'Cliente'; break;
                                        case 'Profissional': echo 'Profissional'; break;
                                        case 'Administrador': echo 'Administrador'; break;
                                    } 
                                ?>
                                <a href="?<?php echo (!empty($filtro_nome) ? 'nome='.$filtro_nome.'&' : '') . (!empty($filtro_email) ? 'email='.$filtro_email : ''); ?>" class="text-white text-decoration-none ms-1">×</a>
                            </span>
                            <?php endif; ?>
                            
                            <a href="admin_usuarios.php" class="btn btn-sm btn-outline-secondary ms-2">
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
                                    <th><i class="bi bi-person me-1"></i>Usuário</th>
                                    <th><i class="bi bi-envelope me-1"></i>Contato</th>
                                    <th><i class="bi bi-card-text me-1"></i>Documento</th>
                                    <th><i class="bi bi-person-badge me-1"></i>Tipo</th>
                                    <th><i class="bi bi-calendar me-1"></i>Cadastro</th>
                                    <th class="text-center"><i class="bi bi-gear me-1"></i>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($num_resultados > 0): ?>
                                    <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge bg-light text-dark">#<?php echo $usuario['id_usuario']; ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-3">
                                                    <?php echo strtoupper(substr($usuario['nome'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($usuario['nome']); ?></div>
                                                    <?php if ($usuario['tipo_usuario'] === 'Profissional' && !empty($usuario['especialidade'])): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($usuario['especialidade']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <small class="text-muted d-block">
                                                    <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($usuario['email']); ?>
                                                </small>
                                                <small class="text-muted">
                                                    <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($usuario['telefone']); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($usuario['cpf'])): ?>
                                                <small class="text-muted">
                                                    <i class="bi bi-card-text me-1"></i><?php echo htmlspecialchars($usuario['cpf']); ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            switch($usuario['tipo_usuario']) {
                                                case 'Administrador':
                                                    echo '<span class="badge bg-danger"><i class="bi bi-shield-check me-1"></i>Administrador</span>';
                                                    break;
                                                case 'Profissional':
                                                    echo '<span class="badge bg-info"><i class="bi bi-scissors me-1"></i>Profissional</span>';
                                                    break;
                                                case 'Cliente':
                                                    echo '<span class="badge bg-success"><i class="bi bi-person-check me-1"></i>Cliente</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge bg-secondary">Indefinido</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($usuario['data_cadastro'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <button class="btn btn-sm btn-outline-primary" title="Editar" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editUserModal"
                                                        data-id="<?php echo $usuario['id_usuario']; ?>"
                                                        data-nome="<?php echo htmlspecialchars($usuario['nome']); ?>"
                                                        data-email="<?php echo htmlspecialchars($usuario['email']); ?>"
                                                        data-telefone="<?php echo htmlspecialchars($usuario['telefone']); ?>"
                                                        data-tipo="<?php echo htmlspecialchars($usuario['tipo_usuario']); ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary" title="Excluir" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteUserModal"
                                                        data-id="<?php echo $usuario['id_usuario']; ?>"
                                                        data-nome="<?php echo htmlspecialchars($usuario['nome']); ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="bi bi-people display-1 text-muted"></i>
                                            <h5 class="text-muted mt-3">Nenhum usuário encontrado</h5>
                                            <p class="text-muted">Adicione o primeiro usuário ou ajuste os filtros</p>
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

<!-- Modal Filtro de Usuários -->
<div class="modal fade" id="filterUserModal" tabindex="-1" aria-labelledby="filterUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title" id="filterUserModalLabel">
                    <i class="bi bi-funnel me-2"></i>Filtrar Usuários
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="filterUserForm" method="get" action="">
                    <div class="mb-3">
                        <label for="filterName" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="filterName" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>" placeholder="Digite o nome do usuário">
                    </div>
                    <div class="mb-3">
                        <label for="filterEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="filterEmail" name="email" value="<?php echo htmlspecialchars($filtro_email); ?>" placeholder="Digite o email do usuário">
                    </div>
                    <div class="mb-3">
                        <label for="filterType" class="form-label">Tipo de Usuário</label>
                        <select class="form-select" id="filterType" name="tipo">
                            <option value="">Todos os tipos</option>
                            <option value="Cliente" <?php echo $filtro_tipo == 'Cliente' ? 'selected' : ''; ?>>Cliente</option>
                            <option value="Profissional" <?php echo $filtro_tipo == 'Profissional' ? 'selected' : ''; ?>>Profissional</option>
                            <option value="Administrador" <?php echo $filtro_tipo == 'Administrador' ? 'selected' : ''; ?>>Administrador</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <a href="admin_usuarios.php" class="btn btn-secondary">
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
</div>

<!-- Modal para Adicionar Novo Usuário -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="addUserModalLabel">
                    <i class="bi bi-person-plus me-2"></i>Cadastrar Novo Usuário
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
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
                            <label for="cpf" class="form-label">CPF</label>
                            <input type="text" class="form-control" id="cpf" name="cpf">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                            <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tipo_usuario" class="form-label">Tipo de Usuário</label>
                            <select class="form-select" id="tipo_usuario" name="tipo_usuario" required onchange="toggleProfessionalFields()">
                                <option value="">Selecione...</option>
                                <option value="Cliente">Cliente</option>
                                <option value="Profissional">Profissional</option>
                                <option value="Administrador">Administrador</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="senha" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="senha" name="senha" required>
                            <div class="form-text">A senha deve ter pelo menos 6 caracteres.</div>
                        </div>
                    </div>
                    
                    <!-- Campos específicos para profissionais -->
                    <div id="professional-fields" style="display: none;">
                        <hr>
                        <h6 class="mb-3 text-primary"><i class="bi bi-scissors me-2"></i>Informações Profissionais</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="especialidade" class="form-label">Especialidade</label>
                                <input type="text" class="form-control" id="especialidade" name="especialidade" placeholder="Ex: Corte e Barba">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="anos_experiencia" class="form-label">Anos de Experiência</label>
                                <input type="number" class="form-control" id="anos_experiencia" name="anos_experiencia" min="0" max="50">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="biografia" class="form-label">Biografia</label>
                            <textarea class="form-control" id="biografia" name="biografia" rows="3" placeholder="Conte um pouco sobre sua experiência e habilidades..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" name="cadastrar_usuario">
                        <i class="bi bi-check-circle me-1"></i>Salvar Usuário
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Usuário -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-warning text-dark">
                <h5 class="modal-title" id="editUserModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Editar Usuário
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
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
                            <label for="edit_tipo_usuario" class="form-label">Tipo de Usuário</label>
                            <select class="form-select" id="edit_tipo_usuario" name="tipo_usuario" required>
                                <option value="">Selecione...</option>
                                <option value="Cliente">Cliente</option>
                                <option value="Profissional">Profissional</option>
                                <option value="Administrador">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_senha" class="form-label">Nova Senha (opcional)</label>
                            <input type="password" class="form-control" id="edit_senha" name="senha">
                            <div class="form-text">Deixe em branco para manter a senha atual.</div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i> CPF e data de nascimento não podem ser alterados após o cadastro.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning" name="editar_usuario">
                        <i class="bi bi-check-circle me-1"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Confirmar Exclusão de Usuário -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-danger text-white">
                <h5 class="modal-title" id="deleteUserModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="delete_id_usuario" name="id_usuario">
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle display-1 text-warning"></i>
                        <h5 class="mt-3">Tem certeza que deseja excluir?</h5>
                        <p>O usuário <strong id="delete_nome_usuario"></strong> será removido permanentemente.</p>
                        <p class="text-muted">Esta ação não pode ser desfeita.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger" name="excluir_usuario">
                        <i class="bi bi-trash me-1"></i>Excluir Usuário
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
</style>

<script>
// Função para mostrar/ocultar campos específicos de profissionais
function toggleProfessionalFields() {
    const tipoUsuario = document.getElementById('tipo_usuario').value;
    const professionalFields = document.getElementById('professional-fields');
    
    if (tipoUsuario === 'Profissional') {
        professionalFields.style.display = 'block';
        // Tornar campos obrigatórios
        document.getElementById('especialidade').required = true;
        document.getElementById('anos_experiencia').required = true;
    } else {
        professionalFields.style.display = 'none';
        // Remover obrigatoriedade
        document.getElementById('especialidade').required = false;
        document.getElementById('anos_experiencia').required = false;
        // Limpar campos
        document.getElementById('especialidade').value = '';
        document.getElementById('anos_experiencia').value = '';
        document.getElementById('biografia').value = '';
    }
}

// Resetar formulário quando modal for fechado
document.getElementById('addUserModal').addEventListener('hidden.bs.modal', function () {
    document.querySelector('#addUserModal form').reset();
    document.getElementById('professional-fields').style.display = 'none';
    document.getElementById('especialidade').required = false;
    document.getElementById('anos_experiencia').required = false;
});

// Script para carregar dados no modal de edição
document.addEventListener('click', function(e) {
    if (e.target.closest('[data-bs-target="#editUserModal"]')) {
        const btn = e.target.closest('[data-bs-target="#editUserModal"]');
        document.getElementById('edit_id_usuario').value = btn.dataset.id;
        document.getElementById('edit_nome').value = btn.dataset.nome;
        document.getElementById('edit_email').value = btn.dataset.email;
        document.getElementById('edit_telefone').value = btn.dataset.telefone;
        document.getElementById('edit_tipo_usuario').value = btn.dataset.tipo;
    }
    
    if (e.target.closest('[data-bs-target="#deleteUserModal"]')) {
        const btn = e.target.closest('[data-bs-target="#deleteUserModal"]');
        document.getElementById('delete_id_usuario').value = btn.dataset.id;
        document.getElementById('delete_nome_usuario').textContent = btn.dataset.nome;
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Adiciona espaço no final da página -->
<div class="mb-5"></div>