<?php
session_start();

// Verifica se o usuário está logado e é admin
if(!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'adm'){
    header('Location: ../HTML/login.php');
    exit;
}

// Verifica se existem dados para exportar
if(!isset($_SESSION['relatorio_dados']) || empty($_SESSION['relatorio_dados'])) {
    echo "Não há dados para exportar. Por favor, gere um relatório primeiro.";
    exit;
}

$dados = $_SESSION['relatorio_dados'];

// Definir tipo de conteúdo e cabeçalhos para download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="relatorio_barbearia_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Criar o arquivo Excel (formato HTML que o Excel pode interpretar)
echo '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Relatório Barbearia</title>
    <style>
        table {border-collapse: collapse; width: 100%;}
        th, td {border: 1px solid #000; padding: 8px; text-align: left;}
        th {background-color: #f2f2f2;}
    </style>
</head>
<body>
    <h1>Relatório da Barbearia</h1>
    <h3>Data de Exportação: ' . date('d/m/Y H:i:s') . '</h3>
    
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Serviço</th>
                <th>Profissional</th>
                <th>Cliente</th>
                <th>Valor (R$)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>';

foreach ($dados as $linha) {
    echo '<tr>';
    echo '<td>' . date('d/m/Y', strtotime($linha['data_agendamento'])) . '</td>';
    echo '<td>' . htmlspecialchars($linha['servico']) . '</td>';
    echo '<td>' . htmlspecialchars($linha['profissional']) . '</td>';
    echo '<td>' . htmlspecialchars($linha['cliente']) . '</td>';
    echo '<td>' . number_format($linha['valor'], 2, ',', '.') . '</td>';
    echo '<td>' . ucfirst($linha['status']) . '</td>';
    echo '</tr>';
}

echo '
        </tbody>
    </table>
</body>
</html>';
?>
