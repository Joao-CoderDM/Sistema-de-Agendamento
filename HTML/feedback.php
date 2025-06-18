<?php include_once('topo.php'); ?>

    <link rel="stylesheet" href="../CSS/SistemaWeb/style_fale_conosco.css">
    <link rel="stylesheet" href="../CSS/SistemaWeb/style_avaliacoes_cards.css">
    <!-- Adicionar SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Banner melhorado para a página de feedback -->
    <div class="feedback-hero-section">
        <div class="container mt-5">
            <div class="row">
                <div class="col-lg-8 offset-lg-2 text-center">
                    <h1 class="display-4 fw-bold">Envie seu Feedback</h1>
                    <div class="title-underline mx-auto mb-4"></div>
                    <p class="lead">Sua opinião é fundamental para aperfeiçoarmos nossos serviços e garantir sua melhor experiência!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Exibir mensagens de erro/sucesso -->
    <?php if (isset($_SESSION['erro_feedback'])): ?>
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo $_SESSION['erro_feedback']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php unset($_SESSION['erro_feedback']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['sucesso_feedback'])): ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo $_SESSION['sucesso_feedback']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php unset($_SESSION['sucesso_feedback']); ?>
    <?php endif; ?>

    <div class="container mt-5">
        <!-- Formulário de Feedback com visual aprimorado -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="feedback-card">
                    <form id="feedbackForm" action="../Validar_Forms/valida_feedback.php" method="post">
                        <div class="mb-4">
                            <label for="nome" class="form-label"><i class="bi bi-person-circle me-2"></i>Nome:</label>
                            <input type="text" id="nome" name="nome" class="form-control" placeholder="Digite seu nome" 
                                   value="<?php echo $_SESSION['form_data']['nome'] ?? ''; ?>" required>
                        </div>
                        <div class="mb-4">
                            <label for="email" class="form-label"><i class="bi bi-envelope me-2"></i>Email:</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="Digite seu email" 
                                   value="<?php echo $_SESSION['form_data']['email'] ?? ''; ?>" required>
                        </div>
                        <div class="mb-4">
                            <label for="avaliacao" class="form-label"><i class="bi bi-star me-2"></i>Como você avalia nossos serviços:</label>
                            <div class="star-rating">
                                <div class="rating-group text-start">
                                    <input type="radio" id="estrela-5" name="avaliacao" value="5" class="rating-input" 
                                           <?php echo (!isset($_SESSION['form_data']['avaliacao']) || $_SESSION['form_data']['avaliacao'] == 5) ? 'checked' : ''; ?> />
                                    <label for="estrela-5" class="rating-label"><i class="bi bi-star-fill"></i></label>
                                    
                                    <input type="radio" id="estrela-4" name="avaliacao" value="4" class="rating-input" 
                                           <?php echo (isset($_SESSION['form_data']['avaliacao']) && $_SESSION['form_data']['avaliacao'] == 4) ? 'checked' : ''; ?> />
                                    <label for="estrela-4" class="rating-label"><i class="bi bi-star-fill"></i></label>
                                    
                                    <input type="radio" id="estrela-3" name="avaliacao" value="3" class="rating-input" 
                                           <?php echo (isset($_SESSION['form_data']['avaliacao']) && $_SESSION['form_data']['avaliacao'] == 3) ? 'checked' : ''; ?> />
                                    <label for="estrela-3" class="rating-label"><i class="bi bi-star-fill"></i></label>
                                    
                                    <input type="radio" id="estrela-2" name="avaliacao" value="2" class="rating-input" 
                                           <?php echo (isset($_SESSION['form_data']['avaliacao']) && $_SESSION['form_data']['avaliacao'] == 2) ? 'checked' : ''; ?> />
                                    <label for="estrela-2" class="rating-label"><i class="bi bi-star-fill"></i></label>
                                    
                                    <input type="radio" id="estrela-1" name="avaliacao" value="1" class="rating-input" 
                                           <?php echo (isset($_SESSION['form_data']['avaliacao']) && $_SESSION['form_data']['avaliacao'] == 1) ? 'checked' : ''; ?> />
                                    <label for="estrela-1" class="rating-label"><i class="bi bi-star-fill"></i></label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="mensagem" class="form-label">
                                <i class="bi bi-chat-left-text me-2"></i>Sua mensagem:
                                <small class="text-muted">*Mínimo 10 caracteres</small>
                            </label>
                            <textarea id="mensagem" name="mensagem" class="form-control" rows="5" 
                                      minlength="10" maxlength="280" 
                                      placeholder="Compartilhe sua experiência conosco... (mínimo 10 caracteres)" 
                                      required><?php echo $_SESSION['form_data']['mensagem'] ?? ''; ?></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted contador-caracteres">0/10 caracteres mínimos</small>
                                <small class="text-muted caracteres-restantes">280 caracteres restantes</small>
                            </div>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-feedback-submit">
                                <i class="bi bi-send me-2"></i>Enviar Feedback
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Adicionando espaço antes do footer -->
    <div class="pre-footer-space"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Adicionar o script principal de feedback -->
    <script src="../JS/feedback.js"></script>
<?php 
// Limpar dados do formulário após exibição
unset($_SESSION['form_data']); 
include_once('footer.php'); 
?>