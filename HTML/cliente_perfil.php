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

// Processar formulário de atualização do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['atualizar_perfil'])) {
            $nome = trim($_POST['nome']);
            $email = trim($_POST['email']);
            $telefone = trim($_POST['telefone']);
            $data_nascimento = $_POST['data_nascimento'];
            
            // Validações básicas
            if (empty($nome) || empty($email) || empty($data_nascimento)) {
                $mensagem = "Nome, email e data de nascimento são obrigatórios.";
                $tipo_mensagem = "danger";
            } else {
                // Verificar se email já existe para outro usuário
                $sql_check = "SELECT id_usuario FROM usuario WHERE email = ? AND id_usuario != ?";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->execute([$email, $cliente_id]);
                
                if ($stmt_check->rowCount() > 0) {
                    $mensagem = "Este email já está sendo usado por outro usuário.";
                    $tipo_mensagem = "danger";
                } else {
                    // Atualizar dados do perfil
                    $sql_update = "UPDATE usuario SET nome = ?, email = ?, telefone = ?, data_nascimento = ? WHERE id_usuario = ?";
                    $stmt_update = $pdo->prepare($sql_update);
                    
                    if ($stmt_update->execute([$nome, $email, $telefone, $data_nascimento, $cliente_id])) {
                        // Atualizar sessão
                        $_SESSION['nome'] = $nome;
                        $_SESSION['email'] = $email;
                        
                        $mensagem = "Perfil atualizado com sucesso!";
                        $tipo_mensagem = "success";
                    } else {
                        $mensagem = "Erro ao atualizar perfil. Tente novamente.";
                        $tipo_mensagem = "danger";
                    }
                }
            }
        }
        
        if (isset($_POST['alterar_senha'])) {
            $senha_atual = $_POST['senha_atual'];
            $nova_senha = $_POST['nova_senha'];
            $confirmar_senha = $_POST['confirmar_senha'];
            
            if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
                $mensagem = "Todos os campos de senha são obrigatórios.";
                $tipo_mensagem = "danger";
            } elseif ($nova_senha !== $confirmar_senha) {
                $mensagem = "A nova senha e a confirmação não coincidem.";
                $tipo_mensagem = "danger";
            } elseif (strlen($nova_senha) < 6) {
                $mensagem = "A nova senha deve ter pelo menos 6 caracteres.";
                $tipo_mensagem = "danger";
            } else {
                // Verificar senha atual
                $sql_senha = "SELECT senha FROM usuario WHERE id_usuario = ?";
                $stmt_senha = $pdo->prepare($sql_senha);
                $stmt_senha->execute([$cliente_id]);
                $senha_bd = $stmt_senha->fetchColumn();
                
                if (password_verify($senha_atual, $senha_bd)) {
                    // Atualizar senha
                    $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $sql_update_senha = "UPDATE usuario SET senha = ? WHERE id_usuario = ?";
                    $stmt_update_senha = $pdo->prepare($sql_update_senha);
                    
                    if ($stmt_update_senha->execute([$nova_senha_hash, $cliente_id])) {
                        $mensagem = "Senha alterada com sucesso!";
                        $tipo_mensagem = "success";
                    } else {
                        $mensagem = "Erro ao alterar senha. Tente novamente.";
                        $tipo_mensagem = "danger";
                    }
                } else {
                    $mensagem = "Senha atual incorreta.";
                    $tipo_mensagem = "danger";
                }
            }
        }
        
        if (isset($_POST['upload_foto'])) {
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $foto = $_FILES['foto'];
                $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
                $extensao = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
                
                if (!in_array($extensao, $extensoes_permitidas)) {
                    $mensagem = "Apenas arquivos JPG, JPEG, PNG e GIF são permitidos.";
                    $tipo_mensagem = "danger";
                } elseif ($foto['size'] > 5 * 1024 * 1024) { // 5MB
                    $mensagem = "O arquivo deve ter no máximo 5MB.";
                    $tipo_mensagem = "danger";
                } else {
                    // Criar diretório se não existir
                    $diretorio_upload = '../uploads/perfil/';
                    if (!is_dir($diretorio_upload)) {
                        mkdir($diretorio_upload, 0755, true);
                    }
                    
                    // Gerar nome único para o arquivo
                    $nome_arquivo = 'perfil_' . $cliente_id . '_' . time() . '.' . $extensao;
                    $caminho_arquivo = $diretorio_upload . $nome_arquivo;
                    
                    if (move_uploaded_file($foto['tmp_name'], $caminho_arquivo)) {
                        // Buscar foto anterior para deletar
                        $sql_foto_antiga = "SELECT foto FROM usuario WHERE id_usuario = ?";
                        $stmt_foto_antiga = $pdo->prepare($sql_foto_antiga);
                        $stmt_foto_antiga->execute([$cliente_id]);
                        $foto_antiga = $stmt_foto_antiga->fetchColumn();
                        
                        // Atualizar no banco de dados
                        $sql_update_foto = "UPDATE usuario SET foto = ? WHERE id_usuario = ?";
                        $stmt_update_foto = $pdo->prepare($sql_update_foto);
                        
                        if ($stmt_update_foto->execute([$caminho_arquivo, $cliente_id])) {
                            // Deletar foto antiga se existir
                            if ($foto_antiga && file_exists($foto_antiga)) {
                                unlink($foto_antiga);
                            }
                            
                            $mensagem = "Foto de perfil atualizada com sucesso!";
                            $tipo_mensagem = "success";
                        } else {
                            $mensagem = "Erro ao salvar foto no banco de dados.";
                            $tipo_mensagem = "danger";
                        }
                    } else {
                        $mensagem = "Erro ao fazer upload da foto. Tente novamente.";
                        $tipo_mensagem = "danger";
                    }
                }
            } else {
                $mensagem = "Erro no upload. Selecione uma foto válida.";
                $tipo_mensagem = "danger";
            }
        }
        
        if (isset($_POST['remover_foto'])) {
            // Buscar foto atual
            $sql_foto_atual = "SELECT foto FROM usuario WHERE id_usuario = ?";
            $stmt_foto_atual = $pdo->prepare($sql_foto_atual);
            $stmt_foto_atual->execute([$cliente_id]);
            $foto_atual = $stmt_foto_atual->fetchColumn();
            
            // Remover foto do banco
            $sql_remover_foto = "UPDATE usuario SET foto = NULL WHERE id_usuario = ?";
            $stmt_remover_foto = $pdo->prepare($sql_remover_foto);
            
            if ($stmt_remover_foto->execute([$cliente_id])) {
                // Deletar arquivo físico
                if ($foto_atual && file_exists($foto_atual)) {
                    unlink($foto_atual);
                }
                
                $mensagem = "Foto de perfil removida com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Erro ao remover foto. Tente novamente.";
                $tipo_mensagem = "danger";
            }
        }
        
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Buscar dados atuais do cliente
$sql_cliente = "SELECT * FROM usuario WHERE id_usuario = ?";
$stmt_cliente = $pdo->prepare($sql_cliente);
$stmt_cliente->execute([$cliente_id]);
$cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

// Buscar estatísticas do cliente
$sql_stats = "SELECT 
    COUNT(*) as total_agendamentos,
    COUNT(CASE WHEN status = 'concluido' THEN 1 END) as agendamentos_concluidos,
    COALESCE(SUM(CASE WHEN status = 'concluido' THEN valor END), 0) as total_gasto,
    MIN(data_agendamento) as primeiro_agendamento
    FROM agendamentos 
    WHERE cliente_id = ?";
$stmt_stats = $pdo->prepare($sql_stats);
$stmt_stats->execute([$cliente_id]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Buscar pontos de fidelidade
$sql_fidelidade = "SELECT pontos_atuais, pontos_acumulados, pontos_resgatados FROM fidelidade WHERE usuario_id = ?";
$stmt_fidelidade = $pdo->prepare($sql_fidelidade);
$stmt_fidelidade->execute([$cliente_id]);
$fidelidade = $stmt_fidelidade->fetch(PDO::FETCH_ASSOC);

include_once('topo_sistema_cliente.php');
?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1"><i class="bi bi-person-circle text-primary me-2"></i>Meu Perfil</h4>
                    <small class="text-muted">Gerencie suas informações pessoais e configurações</small>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-success fs-6 px-3 py-2">
                        <i class="bi bi-shield-check me-1"></i>Conta Ativa
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

            <div class="row">
                <!-- Sidebar com informações do perfil -->
                <div class="col-lg-4 mb-4">
                    <!-- Card de foto e informações básicas -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <?php if (!empty($cliente['foto']) && file_exists($cliente['foto'])): ?>
                                    <img src="<?php echo htmlspecialchars($cliente['foto']); ?>" alt="Foto do Perfil" 
                                         class="rounded-circle profile-photo mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mb-3 mx-auto" 
                                         style="width: 120px; height: 120px;">
                                        <i class="bi bi-person-fill text-muted display-4"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <h5 class="mb-1"><?php echo htmlspecialchars($cliente['nome']); ?></h5>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($cliente['email']); ?></p>
                            
                            <div class="d-flex gap-2 justify-content-center">
                                <button class="btn btn-custom btn-sm" data-bs-toggle="modal" data-bs-target="#fotoModal">
                                    <i class="bi bi-camera me-1"></i>Alterar Foto
                                </button>
                                <?php if (!empty($cliente['foto'])): ?>
                                <form method="POST" class="d-inline">
                                    <button type="submit" name="remover_foto" class="btn btn-outline-danger btn-sm" 
                                            onclick="return confirm('Tem certeza que deseja remover sua foto?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Card de estatísticas -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Minhas Estatísticas</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="stat-item">
                                        <h4 class="text-primary mb-1"><?php echo $stats['total_agendamentos']; ?></h4>
                                        <small class="text-muted">Agendamentos</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="stat-item">
                                        <h4 class="text-success mb-1"><?php echo $stats['agendamentos_concluidos']; ?></h4>
                                        <small class="text-muted">Concluídos</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-item">
                                        <h4 class="text-warning mb-1"><?php echo $fidelidade ? $fidelidade['pontos_atuais'] : 0; ?></h4>
                                        <small class="text-muted">Pontos</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-item">
                                        <h4 class="text-info mb-1">R$ <?php echo number_format($stats['total_gasto'], 2, ',', '.'); ?></h4>
                                        <small class="text-muted">Investido</small>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($stats['primeiro_agendamento']): ?>
                            <div class="text-center mt-3 pt-3 border-top">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-heart me-1"></i>
                                    Cliente desde <?php echo date('d/m/Y', strtotime($stats['primeiro_agendamento'])); ?>
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Formulários -->
                <div class="col-lg-8">
                    <!-- Dados Pessoais -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-gradient-dark text-white">
                            <h6 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Dados Pessoais</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nome Completo *</label>
                                        <input type="text" name="nome" class="form-control" 
                                               value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($cliente['email']); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">CPF</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($cliente['cpf']); ?>" readonly>
                                        <small class="text-muted">O CPF não pode ser alterado</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Telefone</label>
                                        <input type="text" name="telefone" class="form-control" 
                                               value="<?php echo htmlspecialchars($cliente['telefone'] ?? ''); ?>" 
                                               placeholder="(00) 00000-0000">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Data de Nascimento *</label>
                                        <input type="date" name="data_nascimento" class="form-control" 
                                               value="<?php echo $cliente['data_nascimento']; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Membro desde</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo date('d/m/Y', strtotime($cliente['data_cadastro'])); ?>" readonly>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="atualizar_perfil" class="btn btn-custom">
                                        <i class="bi bi-check-circle me-2"></i>Atualizar Dados Pessoais
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Alterar Senha -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Segurança da Conta</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Senha Atual *</label>
                                        <input type="password" name="senha_atual" class="form-control" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nova Senha *</label>
                                        <input type="password" name="nova_senha" class="form-control" 
                                               minlength="6" required>
                                        <small class="text-muted">Mínimo 6 caracteres</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Confirmar Nova Senha *</label>
                                        <input type="password" name="confirmar_senha" class="form-control" 
                                               minlength="6" required>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="alterar_senha" class="btn btn-warning">
                                        <i class="bi bi-shield-check me-2"></i>Alterar Senha
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Upload de Foto -->
<div class="modal fade" id="fotoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-camera me-2"></i>Alterar Foto de Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Selecionar Nova Foto</label>
                        <input type="file" name="foto" class="form-control" accept="image/*" required>
                        <small class="text-muted">Formatos aceitos: JPG, JPEG, PNG, GIF. Tamanho máximo: 5MB</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle me-2"></i>Dicas para uma boa foto:</h6>
                        <ul class="mb-0">
                            <li>Use uma foto recente e clara</li>
                            <li>Certifique-se de que seu rosto está bem visível</li>
                            <li>Evite fotos muito escuras ou com filtros</li>
                            <li>A foto será redimensionada automaticamente</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="upload_foto" class="btn btn-custom">
                        <i class="bi bi-upload me-1"></i>Fazer Upload
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

.btn-custom:focus,
.btn-custom:active {
    background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
    color: white;
    box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
}

.profile-photo {
    border: 4px solid #fff;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.profile-photo:hover {
    transform: scale(1.05);
}

.stat-item {
    padding: 1rem;
    border-radius: 8px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

.form-control:focus {
    border-color: #2c3e50;
    box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
}

.fs-6 {
    font-size: 0.9rem !important;
}

.card-header h6 {
    font-weight: 600;
}

@media (max-width: 768px) {
    .profile-photo {
        width: 100px !important;
        height: 100px !important;
    }
    
    .stat-item h4 {
        font-size: 1.2rem;
    }
}
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Máscara para telefone
    const telefoneInput = document.querySelector('input[name="telefone"]');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
                value = value.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
                value = value.replace(/^(\d{2})(\d{0,5})$/, '($1) $2');
                value = value.replace(/^(\d{0,2})$/, '($1');
            }
            e.target.value = value;
        });
    }
    
    // Validação do formulário de senha
    const formSenha = document.querySelector('form');
    if (formSenha) {
        formSenha.addEventListener('submit', function(e) {
            const novaSenha = document.querySelector('input[name="nova_senha"]');
            const confirmarSenha = document.querySelector('input[name="confirmar_senha"]');
            
            if (novaSenha && confirmarSenha && novaSenha.value !== confirmarSenha.value) {
                e.preventDefault();
                alert('A nova senha e a confirmação não coincidem.');
                confirmarSenha.focus();
            }
        });
    }
    
    // Preview da foto antes do upload
    const fotoInput = document.querySelector('input[name="foto"]');
    if (fotoInput) {
        fotoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Aqui você pode adicionar uma prévia da imagem se desejar
                    console.log('Arquivo selecionado:', file.name);
                };
                reader.readAsDataURL(file);
            }
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
