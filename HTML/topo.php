<?php
// Verificar se a sessão já foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir variáveis de usuário com valores padrão
$nome_usuario = 'Visitante';
$tipo_usuario = 'visitante';
$logado = false;

// Verificar se o usuário está logado e definir variáveis
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $logado = true;
    $nome_usuario = isset($_SESSION['nome']) ? $_SESSION['nome'] : 'Usuário';
    $tipo_usuario = isset($_SESSION['tipo_usuario']) ? $_SESSION['tipo_usuario'] : 'Cliente';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barbearia Mosanberk</title>
    <link rel="icon" href="../Imagens/logotipo.png" type="image/png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="../CSS/SistemaWeb/style_topo.css">
    <link rel="stylesheet" href="../CSS/Sitemaweb/style_responsive.css">
    <link rel="stylesheet" href="../CSS/Sitemaweb/style_imagens_responsivas.css">
    <link href="https://fonts.googleapis.com/css2?family=Belinda&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark navbar-expand-lg fixed-top">
        <div class="container">
          <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="../Imagens/logotipo.png" width="40" height="40" class="logotipo me-2" alt="Logotipo"> 
            <span class="brand-text">Barbearia Mosanberk</span>
          </a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
              <li class="nav-item">
                <a class="nav-link" aria-current="page" href="../HTML/index.php">Home</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="servicos.php">Serviços</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="unidades.php">Unidades</a>
              </li>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  Mais
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="sobre_nos.php">Sobre Nós</a></li>
                  <li><a class="dropdown-item" href="feedback.php">Feedback</a></li>
                  <li><a class="dropdown-item" href="suporte.php">Suporte</a></li>
                </ul>
              </li>
              <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo htmlspecialchars($_SESSION['nome'] ?? 'Usuário'); ?>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'adm'): ?>
                      <li><a class="dropdown-item" href="../HTML/sistema_adm.php">Painel Administrativo</a></li>
                    <?php elseif (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'cliente'): ?>
                      <li><a class="dropdown-item" href="../HTML/sistema_cliente.php">Minha Conta</a></li>
                    <?php elseif (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'profissional'): ?>
                      <li><a class="dropdown-item" href="../HTML/sistema_profissional.php">Painel do Profissional</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../HTML/logout.php">Sair</a></li>
                  </ul>
                </li>
              <?php else: ?>
                <li class="nav-item">
                  <a class="nav-link" href="login.php">Login</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="cadastrar_usuario.php">Cadastrar</a>
                </li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
    </nav>
    
    <!-- Bootstrap JS (Bundle com Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>