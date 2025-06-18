<?php 
// Verificar se a sessão já foi iniciada ANTES de chamar session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once ('../HTML/topo.php'); 
?> <!-- Inclui o cabeçalho do site -->

<link rel="stylesheet" href="../CSS/SistemaWeb/style_login_cadastro.css"> <!-- Importa o CSS correto para a página -->

<!-- Adiciona banner igual ao das demais páginas do site -->
<section class="container mt-5">
    <div class="row">
        <div class="col text-center">
            <h2 class="display-4" style="margin-top: 20px; font-family: 'VELISTA', sans-serif;">Crie sua conta</h2>
            <p class="lead">Cadastre-se e aproveite todos os nossos serviços exclusivos!</p>
        </div>
    </div>
</section>

<div class="container mt-5 d-flex justify-content-center">
    <div class="card" style="width: 22rem;">
        <div class="card-body">
            <h5 class="card-title text-center">Cadastro</h5>
            
            <!-- Exibir erros de validação -->
            <?php if(isset($_SESSION['erros_cadastro'])): ?>
            <div class="alert alert-danger mt-3">
                <ul class="mb-0">
                    <?php foreach($_SESSION['erros_cadastro'] as $erro): ?>
                        <li><?php echo htmlspecialchars($erro); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['erros_cadastro']); ?>
            <?php endif; ?>
            
            <!-- Formulário de cadastro que envia dados para validação -->
            <form action="../Validar_Forms/valida_form_cadastrar_usuario.php" method="post" id="formCadastro">
                <!-- Campo tipo_usuario oculto, sempre será "Cliente" para registros via formulário público -->
                <input type="hidden" id="tipo_usuario" name="tipo_usuario" value="Cliente">
                
                <!-- Campo para nome do usuário -->
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome:</label>
                    <input type="text" id="nome" name="nome" class="form-control" maxlength="100" required>
                    <div class="feedback-message text-danger" id="nomeMessage"></div>
                </div>

                <!-- Campo para CPF -->
                <div class="mb-3">
                    <label for="cpf" class="form-label">CPF:</label>
                    <input type="text" id="cpf" name="cpf" class="form-control" placeholder="000.000.000-00" maxlength="14" required>
                    <div class="feedback-message text-danger" id="cpfMessage"></div>
                </div>

                <!-- Campo para email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" maxlength="100" required>
                    <div class="feedback-message text-danger" id="emailMessage"></div>
                </div>

                <!-- Campo para telefone -->
                <div class="mb-3">
                    <label for="telefone" class="form-label">Telefone:</label>
                    <input type="tel" id="telefone" name="telefone" class="form-control" placeholder="(00) 00000-0000" maxlength="15" required>
                    <div class="feedback-message text-danger" id="telefoneMessage"></div>
                </div>

                <!-- Campo para data de nascimento -->
                <div class="mb-3">
                    <label for="data_nascimento" class="form-label">Data de Nascimento:</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" class="form-control" required>
                    <div class="feedback-message text-danger" id="dataMessage"></div>
                </div>

                <!-- Campo para senha -->
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha:</label>
                    <input type="password" id="senha" name="senha" class="form-control" maxlength="50" required>
                    <small class="text-muted">Mínimo 6 caracteres</small>
                    <div class="feedback-message text-danger" id="senhaMessage"></div>
                </div>

                <!-- Campo para confirmar senha -->
                <div class="mb-3">
                    <label for="confirmar_senha" class="form-label">Confirmar Senha:</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" maxlength="50" required>
                    <div class="feedback-message text-danger" id="confirmarSenhaMessage"></div>
                </div>

                <!-- Botão para enviar o formulário -->
                <div class="d-grid mt-4">
                    <button type="submit" id="btnCadastrar" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
            
            <!-- Link para página de login -->
            <div class="mt-3 text-center">
                <p>Já tem uma conta? <a href="../HTML/login.php">Faça Login</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Adiciona espaço entre o formulário e o footer -->
<div class="mb-5 pb-5"></div>

<!-- Adicionar SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Obtém referências para os elementos do formulário
    const cpfInput = document.getElementById('cpf');
    const cpfMessage = document.getElementById('cpfMessage');
    const emailInput = document.getElementById('email');
    const telefoneInput = document.getElementById('telefone');
    const dataNascimentoInput = document.getElementById('data_nascimento');
    const senhaInput = document.getElementById('senha');
    const confirmarSenhaInput = document.getElementById('confirmar_senha');
    const formCadastro = document.getElementById('formCadastro');
    const btnCadastrar = document.getElementById('btnCadastrar');

    // Estados do CPF
    let cpfValidando = false;
    let cpfValido = false;
    let cpfDisponivel = false;

    // Interceptar o envio do formulário
    if (formCadastro) {
        formCadastro.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Verificar se todas as validações passaram
            if (!validarFormulario()) {
                Swal.fire({
                    title: 'Erro!',
                    text: 'Por favor, corrija os erros no formulário antes de continuar.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
                return;
            }
            
            // Verificar se CPF ainda está sendo validado
            if (cpfValidando) {
                Swal.fire({
                    title: 'Aguarde!',
                    text: 'Ainda estamos verificando o CPF...',
                    icon: 'info',
                    confirmButtonColor: '#17a2b8'
                });
                return;
            }
            
            // Desabilitar botão durante envio
            btnCadastrar.disabled = true;
            btnCadastrar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cadastrando...';
            
            const formData = new FormData(this);
            
            fetch('../Validar_Forms/valida_form_cadastrar_usuario.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta do servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'sucesso') {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: data.mensagem,
                        icon: 'success',
                        confirmButtonColor: '#28a745',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        if (data.redirect) {
                            console.log('Redirecionando para:', data.redirect);
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
                    text: 'Erro ao processar cadastro. Tente novamente.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            })
            .finally(() => {
                // Reabilitar botão
                btnCadastrar.disabled = false;
                btnCadastrar.innerHTML = 'Cadastrar';
            });
        });
    }

    // Função para validar todo o formulário
    function validarFormulario() {
        let valido = true;
        
        // Validar todos os campos
        valido &= validarNome();
        valido &= (cpfValido && cpfDisponivel);
        valido &= validarEmail();
        valido &= validarTelefone();
        valido &= validarDataNascimento();
        valido &= validarSenha();
        valido &= validarConfirmacaoSenha();
        
        return valido;
    }

    // Função para validar CPF usando algoritmo de validação
    function validarCPFAlgoritmo(cpf) {
        cpf = cpf.replace(/\D/g, '');
        
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
            return false;
        }

        // Calcula primeiro dígito verificador
        let soma = 0;
        for (let i = 0; i < 9; i++) {
            soma += parseInt(cpf.charAt(i)) * (10 - i);
        }
        let resto = 11 - (soma % 11);
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.charAt(9))) return false;

        // Calcula segundo dígito verificador
        soma = 0;
        for (let i = 0; i < 10; i++) {
            soma += parseInt(cpf.charAt(i)) * (11 - i);
        }
        resto = 11 - (soma % 11);
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.charAt(10))) return false;

        return true;
    }

    // Função para verificar se campo já existe no banco
    function verificarCampoExistente(campo, valor, arquivo) {
        return fetch(`../Validar_Forms/${arquivo}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `${campo}=` + encodeURIComponent(valor)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na resposta do servidor');
            }
            return response.json();
        })
        .catch(error => {
            console.error(`Erro ao verificar ${campo}:`, error);
            return { existe: false, erro: 'Erro de conexão' };
        });
    }

    // Máscaras de entrada
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            // Aplicar máscara
            if (value.length > 9) {
                value = value.replace(/^(\d{3})(\d{3})(\d{3})/, '$1.$2.$3-');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{3})(\d{3})/, '$1.$2.');
            } else if (value.length > 3) {
                value = value.replace(/^(\d{3})/, '$1.');
            }
            
            e.target.value = value;
        });

        // Validação em tempo real do CPF - VERSÃO MINIMALISTA
        let timeoutCPF;
        cpfInput.addEventListener('input', function() {
            clearTimeout(timeoutCPF);
            
            const cpf = this.value.replace(/\D/g, '');
            
            // Resetar estados
            cpfValidando = false;
            cpfValido = false;
            cpfDisponivel = false;
            
            // Limpar estilos anteriores
            cpfInput.classList.remove('is-valid', 'is-invalid');
            cpfMessage.textContent = '';
            
            if (cpf.length === 0) {
                return;
            }
            
            if (cpf.length !== 11) {
                cpfMessage.textContent = 'CPF deve ter 11 dígitos';
                cpfInput.classList.add('is-invalid');
                return;
            }
            
            // Validar algoritmo do CPF
            if (!validarCPFAlgoritmo(cpf)) {
                cpfMessage.textContent = 'CPF inválido';
                cpfInput.classList.add('is-invalid');
                return;
            }
            
            // CPF tem formato válido, verificar disponibilidade
            cpfValido = true;
            cpfValidando = true;
            
            // Debounce para não fazer muitas requisições
            timeoutCPF = setTimeout(() => {
                verificarCampoExistente('cpf', cpf, 'verifica_cpf.php')
                .then(data => {
                    cpfValidando = false;
                    
                    if (data.erro) {
                        cpfMessage.textContent = 'Erro ao verificar CPF';
                        cpfInput.classList.add('is-invalid');
                    } else if (data.existe) {
                        cpfMessage.textContent = 'Este CPF já está cadastrado';
                        cpfInput.classList.add('is-invalid');
                        cpfDisponivel = false;
                    } else {
                        cpfMessage.textContent = '';
                        cpfInput.classList.add('is-valid');
                        cpfDisponivel = true;
                    }
                });
            }, 800);
        });
    }

    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 10) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
            } else {
                value = value.replace(/^(\d*)/, '($1');
            }
            
            e.target.value = value;
        });
    }

    // Validações individuais
    function validarNome() {
        const nome = document.getElementById('nome').value.trim();
        const nomeMessage = document.getElementById('nomeMessage');
        
        if (nome.length < 2) {
            nomeMessage.textContent = 'Nome deve ter pelo menos 2 caracteres';
            document.getElementById('nome').classList.add('is-invalid');
            document.getElementById('nome').classList.remove('is-valid');
            return false;
        } else {
            nomeMessage.textContent = '';
            document.getElementById('nome').classList.remove('is-invalid');
            document.getElementById('nome').classList.add('is-valid');
            return true;
        }
    }

    function validarEmail() {
        const email = emailInput.value;
        const emailMessage = document.getElementById('emailMessage');
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

        if (!emailRegex.test(email)) {
            emailMessage.textContent = 'Email inválido';
            emailInput.classList.add('is-invalid');
            emailInput.classList.remove('is-valid');
            return false;
        } else {
            emailMessage.textContent = '';
            emailInput.classList.remove('is-invalid');
            emailInput.classList.add('is-valid');
            return true;
        }
    }

    function validarTelefone() {
        const telefone = telefoneInput.value.replace(/\D/g, '');
        const telefoneMessage = document.getElementById('telefoneMessage');

        if (telefone.length < 10) {
            telefoneMessage.textContent = 'Telefone deve ter pelo menos 10 dígitos';
            telefoneInput.classList.add('is-invalid');
            telefoneInput.classList.remove('is-valid');
            return false;
        } else {
            telefoneMessage.textContent = '';
            telefoneInput.classList.remove('is-invalid');
            telefoneInput.classList.add('is-valid');
            return true;
        }
    }

    function validarDataNascimento() {
        const nascimento = new Date(dataNascimentoInput.value);
        const hoje = new Date();
        const dataMessage = document.getElementById('dataMessage');

        let idade = hoje.getFullYear() - nascimento.getFullYear();
        const m = hoje.getMonth() - nascimento.getMonth();

        if (m < 0 || (m === 0 && hoje.getDate() < nascimento.getDate())) {
            idade--;
        }

        if (idade < 10) {
            dataMessage.textContent = 'Você deve ter pelo menos 10 anos para se cadastrar';
            dataNascimentoInput.classList.add('is-invalid');
            dataNascimentoInput.classList.remove('is-valid');
            return false;
        } else if (idade > 100) {
            dataMessage.textContent = 'Data de nascimento inválida';
            dataNascimentoInput.classList.add('is-invalid');
            dataNascimentoInput.classList.remove('is-valid');
            return false;
        } else {
            dataMessage.textContent = '';
            dataNascimentoInput.classList.remove('is-invalid');
            dataNascimentoInput.classList.add('is-valid');
            return true;
        }
    }

    function validarSenha() {
        const senha = senhaInput.value;
        const senhaMessage = document.getElementById('senhaMessage');

        if (senha.length < 6) {
            senhaMessage.textContent = 'A senha deve ter pelo menos 6 caracteres';
            senhaInput.classList.add('is-invalid');
            senhaInput.classList.remove('is-valid');
            return false;
        } else {
            senhaMessage.textContent = '';
            senhaInput.classList.remove('is-invalid');
            senhaInput.classList.add('is-valid');
            return true;
        }
    }

    function validarConfirmacaoSenha() {
        const senha = senhaInput.value;
        const confirmarSenha = confirmarSenhaInput.value;
        const confirmarSenhaMessage = document.getElementById('confirmarSenhaMessage');

        if (senha !== confirmarSenha) {
            confirmarSenhaMessage.textContent = 'As senhas não coincidem';
            confirmarSenhaInput.classList.add('is-invalid');
            confirmarSenhaInput.classList.remove('is-valid');
            return false;
        } else {
            confirmarSenhaMessage.textContent = '';
            confirmarSenhaInput.classList.remove('is-invalid');
            confirmarSenhaInput.classList.add('is-valid');
            return true;
        }
    }

    // Validação em tempo real
    if (document.getElementById('nome')) {
        document.getElementById('nome').addEventListener('blur', validarNome);
    }

    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            if (validarEmail()) {
                // Verificar se email já existe
                verificarCampoExistente('email', this.value, 'verifica_email.php')
                .then(data => {
                    const emailMessage = document.getElementById('emailMessage');
                    if (data.existe) {
                        emailMessage.textContent = 'Este email já está cadastrado';
                        emailInput.classList.add('is-invalid');
                        emailInput.classList.remove('is-valid');
                    }
                });
            }
        });
    }

    if (telefoneInput) {
        telefoneInput.addEventListener('blur', validarTelefone);
    }

    if (dataNascimentoInput) {
        dataNascimentoInput.addEventListener('change', validarDataNascimento);
    }

    if (senhaInput) {
        senhaInput.addEventListener('input', validarSenha);
    }

    if (confirmarSenhaInput) {
        confirmarSenhaInput.addEventListener('input', validarConfirmacaoSenha);
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<?php include_once('../HTML/footer.php'); ?>