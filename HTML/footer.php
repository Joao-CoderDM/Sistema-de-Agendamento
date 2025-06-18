<?php { ?>
  <link rel="stylesheet" href="../CSS/SistemaWeb/style_footer.css">
    <footer class="py-4">
        <div class="container">
          <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
              <h5>Barbearia Mosanberk</h5>
              <p>Estilo e precisão em cada corte.</p>
              <p>Desde 2020 proporcionando o melhor para nossos clientes.</p>
              <div class="social-icons">
                <a href="https://www.instagram.com" target="_blank"><i class="bi bi-instagram"></i></a>
                <a href="https://www.facebook.com" target="_blank"><i class="bi bi-facebook"></i></a>
                <a href="https://www.tiktok.com" target="_blank"><i class="bi bi-tiktok"></i></a>
              </div>
            </div>
            
            <div class="col-md-4 mb-4 mb-md-0">
              <h5>Links Úteis</h5>
              <ul class="footer-links">
                <li><a href="servicos.php">Serviços</a></li>
                <li><a href="unidades.php">Nossas Unidades</a></li>
                <li><a href="sobre_nos.php">Sobre Nós</a></li>
                <li><a href="feedback.php">Feedbacks</a></li>
                <li><a href="suporte.php">Suporte</a></li>
              </ul>
            </div>
            
            <div class="col-md-4">
              <h5>Contato</h5>
              <address>
                <p><i class="bi bi-geo-alt-fill"></i> R. Lauro Linhares, 1015 - Trindade, Florianópolis - SC</p>
                <p><i class="bi bi-telephone-fill"></i> (48) 3307-5337</p>
                <p><i class="bi bi-envelope-fill"></i> contato@mosanberk.com</p>
              </address>
            </div>
          </div>
          
          <hr>
          
          <div class="row">
            <div class="col-12 text-center">
              <p class="mb-0">&copy; <?php echo date('Y'); ?> Barbearia Mosanberk - Todos os direitos reservados.</p>
            </div>
          </div>
        </div>
    </footer>

    <!-- Garante que o Bootstrap JS está carregado -->
    <?php if (!defined('BOOTSTRAP_JS_LOADED')): ?>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <?php endif; ?>
</body>
</html>
<?php } ?>