<?php
// Funções para consultar dados do dashboard administrativo

// Função para obter o número de agendamentos para o dia atual
function obterAgendamentosHoje($pdo) {
    $data_hoje = date('Y-m-d');
    // Corrigir o enum - no banco está 'agendado', não 'agendado'
    $sql = "SELECT COUNT(*) as total FROM agendamentos WHERE data_agendamento = ? AND status IN ('agendado', 'confirmado')";
    
    try {
        $stmt = $pdo->prepare($sql);
        if (!$stmt) {
            return ['total' => 0, 'variacao' => 0];
        }
        
        $stmt->execute([$data_hoje]);
        $dados_hoje = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Buscar agendamentos de ontem para comparação
        $data_ontem = date('Y-m-d', strtotime('-1 day'));
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data_ontem]);
        $dados_ontem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calcular a variação percentual
        $total_hoje = intval($dados_hoje['total']);
        $total_ontem = intval($dados_ontem['total']);
        
        if ($total_ontem > 0) {
            $variacao = (($total_hoje - $total_ontem) / $total_ontem) * 100;
        } else {
            $variacao = $total_hoje > 0 ? 100 : 0;
        }
        
        return [
            'total' => $total_hoje,
            'variacao' => round($variacao)
        ];
    } catch (PDOException $e) {
        error_log("Erro em obterAgendamentosHoje: " . $e->getMessage());
        return ['total' => 0, 'variacao' => 0];
    }
}

// Função para obter o faturamento semanal
function obterFaturamentoSemanal($pdo) {
    $data_inicio = date('Y-m-d', strtotime('-7 days'));
    $data_fim = date('Y-m-d');
    
    // Corrigir o enum - no banco está 'concluido', não 'concluído'
    $sql = "SELECT COALESCE(SUM(valor), 0) as total FROM agendamentos 
            WHERE data_agendamento BETWEEN ? AND ? 
            AND status = 'concluido'";
    
    try {
        $stmt = $pdo->prepare($sql);
        if (!$stmt) {
            return ['total' => 0, 'variacao' => 0];
        }
        
        $stmt->execute([$data_inicio, $data_fim]);
        $dados_semana_atual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Buscar faturamento da semana anterior para comparação
        $data_inicio_anterior = date('Y-m-d', strtotime('-14 days'));
        $data_fim_anterior = date('Y-m-d', strtotime('-8 days'));
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data_inicio_anterior, $data_fim_anterior]);
        $dados_semana_anterior = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calcular a variação percentual
        $total_atual = floatval($dados_semana_atual['total']) ?: 0;
        $total_anterior = floatval($dados_semana_anterior['total']) ?: 0;
        
        if ($total_anterior > 0) {
            $variacao = (($total_atual - $total_anterior) / $total_anterior) * 100;
        } else {
            $variacao = $total_atual > 0 ? 100 : 0;
        }
        
        return [
            'total' => $total_atual,
            'variacao' => round($variacao)
        ];
    } catch (PDOException $e) {
        error_log("Erro em obterFaturamentoSemanal: " . $e->getMessage());
        return ['total' => 0, 'variacao' => 0];
    }
}

// Função para obter o número de clientes ativos
function obterClientesAtivos($pdo) {
    // Clientes com agendamento nos últimos 30 dias
    $data_limite = date('Y-m-d', strtotime('-30 days'));
    
    $sql = "SELECT COUNT(DISTINCT cliente_id) as total FROM agendamentos 
            WHERE data_agendamento >= ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        if (!$stmt) {
            return ['total' => 0, 'variacao' => 0];
        }
        
        $stmt->execute([$data_limite]);
        $dados_mes_atual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Clientes ativos no mês anterior
        $data_inicio_anterior = date('Y-m-d', strtotime('-60 days'));
        $data_fim_anterior = date('Y-m-d', strtotime('-31 days'));
        
        $sql = "SELECT COUNT(DISTINCT cliente_id) as total FROM agendamentos 
                WHERE data_agendamento BETWEEN ? AND ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data_inicio_anterior, $data_fim_anterior]);
        $dados_mes_anterior = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calcular a variação percentual
        $total_atual = intval($dados_mes_atual['total']) ?: 0;
        $total_anterior = intval($dados_mes_anterior['total']) ?: 0;
        
        if ($total_anterior > 0) {
            $variacao = (($total_atual - $total_anterior) / $total_anterior) * 100;
        } else {
            $variacao = $total_atual > 0 ? 100 : 0;
        }
        
        return [
            'total' => $total_atual,
            'variacao' => round($variacao)
        ];
    } catch (PDOException $e) {
        error_log("Erro em obterClientesAtivos: " . $e->getMessage());
        return ['total' => 0, 'variacao' => 0];
    }
}

// Função para obter a avaliação média
function obterAvaliacaoMedia($pdo) {
    try {
        // Verificar se a tabela feedback existe primeiro
        $check_table = $pdo->query("SHOW TABLES LIKE 'feedback'");
        if ($check_table->rowCount() == 0) {
            return ['media' => '0.0', 'variacao' => '0.0'];
        }
        
        // Média das avaliações
        $sql = "SELECT AVG(avaliacao) as media FROM feedback WHERE avaliacao > 0";
        $stmt = $pdo->query($sql);
        $dados_atual = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Calcular a média do mês anterior
        $data_limite = date('Y-m-d', strtotime('-30 days'));
        
        // Verificar se a coluna data_criacao existe
        $check_column = $pdo->query("SHOW COLUMNS FROM feedback LIKE 'data_criacao'");
        
        if ($check_column->rowCount() > 0) {
            $sql = "SELECT AVG(avaliacao) as media FROM feedback 
                    WHERE avaliacao > 0 AND data_criacao < ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data_limite]);
            $dados_anterior = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $media_atual = floatval($dados_atual['media']) ?: 0;
            $media_anterior = floatval($dados_anterior['media']) ?: 0;
            
            if ($media_anterior > 0) {
                $variacao = $media_atual - $media_anterior;
            } else {
                $variacao = 0;
            }
        } else {
            $media_atual = floatval($dados_atual['media']) ?: 0;
            $variacao = 0;
        }
        
        return [
            'media' => number_format($media_atual ?: 0, 1),
            'variacao' => number_format($variacao, 1)
        ];
    } catch (PDOException $e) {
        error_log("Erro em obterAvaliacaoMedia: " . $e->getMessage());
        return [
            'media' => '0.0',
            'variacao' => '0.0'
        ];
    }
}

// Função para obter todos os dados do dashboard
function obterDadosDashboard($pdo) {
    // Para evitar erros no inicio do uso do sistema, verificamos se as tabelas existem
    $tabelas = ['agendamentos', 'servicos', 'profissionais', 'fidelidade'];
    $tabelas_faltando = [];
    
    foreach ($tabelas as $tabela) {
        try {
            $stmt = $pdo->query("SELECT 1 FROM {$tabela} LIMIT 1");
        } catch (PDOException $e) {
            $tabelas_faltando[] = $tabela;
        }
    }
    
    // Buscar os dados reais sempre, mesmo se algumas tabelas estiverem faltando
    $dados = [
        'agendamentos_hoje' => obterAgendamentosHoje($pdo),
        'faturamento_semanal' => obterFaturamentoSemanal($pdo),
        'clientes_ativos' => obterClientesAtivos($pdo),
        'avaliacao_media' => obterAvaliacaoMedia($pdo),
        'tabelas_faltando' => $tabelas_faltando
    ];
    
    // Log para debug
    error_log("Dados dashboard: " . print_r($dados, true));
    
    return $dados;
}
?>
