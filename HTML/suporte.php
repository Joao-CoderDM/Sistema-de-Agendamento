<?php include_once('topo.php'); ?>

<?php { ?>
    <link rel="stylesheet" href="../CSS/SistemaWeb/style_suporte.css">
    
    <!-- Banner melhorado para página de suporte -->
    <div class="support-hero-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2 text-center">
                    <h1 class="display-4 fw-bold">Suporte ao Cliente</h1>
                    <div class="title-underline mx-auto mb-4"></div>
                    <p class="lead mb-4">Estamos aqui para ajudar. Entre em contato conosco pelos canais abaixo.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <!-- NOVA SEÇÃO: Cards de contato com estilo hexagonal -->
        <div class="contact-hexgrid">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="hex-container">
                                <div class="hex-card">
                                    <div class="hex-content phone-support">
                                        <div class="hex-icon">
                                            <i class="bi bi-telephone-fill"></i>
                                        </div>
                                        <h3>Central de Atendimento</h3>
                                        <ul class="hex-info">
                                            <li><a href="tel:+554833075337" style="color: #FFD700;">(48) 3307-5337</a></li>
                                            <li><a href="tel:+5548991210919" style="color: #FFD700;">(48) 99121-0919</a></li>
                                            <li class="schedule">Seg - Sex: 8h às 20h</li>
                                            <li class="schedule">Sáb: 9h às 18h</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="hex-container">
                                <div class="hex-card">
                                    <div class="hex-content email-support">
                                        <div class="hex-icon">
                                            <i class="bi bi-envelope-fill"></i>
                                        </div>
                                        <h3>Suporte por E-mail</h3>
                                        <ul class="hex-info">
                                            <li><a href="mailto:contato@mosanberk.com" style="color: #FFD700;">contato@mosanberk.com</a></li>
                                            <li><a href="mailto:suporte@mosanberk.com" style="color: #FFD700;">suporte@mosanberk.com</a></li>
                                            <li class="response-time">Tempo médio de resposta: 24h</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="hex-container">
                                <div class="hex-card">
                                    <div class="hex-content social-support">
                                        <div class="hex-icon">
                                            <i class="bi bi-chat-square-text-fill"></i>
                                        </div>
                                        <h3>Redes Sociais</h3>
                                        <div class="social-links">
                                            <a href="https://www.instagram.com" class="social-btn instagram" target="_blank" style="color: #FFD700;">
                                                <i class="bi bi-instagram"></i> @mosanberk
                                            </a>
                                            <a href="https://www.facebook.com" class="social-btn facebook" target="_blank" style="color: #FFD700;">
                                                <i class="bi bi-facebook"></i> Barbearia Mosanberk
                                            </a>
                                        </div>
                                        <p class="social-note">Resposta em até 6 horas nos dias úteis</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Seção de perguntas frequentes -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="faq-section">
                    <h2 class="text-center mb-4">Perguntas Frequentes</h2>
                    <p class="text-center mb-5">Encontre respostas para as perguntas mais comuns sobre nossos serviços e sistema de agendamento</p>
                    
                    <div class="accordion" id="faqAccordion">
                        <!-- FAQ Item 1 -->
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    Como posso agendar um horário?
                                </button>
                            </h3>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Você pode agendar um horário diretamente pelo nosso site, criando uma conta e selecionando o serviço desejado, ou entrando em contato por telefone. Recomendamos o agendamento com pelo menos 24 horas de antecedência para garantir disponibilidade.
                                </div>
                            </div>
                        </div>
                        
                        <!-- FAQ Item 2 -->
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Posso cancelar ou reagendar meu horário?
                                </button>
                            </h3>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Sim, você pode cancelar ou reagendar seu horário através do seu painel de cliente na seção "Meus Agendamentos". Pedimos apenas que faça isso com pelo menos 4 horas de antecedência para que possamos disponibilizar o horário para outros clientes. Cancelamentos com menos de 4 horas de antecedência podem estar sujeitos à nossa política de cancelamento.
                                </div>
                            </div>
                        </div>
                        
                        <!-- FAQ Item 3 -->
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Como funciona o sistema de fidelidade?
                                </button>
                            </h3>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    A cada serviço realizado, você acumula pontos com base no valor gasto. Esses pontos podem ser trocados por descontos em serviços futuros ou produtos exclusivos. Verifique seu saldo de pontos na seção "Programa de Fidelidade" do seu painel de cliente.
                                </div>
                            </div>
                        </div>
                        
                        <!-- FAQ Item 4 -->
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    O que acontece se eu me atrasar para o meu horário?
                                </button>
                            </h3>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Pedimos que chegue com pelo menos 5 minutos de antecedência. Em caso de atraso, faremos o possível para acomodá-lo, mas se o atraso for superior a 15 minutos, pode ser necessário reagendar para não prejudicar outros clientes.
                                </div>
                            </div>
                        </div>
                        
                        <!-- FAQ Item 5 -->
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="headingFive">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    Como posso avaliar o serviço recebido?
                                </button>
                            </h3>
                            <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Após a conclusão do serviço, você receberá a opção de avaliá-lo em seu painel de cliente, na seção "Meus Agendamentos". Suas avaliações são muito importantes para mantermos a qualidade e melhorarmos constantemente nossos serviços.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Seção de Localização com estilo harmonioso -->
                <div class="location-section-harmonious mt-5">
                    <div class="location-header text-center mb-4">
                        <h2 class="section-title-white">Nossa Localização</h2>
                        <p class="section-subtitle">Venha nos visitar e conheça nosso espaço</p>
                    </div>
                    
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <div class="row">
                                <!-- Card de Endereço -->
                                <div class="col-md-6 mb-4">
                                    <div class="hex-container">
                                        <div class="hex-card location-card">
                                            <div class="hex-content location-content">
                                                <div class="hex-icon">
                                                    <i class="bi bi-geo-alt-fill"></i>
                                                </div>
                                                <h3>Endereço</h3>
                                                <div class="location-info-list">
                                                    <div class="info-item">
                                                        <i class="bi bi-building"></i>
                                                        <div>
                                                            <strong>Unidade Principal</strong>
                                                            <p>R. Lauro Linhares, 1015 - Trindade<br>
                                                            Florianópolis - SC<br>
                                                            <span class="text-warning">CEP: 88036-002</span></p>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="info-item">
                                                        <i class="bi bi-clock"></i>
                                                        <div>
                                                            <strong>Funcionamento</strong>
                                                            <p>Segunda a Sexta: 8h às 20h<br>
                                                            Sábado: 9h às 18h<br>
                                                            <span class="text-danger">Domingo: Fechado</span></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="location-actions">
                                                    <button class="btn-hex btn-primary-hex" onclick="window.open('https://maps.google.com/?q=R.+Lauro+Linhares,+1015+-+Trindade,+Florianópolis+-+SC', '_blank')">
                                                        <i class="bi bi-geo-alt"></i>
                                                        Como Chegar
                                                    </button>
                                                    <button class="btn-hex btn-primary-hex" onclick="window.open('tel:+554833075337')">
                                                        <i class="bi bi-telephone"></i>
                                                        Ligar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Card do Mapa -->
                                <div class="col-md-6 mb-4">
                                    <div class="hex-container">
                                        <div class="hex-card map-card">
                                            <div class="hex-content map-content">
                                                <div class="hex-icon">
                                                    <i class="bi bi-map"></i>
                                                </div>
                                                <h3>Localização no Mapa</h3>
                                                <div class="map-wrapper">
                                                    <iframe 
                                                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3536.5234567890123!2d-48.5234567!3d-27.5987654!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sR.%20Lauro%20Linhares%2C%201015%20-%20Trindade%2C%20Florian%C3%B3polis%20-%20SC!5e0!3m2!1spt-BR!2sbr!4v1234567890123!5m2!1spt-BR!2sbr" 
                                                        width="100%" 
                                                        height="220" 
                                                        style="border:0; border-radius: 10px;" 
                                                        allowfullscreen="" 
                                                        loading="lazy" 
                                                        referrerpolicy="no-referrer-when-downgrade">
                                                    </iframe>
                                                </div>
                                                <p class="map-note">Clique no mapa para abrir no Google Maps</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<?php } ?>

<?php include_once('footer.php'); ?>