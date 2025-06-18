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
    // Buscar configuração de fidelidade ativa
    $sql_config = "SELECT * FROM config_fid WHERE ativo = 1 LIMIT 1";
    $stmt_config = $pdo->prepare($sql_config);
    $stmt_config->execute();
    $config = $stmt_config->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        // Se não há configuração, criar uma padrão
        $sql_insert_config = "INSERT INTO config_fid (pontos_por_real, pontos_expiracao_dias, ativo) VALUES (1.00, 365, 1)";
        $pdo->exec($sql_insert_config);
        $config_id = $pdo->lastInsertId();
        $pontos_por_real = 1.00;
        $mensagem .= "Configuração de fidelidade criada com 1 ponto por real. ";
    } else {
        $config_id = $config['id_config'];
        $pontos_por_real = $config['pontos_por_real'];
    }
    
    // Buscar todos os agendamentos concluídos que não têm pontos calculados
    $sql_agendamentos = "SELECT a.cliente_id, a.valor, a.id_agendamento, u.nome as cliente_nome
                        FROM agendamentos a 
                        JOIN usuario u ON a.cliente_id = u.id_usuario
                        WHERE a.status = 'concluido'
                        ORDER BY a.cliente_id, a.data_agendamento";
    $stmt_agendamentos = $pdo->prepare($sql_agendamentos);
    $stmt_agendamentos->execute();
    $agendamentos = $stmt_agendamentos->fetchAll(PDO::FETCH_ASSOC);
    
    $clientes_processados = [];
    $total_pontos_criados = 0;
    $clientes_atualizados = 0;
    
    foreach ($agendamentos as $agendamento) {
        $cliente_id = $agendamento['cliente_id'];
        $valor = $agendamento['valor'];
        $pontos_ganhos = floor($valor * $pontos_por_real);
        
        // Verificar se cliente já tem registro de fidelidade
        $sql_fidelidade = "SELECT * FROM fidelidade WHERE usuario_id = ?";
        $stmt_fidelidade = $pdo->prepare($sql_fidelidade);
        $stmt_fidelidade->execute([$cliente_id]);
        $fidelidade = $stmt_fidelidade->fetch(PDO::FETCH_ASSOC);
        
        if (!isset($clientes_processados[$cliente_id])) {
            $clientes_processados[$cliente_id] = [
                'nome' => $agendamento['cliente_nome'],
                'pontos_total' => 0,
                'agendamentos' => 0
            ];
        }
        
        $clientes_processados[$cliente_id]['pontos_total'] += $pontos_ganhos;
        $clientes_processados[$cliente_id]['agendamentos']++;
        $total_pontos_criados += $pontos_ganhos;
    }
    
    // Processar cada cliente
    foreach ($clientes_processados as $cliente_id => $dados) {
        $sql_fidelidade = "SELECT * FROM fidelidade WHERE usuario_id = ?";
        $stmt_fidelidade = $pdo->prepare($sql_fidelidade);
        $stmt_fidelidade->execute([$cliente_id]);
        $fidelidade = $stmt_fidelidade->fetch(PDO::FETCH_ASSOC);
        
        if ($fidelidade) {
            // Atualizar pontos existentes
            $sql_update = "UPDATE fidelidade SET 
                          pontos_atuais = pontos_atuais + ?, 
                          pontos_acumulados = pontos_acumulados + ? 
                          WHERE usuario_id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$dados['pontos_total'], $dados['pontos_total'], $cliente_id]);
        } else {
            // Criar novo registro de fidelidade
            $sql_insert = "INSERT INTO fidelidade (usuario_id, config_id, pontos_atuais, pontos_acumulados, pontos_resgatados) 
                          VALUES (?, ?, ?, ?, 0)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([$cliente_id, $config_id, $dados['pontos_total'], $dados['pontos_total']]);
        }
        
        $clientes_atualizados++;
    }
    
    $mensagem .= "Pontos de fidelidade corrigidos com sucesso! ";
    $mensagem .= "Processados {$clientes_atualizados} clientes, ";
    $mensagem .= "total de {$total_pontos_criados} pontos criados.";
    $tipo_mensagem = "success";
    
} catch (Exception $e) {
    $mensagem = "Erro ao corrigir pontos: " . $e->getMessage();
    $tipo_mensagem = "danger";
}

include_once('topo_sistema_adm.php');
?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-tools me-2"></i>Correção de Pontos de Fidelidade</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?>" role="alert">
                        <i class="bi bi-<?php echo $tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                        <?php echo $mensagem; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Detalhes do Processamento:</h6>
                            <?php if (!empty($clientes_processados)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Agendamentos</th>
                                            <th>Pontos Adicionados</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($clientes_processados as $dados): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($dados['nome']); ?></td>
                                            <td><?php echo $dados['agendamentos']; ?></td>
                                            <td><span class="badge bg-success"><?php echo $dados['pontos_total']; ?> pts</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle me-2"></i>Informações:</h6>
                                <ul class="mb-0">
                                    <li>Este script processa todos os agendamentos concluídos</li>
                                    <li>Calcula os pontos baseado na configuração atual (<?php echo $pontos_por_real; ?> ponto por real)</li>
                                    <li>Cria registros de fidelidade para clientes que não possuem</li>
                                    <li>Atualiza pontos para clientes existentes</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="sistema_adm.php" class="btn btn-primary">
                                    <i class="bi bi-arrow-left me-2"></i>Voltar ao Dashboard
                                </a>
                                <a href="admin_fidelidade.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-star me-2"></i>Gerenciar Fidelidade
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
