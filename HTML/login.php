<?php
// Inicia a sessão antes de qualquer saída HTML
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Inicia a sessão PHP para gerenciar variáveis de sessão do usuário
}

include_once('topo.php'); // Inclui o cabeçalho do site
?>

<link rel="stylesheet" href="../CSS/SistemaWeb/style_login_cadastro.css"> <!-- Importa o CSS específico para a página de login -->
<!-- Adicionar SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Adiciona banner igual ao das demais páginas do site -->
<section class="container mt-5">
    <div class="row">
        <div class="col text-center">
            <h2 class="display-4" style="margin-top: 20px; font-family: 'VELISTA', sans-serif;">Acesse sua conta</h2>
            <p class="lead">Entre agora mesmo e aproveite todos os nossos serviços!</p>
        </div>
    </div>
</section>

<div class="container mt-5 d-flex justify-content-center">
    <div class="card" style="width: 22rem;">
        <div class="card-body">
            <h5 class="card-title text-center">Login</h5>
            <form action="../Validar_Forms/valida_form_login_usuario.php" method="post" id="formLogin">
                <!-- Formulário de login com campos email e senha -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" required> <!-- Campo obrigatório para email -->
                </div>
                
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha:</label>
                    <input type="password" id="senha" name="senha" class="form-control" required> <!-- Campo obrigatório para senha -->
                </div>
                
                <!-- Exibe mensagens de erro caso existam na sessão -->
                <?php if(isset($_SESSION['erro_login'])): ?>
                <div class="alert alert-danger mt-3">
                    <?php echo htmlspecialchars($_SESSION['erro_login']); unset($_SESSION['erro_login']); ?> <!-- Exibe e depois remove o erro da sessão -->
                </div>
                <?php endif; ?>
                
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary">Entrar</button> <!-- Botão para enviar o formulário -->
                </div>
            </form>
            
            <div class="mt-3 text-center">
                <p>Não tem uma conta? <a href="cadastrar_usuario.php">Cadastre-se</a></p> <!-- Link para página de cadastro -->
            </div>
        </div>
    </div>
</div>

<!-- Adiciona espaço entre o formulário e o footer -->
<div class="mb-5 pb-5"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formLogin = document.getElementById('formLogin');
    
    if (formLogin) {
        formLogin.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../Validar_Forms/valida_form_login_usuario.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    // Se não for JSON, ler como texto para debug
                    return response.text().then(text => {
                        console.error('Resposta não é JSON:', text);
                        throw new Error('Servidor retornou resposta inválida');
                    });
                }
            })
            .then(data => {
                if (data.status === 'sucesso') {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: data.mensagem,
                        icon: 'success',
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: data.mensagem,
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    title: 'Erro!',
                    text: 'Erro interno do servidor. Verifique os logs para mais detalhes.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        });
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once('footer.php'); ?> <!-- Inclui o rodapé do site -->