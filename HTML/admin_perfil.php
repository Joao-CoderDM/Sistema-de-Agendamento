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

// Buscar dados do usuário logado
$usuario_id = $_SESSION['id_usuario'];
$sql = "SELECT * FROM usuario WHERE id_usuario = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['atualizar_perfil'])) {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $telefone = $_POST['telefone'];
        $cpf = $_POST['cpf'];
        $data_nascimento = $_POST['data_nascimento'];
        
        try {
            // Verificar se o email já existe para outro usuário
            $sql_check = "SELECT id_usuario FROM usuario WHERE email = ? AND id_usuario != ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$email, $usuario_id]);
            
            if ($stmt_check->rowCount() > 0) {
                $mensagem = "Este e-mail já está sendo usado por outro usuário!";
                $tipo_mensagem = "danger";
            } else {
                $sql = "UPDATE usuario SET nome = ?, email = ?, telefone = ?, cpf = ?, data_nascimento = ? WHERE id_usuario = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nome, $email, $telefone, $cpf, $data_nascimento, $usuario_id]);
                
                // Atualizar dados da sessão
                $_SESSION['nome'] = $nome;
                $_SESSION['email'] = $email;
                
                // Recarregar dados do usuário
                $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
                $stmt->execute([$usuario_id]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $mensagem = "Perfil atualizado com sucesso!";
                $tipo_mensagem = "success";
            }
        } catch (PDOException $e) {
            $mensagem = "Erro ao atualizar perfil: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
    
    if (isset($_POST['alterar_senha'])) {
        $senha_atual = $_POST['senha_atual'];
        $nova_senha = $_POST['nova_senha'];
        $confirmar_senha = $_POST['confirmar_senha'];
        
        try {
            // Verificar senha atual
            if (!password_verify($senha_atual, $usuario['senha'])) {
                $mensagem = "Senha atual incorreta!";
                $tipo_mensagem = "danger";
            } elseif ($nova_senha !== $confirmar_senha) {
                $mensagem = "A confirmação da nova senha não confere!";
                $tipo_mensagem = "danger";
            } elseif (strlen($nova_senha) < 6) {
                $mensagem = "A nova senha deve ter pelo menos 6 caracteres!";
                $tipo_mensagem = "danger";
            } else {
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $sql = "UPDATE usuario SET senha = ? WHERE id_usuario = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$senha_hash, $usuario_id]);
                
                $mensagem = "Senha alterada com sucesso!";
                $tipo_mensagem = "success";
            }
        } catch (PDOException $e) {
            $mensagem = "Erro ao alterar senha: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
    
    if (isset($_POST['upload_foto'])) {
        $target_dir = "../uploads/perfil/";
        
        // Criar diretório se não existir
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION));
        $new_filename = "perfil_" . $usuario_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        $upload_ok = 1;
        
        // Verificar se é uma imagem real
        $check = getimagesize($_FILES["foto"]["tmp_name"]);
        if($check === false) {
            $mensagem = "O arquivo não é uma imagem!";
            $tipo_mensagem = "danger";
            $upload_ok = 0;
        }
        
        // Verificar tamanho do arquivo (5MB)
        if ($_FILES["foto"]["size"] > 5000000) {
            $mensagem = "O arquivo é muito grande! Máximo 5MB.";
            $tipo_mensagem = "danger";
            $upload_ok = 0;
        }
        
        // Permitir apenas alguns formatos
        if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif") {
            $mensagem = "Apenas arquivos JPG, JPEG, PNG e GIF são permitidos!";
            $tipo_mensagem = "danger";
            $upload_ok = 0;
        }
        
        if ($upload_ok == 1) {
            if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
                // Remover foto anterior se existir
                if (!empty($usuario['foto']) && file_exists($usuario['foto'])) {
                    unlink($usuario['foto']);
                }
                
                try {
                    $sql = "UPDATE usuario SET foto = ? WHERE id_usuario = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$target_file, $usuario_id]);
                    
                    // Recarregar dados do usuário
                    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
                    $stmt->execute([$usuario_id]);
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $mensagem = "Foto do perfil atualizada com sucesso!";
                    $tipo_mensagem = "success";
                } catch (PDOException $e) {
                    $mensagem = "Erro ao salvar foto no banco: " . $e->getMessage();
                    $tipo_mensagem = "danger";
                }
            } else {
                $mensagem = "Erro ao fazer upload da foto!";
                $tipo_mensagem = "danger";
            }
        }
    }
    
    if (isset($_POST['remover_foto'])) {
        try {
            // Remover arquivo se existir
            if (!empty($usuario['foto']) && file_exists($usuario['foto'])) {
                unlink($usuario['foto']);
            }
            
            $sql = "UPDATE usuario SET foto = NULL WHERE id_usuario = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$usuario_id]);
            
            // Recarregar dados do usuário
            $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
            $stmt->execute([$usuario_id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $mensagem = "Foto do perfil removida com sucesso!";
            $tipo_mensagem = "success";
        } catch (PDOException $e) {
            $mensagem = "Erro ao remover foto: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
}

// Buscar estatísticas do sistema para o dashboard
$stats = [];

try {
    // Total de usuários
    $sql = "SELECT COUNT(*) as total FROM usuario WHERE ativo = 1";
    $stmt = $pdo->query($sql);
    $stats['total_usuarios'] = $stmt->fetch()['total'] ?? 0;

    // Total de agendamentos este mês
    $sql = "SELECT COUNT(*) as total FROM agendamentos WHERE MONTH(data_agendamento) = MONTH(CURRENT_DATE()) AND YEAR(data_agendamento) = YEAR(CURRENT_DATE())";
    $stmt = $pdo->query($sql);
    $stats['agendamentos_mes'] = $stmt->fetch()['total'] ?? 0;

    // Total de serviços ativos
    $sql = "SELECT COUNT(*) as total FROM servicos WHERE ativo = 1";
    $stmt = $pdo->query($sql);
    $stats['servicos_ativos'] = $stmt->fetch()['total'] ?? 0;

    // Faturamento este mês
    $sql = "SELECT SUM(valor) as total FROM agendamentos WHERE status = 'concluido' AND MONTH(data_agendamento) = MONTH(CURRENT_DATE()) AND YEAR(data_agendamento) = YEAR(CURRENT_DATE())";
    $stmt = $pdo->query($sql);
    $stats['faturamento_mes'] = $stmt->fetch()['total'] ?? 0;
} catch (PDOException $e) {
    $stats['total_usuarios'] = 0;
    $stats['agendamentos_mes'] = 0;
    $stats['servicos_ativos'] = 0;
    $stats['faturamento_mes'] = 0;
}

include_once('topo_sistema_adm.php');
?>

<!-- Incluir CSS personalizado -->
<link rel="stylesheet" href="../../CSS/admin_perfil.css">

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0"><i class="bi bi-person-circle text-primary me-2"></i>Meu Perfil</h2>
                    <p class="text-muted mb-0">Gerencie suas informações pessoais e configurações</p>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-success fs-6 px-3 py-2">
                        <i class="bi bi-shield-check me-1"></i>Administrador
                    </span>
                </div>
            </div>
            
            <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Perfil -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-gradient-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-person me-2"></i>Foto do Perfil</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="profile-photo-container mb-3">
                                <?php if (!empty($usuario['foto']) && file_exists($usuario['foto'])): ?>
                                    <img src="<?php echo htmlspecialchars($usuario['foto']); ?>" alt="Foto do Perfil" class="profile-photo">
                                <?php else: ?>
                                    <div class="profile-photo-placeholder">
                                        <i class="bi bi-person-fill display-1 text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <h5><?php echo htmlspecialchars($usuario['nome']); ?></h5>
                            <p class="text-muted"><?php echo htmlspecialchars($usuario['email']); ?></p>
                            <p class="badge bg-primary"><?php echo $usuario['tipo_usuario']; ?></p>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#photoModal">
                                    <i class="bi bi-camera me-1"></i> Alterar Foto
                                </button>
                                <?php if (!empty($usuario['foto'])): ?>
                                <form method="post" style="display: inline;">
                                    <button type="submit" name="remover_foto" class="btn btn-outline-danger btn-sm w-100 rounded-pill shadow-sm" 
                                            onclick="return confirm('Tem certeza que deseja remover a foto?')">
                                        <i class="bi bi-trash me-1"></i> Remover Foto
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informações Pessoais -->
                <div class="col-lg-8 mb-4">
                    <!-- Navegação por abas -->
                    <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                                <i class="bi bi-person me-1"></i> Informações Pessoais
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                                <i class="bi bi-shield-lock me-1"></i> Segurança
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab">
                                <i class="bi bi-graph-up me-1"></i> Estatísticas
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Aba Informações Pessoais -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel">
                            <div class="card shadow-sm">
                                <div class="card-header bg-gradient-dark text-white">
                                    <h5 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Dados Pessoais</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="nome" class="form-label">Nome Completo</label>
                                                <input type="text" class="form-control" id="nome" name="nome" 
                                                       value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="email" class="form-label">E-mail</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="telefone" class="form-label">Telefone</label>
                                                <input type="text" class="form-control" id="telefone" name="telefone" 
                                                       value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="cpf" class="form-label">CPF</label>
                                                <input type="text" class="form-control" id="cpf" name="cpf" 
                                                       value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                                                <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" 
                                                       value="<?php echo $usuario['data_nascimento'] ?? ''; ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="data_cadastro" class="form-label">Data de Cadastro</label>
                                                <input type="text" class="form-control" id="data_cadastro" 
                                                       value="<?php echo date('d/m/Y H:i', strtotime($usuario['data_cadastro'])); ?>" readonly>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary rounded-pill shadow-sm" name="atualizar_perfil">
                                            <i class="bi bi-check-circle me-1"></i>Salvar Alterações
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aba Segurança -->
                        <div class="tab-pane fade" id="security" role="tabpanel">
                            <div class="card shadow-sm">
                                <div class="card-header bg-gradient-dark text-white">
                                    <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Alterar Senha</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="">
                                        <div class="mb-3">
                                            <label for="senha_atual" class="form-label">Senha Atual</label>
                                            <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="nova_senha" class="form-label">Nova Senha</label>
                                                <input type="password" class="form-control" id="nova_senha" name="nova_senha" 
                                                       minlength="6" required>
                                                <div class="form-text">Mínimo de 6 caracteres</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                                                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" 
                                                       minlength="6" required>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-warning rounded-pill shadow-sm" name="alterar_senha">
                                            <i class="bi bi-key me-1"></i>Alterar Senha
                                        </button>
                                    </form>
                                    
                                    <hr class="my-4">
                                    
                                    <div class="alert alert-info">
                                        <h6><i class="bi bi-info-circle me-2"></i>Dicas de Segurança</h6>
                                        <ul class="mb-0">
                                            <li>Use uma senha forte com pelo menos 8 caracteres</li>
                                            <li>Combine letras maiúsculas, minúsculas, números e símbolos</li>
                                            <li>Não compartilhe sua senha com ninguém</li>
                                            <li>Altere sua senha regularmente</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aba Estatísticas -->
                        <div class="tab-pane fade" id="stats" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body text-center">
                                            <div class="text-primary mb-2">
                                                <i class="bi bi-people display-4"></i>
                                            </div>
                                            <h3 class="mb-1"><?php echo $stats['total_usuarios']; ?></h3>
                                            <p class="text-muted mb-0">Usuários Ativos</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body text-center">
                                            <div class="text-success mb-2">
                                                <i class="bi bi-calendar-check display-4"></i>
                                            </div>
                                            <h3 class="mb-1"><?php echo $stats['agendamentos_mes']; ?></h3>
                                            <p class="text-muted mb-0">Agendamentos Este Mês</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body text-center">
                                            <div class="text-info mb-2">
                                                <i class="bi bi-scissors display-4"></i>
                                            </div>
                                            <h3 class="mb-1"><?php echo $stats['servicos_ativos']; ?></h3>
                                            <p class="text-muted mb-0">Serviços Disponíveis</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body text-center">
                                            <div class="text-warning mb-2">
                                                <i class="bi bi-currency-dollar display-4"></i>
                                            </div>
                                            <h3 class="mb-1">R$ <?php echo number_format($stats['faturamento_mes'], 2, ',', '.'); ?></h3>
                                            <p class="text-muted mb-0">Faturamento Este Mês</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para alterar foto -->
<div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="photoModalLabel">
                    <i class="bi bi-camera me-2"></i>Alterar Foto do Perfil
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="foto" class="form-label">Selecionar Nova Foto</label>
                        <input type="file" class="form-control" id="foto" name="foto" accept="image/*" required>
                        <div class="form-text">
                            Formatos aceitos: JPG, JPEG, PNG, GIF. Tamanho máximo: 5MB.
                        </div>
                    </div>
                    <div id="preview-container" style="display: none;">
                        <img id="preview-image" src="" alt="Preview" style="max-width: 100%; height: auto; border-radius: 8px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary rounded-pill" name="upload_foto">
                        <i class="bi bi-upload me-1"></i>Fazer Upload
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

.card {
    border: none;
    border-radius: 10px;
}

.profile-photo-container {
    position: relative;
    display: inline-block;
}

.profile-photo {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #f8f9fa;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.profile-photo-placeholder {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background-color: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid #e9ecef;
    margin: 0 auto;
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

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
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

.btn-primary.rounded-pill {
background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border: none;
}

.btn-primary.rounded-pill:hover {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border: none;
}

.btn-warning.rounded-pill {
   background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border: none;
    color: white;
}

.btn-warning.rounded-pill:hover {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border: none;
    color: white;
}

.btn-secondary.rounded-pill {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border: none;
}

.btn-secondary.rounded-pill:hover {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border: none;
}

.btn.rounded-pill:focus {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
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
// Preview da imagem antes do upload
document.getElementById('foto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-image').src = e.target.result;
            document.getElementById('preview-container').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

// Validação de confirmação de senha
document.getElementById('confirmar_senha').addEventListener('input', function() {
    const novaSenha = document.getElementById('nova_senha').value;
    const confirmarSenha = this.value;
    
    if (novaSenha !== confirmarSenha) {
        this.setCustomValidity('As senhas não conferem');
    } else {
        this.setCustomValidity('');
    }
});

// Máscara para CPF
document.getElementById('cpf').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    e.target.value = value;
});

// Máscara para telefone
document.getElementById('telefone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 10) {
        value = value.replace(/(\d{2})(\d)/, '($1) $2');
        value = value.replace(/(\d{4})(\d)/, '$1-$2');
    } else {
        value = value.replace(/(\d{2})(\d)/, '($1) $2');
        value = value.replace(/(\d{5})(\d)/, '$1-$2');
    }
    e.target.value = value;
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Adiciona espaço no final da página -->
<div class="mb-5"></div>

