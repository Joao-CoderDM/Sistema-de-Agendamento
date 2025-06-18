<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'Profissional') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

include_once('../Conexao/conexao.php');

$data = $_GET['data'] ?? '';
$dia_semana = $_GET['dia_semana'] ?? '';
$profissional_id = $_SESSION['id_usuario'];

try {
    // Mapear números dos dias da semana para nomes dos campos
    $dias_semana_map = [
        0 => 'domingo',
        1 => 'segunda', 
        2 => 'terca',
        3 => 'quarta',
        4 => 'quinta',
        5 => 'sexta',
        6 => 'sabado'
    ];
    
    $dia_campo = $dias_semana_map[$dia_semana] ?? 'segunda';
    
    // Verificar configuração da agenda do profissional
    $stmt = $pdo->prepare("SELECT * FROM profissional_agenda WHERE profissional_id = ?");
    $stmt->execute([$profissional_id]);
    $agenda = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($agenda && $agenda["{$dia_campo}_trabalha"]) {
        // Verificar se há bloqueios para esta data
        $stmt = $pdo->prepare("SELECT * FROM dias_bloqueados WHERE profissional_id = ? AND data_bloqueio = ?");
        $stmt->execute([$profissional_id, $data]);
        $bloqueio = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bloqueio) {
            echo json_encode([
                'success' => false,
                'message' => 'Este dia está bloqueado: ' . $bloqueio['motivo']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'horarios' => [
                    'hora_inicio' => $agenda["{$dia_campo}_inicio"],
                    'hora_fim' => $agenda["{$dia_campo}_fim"],
                    'intervalo_inicio' => $agenda["{$dia_campo}_intervalo_inicio"],
                    'intervalo_fim' => $agenda["{$dia_campo}_intervalo_fim"],
                    'intervalo_minutos' => $agenda['intervalo_agendamentos']
                ]
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Profissional não trabalha neste dia da semana'
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
