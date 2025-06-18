<?php 
// Iniciar sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado (corrigir case sensitivity)
if(!isset($_SESSION['loggedin']) || strtolower($_SESSION['tipo_usuario']) !== 'cliente') {
    header('Location: login.php');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
include_once('../Conexao/conexao.php');

// Buscar dados do usuário logado incluindo foto
$usuario_id = $_SESSION['id_usuario'];
$sql_user = "SELECT nome, email, foto FROM usuario WHERE id_usuario = ?";
$stmt_user = $pdo->prepare($sql_user);
$stmt_user->execute([$usuario_id]);
$usuario_logado = $stmt_user->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Cliente - Barbearia Mosanberk</title>
    <link rel="icon" href="../Imagens/logotipo.png" type="image/png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Fontes -->
    <link href="https://fonts.googleapis.com/css2?family=Belinda&display=swap" rel="stylesheet">
    <!-- CSS personalizado -->
    <link rel="stylesheet" href="../CSS/Cliente/sistema_cliente.css">
    <link rel="stylesheet" href="../CSS/style_responsive.css">
    <link rel="stylesheet" href="../CSS/style_imagens_responsivas.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
        <div class="container-fluid px-4">
            <!-- Brand -->
            <a class="navbar-brand d-flex align-items-center" href="sistema_cliente.php">
                <img src="../Imagens/logotipo.png" alt="Logo" width="40" height="40">
                <span class="ms-2 fw-bold">Barbearia Mosanberk</span>
            </a>

            <!-- Mobile menu button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'sistema_cliente.php') ? 'active' : ''; ?>" href="sistema_cliente.php">
                            <i class="bi bi-house me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'cliente_agendar.php') ? 'active' : ''; ?>" href="cliente_agendar.php">
                            <i class="bi bi-calendar-plus me-1"></i>Agendar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'meus_agendamentos.php') ? 'active' : ''; ?>" href="meus_agendamentos.php">
                            <i class="bi bi-calendar-check me-1"></i>Meus Agendamentos
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-grid me-1"></i>Mais
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="cliente_servicos.php"><i class="bi bi-scissors me-2"></i>Serviços</a></li>
                            <li><a class="dropdown-item" href="cliente_programa_fidelidade.php"><i class="bi bi-star me-2"></i>Programa Fidelidade</a></li>
                            <li><a class="dropdown-item" href="cliente_avaliacoes.php"><i class="bi bi-chat-square-heart me-2"></i>Avaliações</a></li>
                        </ul>
                    </li>
                    <div class="nav-divider d-none d-lg-block"></div>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" 
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
                                <a class="dropdown-item" href="cliente_perfil.php">
                                    <i class="bi bi-person-circle me-2"></i>Meu Perfil
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="../HTML/logout.php">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

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

    /* Estilo para link ativo */
    .nav-link.active {
        color: #0d6efd !important;
        font-weight: 600;
    }

    /* Divider entre menus */
    .nav-divider {
        width: 1px;
        height: 30px;
        background-color: #dee2e6;
        margin: 0 1rem;
    }
    </style>
</body>
</html>
