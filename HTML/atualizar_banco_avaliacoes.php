<?php
session_start();

// Verifica se o usuário está logado e é administrador
if(!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'Administrador'){
    header('Location: login.php');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
include_once('../Conexao/conexao.php');

$mensagem = '';
$tipo_mensagem = '';

try {
    // Verificar se as colunas já existem
    $sql_check = "SHOW COLUMNS FROM feedback LIKE 'avaliacao_profissional'";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute();
    $coluna_existe = $stmt_check->rowCount() > 0;
    
    if (!$coluna_existe) {
        // Adicionar coluna avaliacao_profissional
        $sql_add_col1 = "ALTER TABLE feedback 
                        ADD COLUMN avaliacao_profissional INT(11) DEFAULT NULL COMMENT 'Avaliação do profissional de 1 a 5 estrelas'";
        $pdo->exec($sql_add_col1);
        
        // Adicionar coluna comentario_profissional
        $sql_add_col2 = "ALTER TABLE feedback 
                        ADD COLUMN comentario_profissional TEXT DEFAULT NULL COMMENT 'Comentário específico sobre o profissional'";
        $pdo->exec($sql_add_col2);
        
        // Atualizar comentários das colunas existentes
        $sql_modify1 = "ALTER TABLE feedback 
                       MODIFY COLUMN avaliacao INT(11) DEFAULT 0 COMMENT 'Avaliação do serviço de 1 a 5 estrelas'";
        $pdo->exec($sql_modify1);
        
        $sql_modify2 = "ALTER TABLE feedback 
                       MODIFY COLUMN mensagem TEXT NOT NULL COMMENT 'Comentário sobre o serviço'";
        $pdo->exec($sql_modify2);
        
        $mensagem = "Banco de dados atualizado com sucesso! Agora os clientes podem avaliar tanto o serviço quanto o profissional.";
        $tipo_mensagem = "success";
    } else {
        $mensagem = "Banco de dados já está atualizado. As colunas de avaliação do profissional já existem.";
        $tipo_mensagem = "info";
    }
    
} catch (Exception $e) {
    $mensagem = "Erro ao atualizar banco de dados: " . $e->getMessage();
    $tipo_mensagem = "danger";
}

include_once('topo_sistema_adm.php');
?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-database-gear me-2"></i>Atualização do Sistema de Avaliações</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?>" role="alert">
                        <i class="bi bi-<?php echo $tipo_mensagem === 'success' ? 'check-circle' : ($tipo_mensagem === 'info' ? 'info-circle' : 'exclamation-triangle'); ?> me-2"></i>
                        <?php echo $mensagem; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Atualizações Realizadas:</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Adicionada coluna <code>avaliacao_profissional</code>
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Adicionada coluna <code>comentario_profissional</code>
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Atualizados comentários das colunas existentes
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Sistema de avaliação dual implementado
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle me-2"></i>Funcionalidades Adicionadas:</h6>
                                <ul class="mb-0">
                                    <li>Clientes podem avaliar o serviço (1-5 estrelas)</li>
                                    <li>Clientes podem avaliar o profissional (1-5 estrelas)</li>
                                    <li>Comentários separados para serviço e profissional</li>
                                    <li>Estatísticas separadas na página de avaliações</li>
                                    <li>Exibição diferenciada das avaliações</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="sistema_adm.php" class="btn btn-primary">
                                    <i class="bi bi-arrow-left me-2"></i>Voltar ao Dashboard
                                </a>
                                <a href="admin_feedback.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-star me-2"></i>Gerenciar Feedbacks
                                </a>
                                <a href="cliente_avaliacoes.php" class="btn btn-outline-success">
                                    <i class="bi bi-eye me-2"></i>Ver Sistema de Avaliações
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-5"></div>
