<?php { ?>
    <link rel="stylesheet" href="../CSS/SistemaWeb/style_content.css">
    
    <!-- Banner principal melhorado com overlay e animação -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="image-wrapper">
                        <img src="../Imagens/banner1.jpg" alt="Banner" class="img-fluid rounded img-banner animate-in">
                        <div class="image-overlay"></div>
                    </div>
                </div>
                <div class="col-md-6 text-center animate-text">
                    <h1 class="display-4 fw-bold mb-4">Estilo e Precisão na Barbearia Mosanberk</h1>
                    <p class="lead mb-3">Estilo que fala por você – Corte, barba e atitude na medida certa!</p>
                    <p class="mb-4">Agende seu horário e garanta um atendimento exclusivo com qualidade e estilo!</p>
                    <a href="cadastrar_usuario.php" class="btn btn-lg btn-agendar pulse-animation">AGENDE AGORA <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Divisor estilizado (removido espaçamento extra) -->
    <div class="container">
        <div class="divider">
            <span><i class="bi bi-scissors"></i></span>
        </div>
    </div>
    
    <!-- Cards de serviços com estilos melhorados -->
    <div class="container section-spacing">
        <div class="section-title text-center mb-5">
            <h2 class="fw-bold">NOSSOS DIFERENCIAIS</h2>
            <div class="title-underline"></div>
        </div>
        
        <div class="row row-cols-1 row-cols-md-4 g-4">
            <div class="col">
                <div class="card service-card h-100">
                    <div class="card-icon">
                        <i class="bi bi-scissors"></i>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Serviços</h5>
                        <p class="card-text">Conheça nossa variedade de serviços premium! Desde cortes tradicionais a tratamentos especiais, temos tudo para realçar seu estilo e personalidade.</p>
                        <a href="servicos.php" class="btn btn-agendar-special mt-3">Ver Serviços</a>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card service-card h-100">
                    <div class="card-icon">
                        <i class="bi bi-card-checklist"></i>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Cartão Fidelidade</h5>
                        <p class="card-text">Na Mosanberk, sua fidelidade vale benefícios! A cada corte, você acumula pontos e pode ganhar descontos e serviços especiais. Quanto mais você vem, mais vantagens tem!</p>
                        <a href="cadastrar_usuario.php" class="btn btn-agendar-special mt-3">Cadastre-se já</a>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card service-card h-100">
                    <div class="card-icon">
                        <i class="bi bi-check2-square"></i>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Agende Agora</h5>
                        <p class="card-text">Garanta seu horário agora mesmo e evite esperas! Clique abaixo e agende seu corte ou barba com praticidade.</p>
                        <a href="cadastrar_usuario.php" class="btn btn-agendar-special mt-3">Agendar!</a>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card service-card h-100">
                    <div class="card-icon">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Saiba mais</h5>
                        <p class="card-text">Quer conhecer um pouco da nossa história? Descubra como podemos transformar seu visual com qualidade e estilo!</p>
                        <a href="sobre_nos.php" class="btn btn-agendar-special mt-3">Saiba Mais!</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Divisor estilizado -->
    <div class="container mt-7">
        <div class="divider">
            <span><i class="bi bi-chat-quote"></i></span>
        </div>
    </div>
    
    <!-- Seção de Avaliações redesenhada -->
    <div class="testimonials-section py-5">
        <div class="container text-center">
            <h2 class="fw-bold mb-2">O QUE NOSSOS CLIENTES DIZEM</h2>
            <div class="title-underline mb-4 mx-auto"></div>
            <p class="lead mb-5">Avaliações 5 estrelas de clientes satisfeitos com nossos serviços</p>
            
            <?php
            // Incluir o arquivo de consulta
            include_once('../Consultas/consultar_avaliacoes.php');
            
            // Obter avaliações de 5 estrelas
            $avaliacoes = obterAvaliacoesCincoEstrelas(6);
            
            // Verificar se existem avaliações
            if (is_array($avaliacoes) && count($avaliacoes) > 0) {
                // Criar o carrossel que passa automaticamente (data-bs-interval="5000" = 5 segundos)
                echo '<div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">';
                echo '<div class="carousel-inner">';
                
                // Dividir as avaliações em grupos de 3 (para cada slide)
                $totalSlides = ceil(count($avaliacoes) / 3);
                
                for ($i = 0; $i < $totalSlides; $i++) {
                    // Determinar a classe active para o primeiro slide
                    $activeClass = ($i === 0) ? 'active' : '';
                    
                    echo '<div class="carousel-item ' . $activeClass . '">';
                    echo '<div class="row">';
                    
                    // Exibir até 3 avaliações por slide
                    for ($j = $i * 3; $j < min(($i * 3) + 3, count($avaliacoes)); $j++) {
                        $avaliacao = $avaliacoes[$j];
                        // Limitando a exibição a 280 caracteres
                        $nome = $avaliacao['nome']; 
                        $mensagem = $avaliacao['mensagem'];
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="card p-4 mx-0 feedback-card equal-height-card">
                                <div class="quote-icon mb-2">
                                    <i class="bi bi-quote fs-1"></i>
                                </div>
                                <div class="mb-2 stars">
                                    <?php for ($star = 1; $star <= 5; $star++): ?>
                                        <i class="bi bi-star-fill text-warning"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="feedback-content">
                                    <p class="card-text feedback-text"><?php echo nl2br(htmlspecialchars($mensagem)); ?></p>
                                </div>
                                <div class="testimonial-author mt-auto">
                                    <div class="avatar">
                                        <?php echo strtoupper(substr($nome, 0, 1)); ?>
                                    </div>
                                    <h5 class="mb-0 ms-2"><?php echo htmlspecialchars($nome); ?></h5>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    
                    echo '</div>'; // Fecha row
                    echo '</div>'; // Fecha carousel-item
                }
                
                echo '</div>'; // Fecha carousel-inner
                
                // Controles do carrossel (independente do número de slides)
                if ($totalSlides > 1) {
                    ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Próximo</span>
                    </button>
                    
                    <!-- Indicadores estilizados -->
                    <div class="carousel-indicators">
                        <?php for ($i = 0; $i < $totalSlides; $i++) { ?>
                            <button type="button" 
                                    data-bs-target="#testimonialCarousel" 
                                    data-bs-slide-to="<?php echo $i; ?>" 
                                    <?php echo ($i === 0) ? 'class="active" aria-current="true"' : ''; ?> 
                                    aria-label="Slide <?php echo $i + 1; ?>"></button>
                        <?php } ?>
                    </div>
                    <?php
                }
                echo '</div>'; // Fecha testimonialCarousel
            } else {
                ?>
                <div class="no-reviews">
                    <div class="empty-reviews-icon mb-4">
                        <i class="bi bi-star"></i>
                    </div>
                    <h3>Seja o primeiro a nos avaliar!</h3>
                    <p class="text-muted">Ainda não temos avaliações de 5 estrelas.</p>
                    <a href="../HTML/feedback.php" class="btn btn-lg btn-agendar mt-3">Avaliar Agora</a>
                </div>
                <?php
            }
            ?>
            
            <!-- Link para avaliações com design aprimorado -->
            <div class="rate-us-section mt-5">
                <a href="../HTML/feedback.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-star me-2"></i> Deixe sua avaliação
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Script personalizado -->
    <script src="../JS/content.js"></script>
<?php } ?>