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

// Incluir biblioteca FPDF
require('../Bibliotecas/fpdf/fpdf.php');

// Verificar se a biblioteca FPDF está instalada
if (!class_exists('FPDF')) {
    // Se não estiver instalada, mostra um erro com instruções
    echo '
    <html>
    <head>
        <title>Biblioteca FPDF não encontrada</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
            .container { max-width: 800px; margin: 0 auto; }
            h1 { color: #c00; }
            code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Erro: Biblioteca FPDF não encontrada</h1>
            <p>Para utilizar este recurso, é necessário instalar a biblioteca FPDF.</p>
            <h2>Instruções de instalação:</h2>
            <ol>
                <li>Baixe a biblioteca FPDF em <a href="http://www.fpdf.org/" target="_blank">www.fpdf.org</a></li>
                <li>Crie uma pasta chamada "fpdf" dentro do diretório "Bibliotecas"</li>
                <li>Extraia os arquivos baixados para esta pasta</li>
                <li>Tente exportar novamente</li>
            </ol>
            <p><a href="../HTML/admin_relatorios.php">&laquo; Voltar para relatórios</a></p>
        </div>
    </body>
    </html>';
    exit;
}

// Criar PDF
class PDF extends FPDF
{
    function Header()
    {
        // Logo
        //$this->Image('../Imagens/logo.png', 10, 6, 30);
        // Título
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'Relatório da Barbearia', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 10, 'Data de exportação: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $this->Ln(10);
        
        // Cabeçalhos da tabela
        $this->SetFillColor(200, 200, 200);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(25, 7, 'Data', 1, 0, 'C', true);
        $this->Cell(45, 7, 'Serviço', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Profissional', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Cliente', 1, 0, 'C', true);
        $this->Cell(20, 7, 'Valor (R$)', 1, 0, 'C', true);
        $this->Cell(20, 7, 'Status', 1, 1, 'C', true);
    }

    function Footer()
    {
        // Posiciona a 1.5 cm do fim
        $this->SetY(-15);
        // Arial itálico 8
        $this->SetFont('Arial', 'I', 8);
        // Número da página
        $this->Cell(0, 10, 'Página ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Instanciar o PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 9);

// Adicionar linhas de dados
foreach ($dados as $linha) {
    $pdf->Cell(25, 6, date('d/m/Y', strtotime($linha['data_agendamento'])), 1, 0, 'L');
    $pdf->Cell(45, 6, utf8_decode(substr($linha['servico'], 0, 20)), 1, 0, 'L');
    $pdf->Cell(40, 6, utf8_decode(substr($linha['profissional'], 0, 18)), 1, 0, 'L');
    $pdf->Cell(40, 6, utf8_decode(substr($linha['cliente'], 0, 18)), 1, 0, 'L');
    $pdf->Cell(20, 6, number_format($linha['valor'], 2, ',', '.'), 1, 0, 'R');
    
    // Definir cor do status
    $status = $linha['status'];
    if ($status == 'concluído') {
        $pdf->SetTextColor(0, 128, 0); // Verde
    } elseif ($status == 'cancelado') {
        $pdf->SetTextColor(255, 0, 0); // Vermelho
    } elseif ($status == 'agendado') {
        $pdf->SetTextColor(255, 165, 0); // Laranja
    } else {
        $pdf->SetTextColor(128, 128, 128); // Cinza
    }
    
    $pdf->Cell(20, 6, utf8_decode(ucfirst($status)), 1, 1, 'C');
    $pdf->SetTextColor(0, 0, 0); // Voltar para preto
}

// Output do PDF
$pdf->Output('D', 'relatorio_barbearia_' . date('Y-m-d') . '.pdf');
?>
