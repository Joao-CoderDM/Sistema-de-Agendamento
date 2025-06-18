<?php
session_start();
header('Content-Type: application/json');

// Verificar se o usuário está logado
if(!isset($_SESSION['loggedin'])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

include_once('../Conexao/conexao.php');

// Receber dados JSON
$input = json_decode(file_get_contents('php://input'), true);
$profissional_id = $input['profissional_id'] ?? null;
$data = $input['data'] ?? null;

if (!$profissional_id || !$data) {
    echo json_encode(['error' => 'Parâmetros inválidos']);
    exit;
}

try {
    // Buscar configuração da agenda do profissional
    $stmt = $pdo->prepare("SELECT * FROM profissional_agenda WHERE profissional_id = ?");
    $stmt->execute([$profissional_id]);
    $config_agenda = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config_agenda) {
        echo json_encode(['horarios' => [], 'message' => 'Profissional sem agenda configurada']);
        exit;
    }
    
    // Mapear dias da semana
    $dias_semana_map = [
        0 => 'domingo', 1 => 'segunda', 2 => 'terca', 3 => 'quarta',
        4 => 'quinta', 5 => 'sexta', 6 => 'sabado'
    ];
    
    $dia_semana = date('w', strtotime($data));
    $dia_campo = $dias_semana_map[$dia_semana];
    
    // Verificar se trabalha neste dia
    if (!$config_agenda["{$dia_campo}_trabalha"]) {
        echo json_encode(['horarios' => [], 'message' => 'Profissional não trabalha neste dia']);
        exit;
    }
    
    $hora_inicio = $config_agenda["{$dia_campo}_inicio"];
    $hora_fim = $config_agenda["{$dia_campo}_fim"];
    $intervalo_inicio = $config_agenda["{$dia_campo}_intervalo_inicio"];
    $intervalo_fim = $config_agenda["{$dia_campo}_intervalo_fim"];
    
    if (!$hora_inicio || !$hora_fim) {
        echo json_encode(['horarios' => [], 'message' => 'Horários não configurados para este dia']);
        exit;
    }
    
    $horarios = [];
    $inicio = new DateTime($hora_inicio);
    $fim = new DateTime($hora_fim);
    $intervalo = new DateInterval('PT30M'); // Intervalos de 30 minutos
    
    // Buscar todos os agendamentos do dia com suas durações
    $sql_agendamentos = "SELECT a.hora_agendamento, s.duracao 
                        FROM agendamentos a
                        JOIN servicos s ON a.servico_id = s.id_servico
                        WHERE a.profissional_id = ? AND a.data_agendamento = ? 
                        AND a.status NOT IN ('cancelado')";
    $stmt_agendamentos = $pdo->prepare($sql_agendamentos);
    $stmt_agendamentos->execute([$profissional_id, $data]);
    $agendamentos_do_dia = $stmt_agendamentos->fetchAll(PDO::FETCH_ASSOC);
    
    // Função para verificar se um horário está ocupado considerando a duração dos serviços
    function horarioEstaOcupado($horario_teste, $agendamentos_do_dia) {
        $horario_teste_obj = new DateTime($horario_teste);
        
        foreach ($agendamentos_do_dia as $agendamento) {
            $inicio_agendamento = new DateTime($agendamento['hora_agendamento']);
            $duracao_minutos = $agendamento['duracao'] ?? 30;
            $fim_agendamento = clone $inicio_agendamento;
            $fim_agendamento->add(new DateInterval("PT{$duracao_minutos}M"));
            
            // Verificar se o horário de teste está dentro do período ocupado
            // Consideramos ocupado se o horário de teste + 30min (duração mínima de um slot) 
            // sobrepõe com o agendamento existente
            $fim_teste = clone $horario_teste_obj;
            $fim_teste->add(new DateInterval('PT30M'));
            
            // Há sobreposição se:
            // - O início do teste é antes do fim do agendamento E
            // - O fim do teste é depois do início do agendamento
            if ($horario_teste_obj < $fim_agendamento && $fim_teste > $inicio_agendamento) {
                return true;
            }
        }
        
        return false;
    }
    
    while ($inicio < $fim) {
        $horario_atual = $inicio->format('H:i:s');
        
        // Verificar se está no intervalo de pausa específico do dia
        $esta_no_intervalo = false;
        if ($intervalo_inicio && $intervalo_fim) {
            $hora_atual = $inicio->format('H:i:s');
            if ($hora_atual >= $intervalo_inicio && $hora_atual < $intervalo_fim) {
                $esta_no_intervalo = true;
            }
        }
        
        if (!$esta_no_intervalo) {
            // Verificar se o horário está ocupado considerando a duração dos serviços
            if (!horarioEstaOcupado($horario_atual, $agendamentos_do_dia)) {
                $horarios[] = $horario_atual;
            }
        }
        
        $inicio->add($intervalo);
    }
    
    // Debug mais detalhado
    $sql_debug = "SELECT a.hora_agendamento, a.status, s.nome as servico_nome, s.duracao 
                  FROM agendamentos a
                  JOIN servicos s ON a.servico_id = s.id_servico
                  WHERE a.profissional_id = ? AND a.data_agendamento = ? 
                  ORDER BY a.hora_agendamento";
    $stmt_debug = $pdo->prepare($sql_debug);
    $stmt_debug->execute([$profissional_id, $data]);
    $agendamentos_existentes = $stmt_debug->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'horarios' => $horarios,
        'debug' => [
            'dia_semana' => $dia_semana,
            'dia_campo' => $dia_campo,
            'trabalha' => $config_agenda["{$dia_campo}_trabalha"],
            'hora_inicio' => $hora_inicio,
            'hora_fim' => $hora_fim,
            'intervalo_inicio' => $intervalo_inicio,
            'intervalo_fim' => $intervalo_fim,
            'agendamentos_existentes' => $agendamentos_existentes,
            'data_pesquisada' => $data,
            'profissional_id' => $profissional_id,
            'total_horarios_disponiveis' => count($horarios)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
}
?>
