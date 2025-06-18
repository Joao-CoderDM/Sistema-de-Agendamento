<?php
/**
 * Funções para consultar dados do dashboard do cliente usando PDO
 */

/**
 * Obtém todos os dados necessários para o dashboard do cliente
 */
function obterDadosDashboardCliente($pdo, $id_cliente) {
    return [
        'proximo_agendamento' => obterProximoAgendamento($pdo, $id_cliente),
        'historico_agendamentos' => obterHistoricoAgendamentos($pdo, $id_cliente),
        'pontos_fidelidade' => obterPontosFidelidade($pdo, $id_cliente),
        'servicos_populares' => obterServicosPopulares($pdo)
    ];
}

/**
 * Obtém o próximo agendamento do cliente
 */
function obterProximoAgendamento($pdo, $id_cliente) {
    $data_atual = date('Y-m-d');
    $hora_atual = date('H:i:s');
    
    $sql = "SELECT a.*, s.nome as nome_servico, u.nome as nome_profissional 
            FROM agendamentos a
            LEFT JOIN servicos s ON a.servico_id = s.id_servico
            LEFT JOIN usuario u ON a.profissional_id = u.id_usuario
            WHERE a.cliente_id = ? 
            AND ((a.data_agendamento = ? AND a.hora_agendamento >= ?) 
                OR a.data_agendamento > ?)
            AND a.status != 'cancelado'
            ORDER BY a.data_agendamento ASC, a.hora_agendamento ASC
            LIMIT 1";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_cliente, $data_atual, $hora_atual, $data_atual]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Comentar log em produção
        // error_log("Erro ao buscar próximo agendamento: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtém o histórico de agendamentos do cliente
 */
function obterHistoricoAgendamentos($pdo, $id_cliente, $limite = 5) {
    $sql = "SELECT a.*, s.nome as nome_servico, u.nome as nome_profissional 
            FROM agendamentos a
            LEFT JOIN servicos s ON a.servico_id = s.id_servico
            LEFT JOIN usuario u ON a.profissional_id = u.id_usuario
            WHERE a.cliente_id = ? 
            ORDER BY a.data_agendamento DESC, a.hora_agendamento DESC 
            LIMIT ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_cliente, $limite]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Comentar log em produção
        // error_log("Erro ao buscar histórico de agendamentos: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtém informações de pontos de fidelidade do cliente
 */
function obterPontosFidelidade($pdo, $id_cliente) {
    $sql = "SELECT pontos_atuais, pontos_acumulados, pontos_resgatados 
            FROM fidelidade 
            WHERE usuario_id = ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_cliente]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result;
        }
        
        return [
            'pontos_atuais' => 0,
            'pontos_acumulados' => 0,
            'pontos_resgatados' => 0
        ];
    } catch (PDOException $e) {
        // Comentar log em produção
        // error_log("Erro ao buscar pontos de fidelidade: " . $e->getMessage());
        return [
            'pontos_atuais' => 0,
            'pontos_acumulados' => 0,
            'pontos_resgatados' => 0
        ];
    }
}

/**
 * Obtém os serviços mais populares
 */
function obterServicosPopulares($pdo, $limite = 3) {
    $sql = "SELECT s.*, COUNT(a.id_agendamento) as total_agendamentos
            FROM servicos s
            LEFT JOIN agendamentos a ON s.id_servico = a.servico_id
            WHERE s.ativo = 1
            GROUP BY s.id_servico
            ORDER BY total_agendamentos DESC, s.nome
            LIMIT ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limite]);
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($result)) {
            return $result;
        }
        
        // Se não houver dados, buscar apenas os serviços ativos
        $sql_alt = "SELECT * FROM servicos WHERE ativo = 1 ORDER BY nome LIMIT ?";
        $stmt_alt = $pdo->prepare($sql_alt);
        $stmt_alt->execute([$limite]);
        
        return $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Comentar log em produção
        // error_log("Erro ao buscar serviços populares: " . $e->getMessage());
        
        // Retornar dados dummy em caso de erro
        return [
            [
                'id_servico' => 1,
                'nome' => 'Corte Masculino',
                'descricao' => 'Corte de cabelo tradicional',
                'valor' => 40.00,
                'duracao' => 30
            ],
            [
                'id_servico' => 2,
                'nome' => 'Barba',
                'descricao' => 'Barba completa com toalha quente',
                'valor' => 30.00,
                'duracao' => 20
            ],
            [
                'id_servico' => 3,
                'nome' => 'Combo Corte + Barba',
                'descricao' => 'Corte de cabelo e barba',
                'valor' => 65.00,
                'duracao' => 50
            ]
        ];
    }
}
?>
                'nome' => 'Barba',
                'descricao' => 'Barba completa com toalha quente',
                'valor' => 30.00,
                'duracao' => 20
            ],
            [
                'id_servico' => 3,
                'nome' => 'Combo Corte + Barba',
                'descricao' => 'Corte de cabelo e barba',
                'valor' => 65.00,
                'duracao' => 50
            ]
        ];
    }
}
?>
        return $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar serviços populares: " . $e->getMessage());
        return [];
    }
}
?>
        error_log("Erro ao buscar serviços populares: " . $e->getMessage());
        
        // Retornar dados dummy em caso de erro
        return [
            [
                'id_servico' => 1,
                'nome' => 'Corte Masculino',
                'descricao' => 'Corte de cabelo tradicional',
                'valor' => 40.00,
                'duracao' => 30
            ],
            [
                'id_servico' => 2,
                'nome' => 'Barba',
                'descricao' => 'Barba completa com toalha quente',
                'valor' => 30.00,
                'duracao' => 20
            ],
            [
                'id_servico' => 3,
                'nome' => 'Combo Corte + Barba',
                'descricao' => 'Corte de cabelo e barba',
                'valor' => 65.00,
                'duracao' => 50
            ]
        ];
    }
}
?>
