// Função genérica para mostrar alertas
function mostrarAlerta(titulo, texto, tipo, redirecionamento = null) {
    Swal.fire({
        title: titulo,
        text: texto,
        icon: tipo,
        confirmButtonColor: tipo === 'success' ? '#28a745' : '#dc3545'
    }).then(() => {
        if (redirecionamento) {
            window.location.href = redirecionamento;
        }
    });
}

// Login
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
            .then(response => response.json())
            .then(data => {
                if(data.status === 'sucesso') {
                    mostrarAlerta('Sucesso!', data.mensagem, 'success', data.redirect);
                } else {
                    mostrarAlerta('Erro!', data.mensagem, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarAlerta('Erro!', 'Ocorreu um erro ao fazer login.', 'error');
            });
        });
    }

    // Remover ou comentar esta parte que pode conflitar
    /*
    const formFeedback = document.querySelector('#feedbackForm');
    if (formFeedback) {
        formFeedback.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Verificar se o script específico de feedback está carregado
            if (typeof window.feedbackFormHandler !== 'undefined') {
                window.feedbackFormHandler(e);
            } else {
                console.log('Formulário de feedback será processado pelo script específico');
            }
        });
    }
    */
});
