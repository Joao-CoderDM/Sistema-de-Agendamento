<?php 
// Iniciar sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if(!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'Administrador') {
    header('Location: login.php');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
include_once('../Conexao/conexao.php');

// Incluir arquivo de consultas para o dashboard
include_once('../Consultas/consultar_dashboard.php');

// Debug: verificar se a conexão está funcionando
try {
    $test_query = $pdo->query("SELECT 1");
    error_log("Conexão PDO funcionando");
} catch (PDOException $e) {
    error_log("Erro na conexão PDO: " . $e->getMessage());
}

// Obter dados do dashboard usando o objeto PDO
$dados_dashboard = obterDadosDashboard($pdo);

// Debug: mostrar dados obtidos
error_log("Dados obtidos do dashboard: " . print_r($dados_dashboard, true));

// Se houver tabelas faltando, executar o script de criação
if (!empty($dados_dashboard['tabelas_faltando'])) {
    $script_path = '../Banco_de_Dados/criar_tabelas_dashboard.sql';
    if (file_exists($script_path)) {
        try {
            $sql = file_get_contents($script_path);
            $statements = explode(';', $sql);
            
            foreach ($statements as $statement) {
                if (trim($statement) != '') {
                    $pdo->exec($statement);
                }
            }
            
            // Atualizar dados do dashboard após criar as tabelas
            $dados_dashboard = obterDadosDashboard($pdo);
        } catch (PDOException $e) {
            // Registrar erro, mas continuar a execução
            error_log('Erro ao criar tabelas: ' . $e->getMessage());
        }
    }
}

// Buscar dados do usuário logado incluindo foto
$usuario_id = $_SESSION['id_usuario'];
$sql_user = "SELECT nome, email, foto FROM usuario WHERE id_usuario = ?";
$stmt_user = $pdo->prepare($sql_user);
$stmt_user->execute([$usuario_id]);
$usuario_logado = $stmt_user->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Administração - Barbearia Mosanberk</title>
    <link rel="icon" href="../Imagens/logotipo.png" type="image/png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="../CSS/Admin/style_sistema_adm.css">
    <link rel="stylesheet" href="../CSS/style_responsive.css">
    <link rel="stylesheet" href="../CSS/style_imagens_responsivas.css">
    <link href="https://fonts.googleapis.com/css2?family=Belinda&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="sistema_adm.php">
                <img src="../Imagens/logotipo.png" alt="Logo" width="40" height="40">
                <span class="ms-2 fw-bold">Barbearia Mosanberk</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'sistema_adm.php') ? 'active' : ''; ?>" href="sistema_adm.php">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_usuarios.php') ? 'active' : ''; ?>" href="admin_usuarios.php">
                            <i class="bi bi-people me-1"></i> Usuários
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_servicos.php') ? 'active' : ''; ?>" href="admin_servicos.php">
                            <i class="bi bi-scissors me-1"></i> Serviços
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_agendamentos.php') ? 'active' : ''; ?>" href="admin_agendamentos.php">
                            <i class="bi bi-calendar-check me-1"></i> Agenda
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-grid me-1"></i> Mais
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="admin_profissionais.php"><i class="bi bi-person-badge me-2"></i>Profissionais</a></li>
                            <li><a class="dropdown-item" href="admin_feedback.php"><i class="bi bi-star me-2"></i>Feedbacks</a></li>
                            <li><a class="dropdown-item" href="admin_relatorios.php"><i class="bi bi-graph-up me-2"></i>Relatórios</a></li>
                            <li><a class="dropdown-item" href="admin_fidelidade.php"><i class="bi bi-award me-2"></i>Fidelidade</a></li>
                        </ul>
                    </li>
                    <div class="nav-divider d-none d-lg-block"></div>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="me-2">
                                <?php if (!empty($usuario_logado['foto']) && file_exists($usuario_logado['foto'])): ?>
                                    <img src="<?php echo htmlspecialchars($usuario_logado['foto']); ?>" alt="Foto do Perfil" 
                                         class="rounded-circle" style="width: 35px; height: 35px; object-fit: cover; border: 2px solid #fff;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" 
                                         style="width: 35px; height: 35px; border: 2px solid #fff;">
                                        <i class="bi bi-person-fill text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['nome']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li>
                                <div class="dropdown-header">
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($usuario_logado['foto']) && file_exists($usuario_logado['foto'])): ?>
                                            <img src="<?php echo htmlspecialchars($usuario_logado['foto']); ?>" alt="Foto do Perfil" 
                                                 class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2" 
                                                 style="width: 40px; height: 40px;">
                                                <i class="bi bi-person-fill text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['nome']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($_SESSION['email'] ?? $usuario_logado['email']); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            
                            <li>
                                <a class="dropdown-item" href="admin_perfil.php">
                                    <i class="bi bi-person-circle me-2"></i>Meu Perfil
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main>
    <!-- Bootstrap JS -->

    <!-- Custom JS -->
    <script src="../JS/topo_sistema_adm.js"></script>

    <style>
    /* Estilo para foto de perfil no dropdown */
    
.dropdown-header {
    padding: 0.75rem 1rem;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.dropdown-item {
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    transform: translateX(3px);
}

.dropdown-menu {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border-radius: 0.5rem;
    min-width: 250px;
}

/* Animação para o avatar */
.nav-link img, .nav-link .rounded-circle {
    transition: transform 0.2s ease;
}

.nav-link:hover img, .nav-link:hover .rounded-circle {
    transform: scale(1.1);
}
    </style>
</body>
</html>
