<?php
session_start();

// Verifica se o usuário está logado e é profissional
if(!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'Profissional'){
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Incluir arquivo de conexão com o banco de dados
include_once('../../Conexao/conexao.php');

$profissional_id = $_SESSION['id_usuario'];
$cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;

if (!$cliente_id) {
    echo json_encode(['success' => false, 'message' => 'ID do cliente não informado']);
    exit;
}

try {
    // Buscar histórico de agendamentos do cliente com o profissional
    $sql = "SELECT a.*, s.nome as servico_nome, s.duracao,
            DATE_FORMAT(a.data_agendamento, '%d/%m/%Y') as data_agendamento_formatada,
            TIME_FORMAT(a.hora_agendamento, '%H:%i') as hora_agendamento_formatada
            FROM agendamentos a
            INNER JOIN servicos s ON a.servico_id = s.id_servico
            WHERE a.cliente_id = ? AND a.profissional_id = ?
            ORDER BY a.data_agendamento DESC, a.hora_agendamento DESC
            LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cliente_id, $profissional_id]);
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatar os dados para o frontend
    $historico_formatado = [];
    foreach ($historico as $item) {
        $historico_formatado[] = [
            'id_agendamento' => $item['id_agendamento'],
            'data_agendamento' => $item['data_agendamento_formatada'],
            'hora_agendamento' => $item['hora_agendamento_formatada'],
            'servico_nome' => $item['servico_nome'],
            'valor' => number_format($item['valor'], 2, ',', '.'),
            'status' => ucfirst($item['status']),
            'duracao' => $item['duracao'],
            'observacoes' => $item['observacoes']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'historico' => $historico_formatado
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar histórico: ' . $e->getMessage()
    ]);
}
?>
