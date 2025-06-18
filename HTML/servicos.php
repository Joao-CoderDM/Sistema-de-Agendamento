<?php { ?>
    
<?php include_once('topo.php'); ?>

<link rel="stylesheet" href="../CSS/SistemaWeb/style_servicos.css">


<!-- Banner principal -->
<section class="hero-section container-fluid">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4">Nossos Serviços</h1>
                <p class="lead">Oferecemos uma experiência completa de cuidados masculinos com profissionais qualificados e ambiente exclusivo.</p>
                <a href="cadastrar_usuario.php" class="btn btn-agendar">Agendar Agora</a>
            </div>
            <div class="col-md-6 d-none d-md-block">
                <img src="../Imagens/banner-servico.jpg" class="img-fluid hero-image" alt="Banner Serviços">
            </div>
        </div>
    </div>
</section>

<!-- Serviços principais com imagens -->
<section class="servicos-principais container">
    <div class="row">
        <div class="col-12 text-center">
            <h2 class="section-title">Serviços Principais</h2>
            <div class="section-divider"></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card servico-card h-100">
                <div class="card-badge">Popular</div>
                <img src="../Imagens/corte.jpg" class="card-img-top img-servico" alt="Corte de Cabelo">
                <div class="card-body">
                    <h5 class="card-title">Corte de Cabelo</h5>
                    <p class="card-text">Cortes modernos ou tradicionais realizados com técnicas precisas para valorizar seu estilo.</p>
                    <div class="card-price">A partir de R$ 45,00</div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="cadastrar_usuario.php" class="btn btn-outline-light btn-sm">Agendar</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card servico-card h-100">
                <img src="../Imagens/barba.jpg" class="card-img-top img-servico" alt="Barba">
                <div class="card-body">
                    <h5 class="card-title">Barba</h5>
                    <p class="card-text">Aparação, modelagem e tratamento para sua barba, incluindo toalha quente e produtos especiais.</p>
                    <div class="card-price">A partir de R$ 35,00</div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="cadastrar_usuario.php" class="btn btn-outline-light btn-sm">Agendar</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card servico-card h-100">
                <div class="card-badge">Novo</div>
                <img src="../Imagens/acabamentos.jpg" class="card-img-top img-servico" alt="Acabamentos">
                <div class="card-body">
                    <h5 class="card-title">Acabamentos</h5>
                    <p class="card-text">Finalização impecável com detalhes perfeitos para destacar seu estilo e personalidade.</p>
                    <div class="card-price">A partir de R$ 25,00</div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="cadastrar_usuario.php" class="btn btn-outline-light btn-sm">Agendar</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Combo promocional -->
<section class="promocao-section container-fluid">
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2 text-center">
                <div class="promo-card">
                    <h2>Combo Completo</h2>
                    <p class="promo-description">Corte + Barba + Tratamento Facial</p>
                    <div class="promo-price">
                        <span class="old-price">R$ 120,00</span>
                        <span class="new-price">R$ 95,00</span>
                    </div>
                    <p class="promo-text">Transforme seu visual com nosso combo mais completo e economize!</p>
                    <a href="cadastrar_usuario.php" class="btn btn-agendar">Aproveitar Promoção</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Serviços adicionais -->
<section class="servicos-adicionais container">
    <div class="row">
        <div class="col-12 text-center">
            <h2 class="section-title">Serviços Adicionais</h2>
            <div class="section-divider"></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card servico-simples h-100">
                <div class="card-body">
                    <div class="servico-icon">
                        <i class="bi bi-trophy"></i>
                    </div>
                    <h5 class="card-title">Tratamento Capilar</h5>
                    <p class="card-text">Revitalize seus cabelos com nossas máscaras hidratantes e terapias restauradoras.</p>
                    <div class="card-price">A partir de R$ 40,00</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card servico-simples h-100">
                <div class="card-body">
                    <div class="servico-icon">
                        <i class="bi bi-shield"></i>
                    </div>
                    <h5 class="card-title">Cuidados com a Pele</h5>
                    <p class="card-text">Mantenha sua pele jovem e bem cuidada com nossos tratamentos de limpeza, hidratação e rejuvenescimento.</p>
                    <div class="card-price">A partir de R$ 45,00</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card servico-simples h-100">
                <div class="card-body">
                    <div class="servico-icon">
                        <i class="bi bi-hand-thumbs-up"></i>
                    </div>
                    <h5 class="card-title">Massagens Relaxantes</h5>
                    <p class="card-text">Relaxe e alivie o estresse com massagens capilares, faciais e terapêuticas exclusivas.</p>
                    <div class="card-price">A partir de R$ 35,00</div>
                </div>
            </div>
        </div>
    </div>
</section>
    
<!-- Serviços premium -->
<section class="servicos-premium container">
    <div class="row">
        <div class="col-12 text-center mb-4">
            <h2 class="section-title">Serviços Premium</h2>
            <div class="section-divider"></div>
            <p class="lead">Experimente o melhor em cuidados masculinos com nossos serviços exclusivos</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card servico-simples h-100">
                <div class="card-body">
                    <div class="servico-icon">
                        <i class="bi bi-star"></i>
                    </div>
                    <h5 class="card-title">Serviços Premium</h5>
                    <p class="card-text">Atendimento diferenciado com experiências exclusivas, desde cortes sofisticados até tratamentos completos.</p>
                    <div class="card-price">A partir de R$ 85,00</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card servico-simples h-100">
                <div class="card-body">
                    <div class="servico-icon">
                        <i class="bi bi-gift"></i>
                    </div>
                    <h5 class="card-title">Pacotes Especiais</h5>
                    <p class="card-text">Ofertas personalizadas para noivos, executivos e clientes que querem um dia de cuidados especiais.</p>
                    <div class="card-price">A partir de R$ 120,00</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card servico-simples h-100">
                <div class="card-body">
                    <div class="servico-icon">
                        <i class="bi bi-gem"></i>
                    </div>
                    <h5 class="card-title">VIP Experience</h5>
                    <p class="card-text">Atendimento exclusivo fora do horário comercial, com bebidas e serviços completos personalizados.</p>
                    <div class="card-price">A partir de R$ 150,00</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA - Call to action -->
<section class="cta-section container-fluid">
    <div class="container">
        <div class="row">
            <div class="col-md-10 offset-md-1 text-center">
                <h2 class="cta-title">Pronto para transformar seu visual?</h2>
                <p class="cta-text">Agende seu horário hoje mesmo e venha descobrir por que somos a barbearia mais bem avaliada da região!</p>
                <a href="cadastrar_usuario.php" class="btn btn-agendar btn-lg mt-3">Agendar Agora</a>
            </div>
        </div>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<?php include_once('footer.php'); ?>

<?php } ?>