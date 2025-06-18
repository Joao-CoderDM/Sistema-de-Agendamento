document.addEventListener('DOMContentLoaded', function() {
    // Preview da imagem
    const inputFoto = document.getElementById('nova_foto');
    const previewImg = document.getElementById('preview-img');
    
    if (inputFoto) {
        inputFoto.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.innerHTML = '';
                    previewImg.style.backgroundImage = `url(${e.target.result})`;
                    previewImg.style.backgroundSize = 'cover';
                    previewImg.style.backgroundPosition = 'center';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Máscaras para CPF e telefone
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 9) {
                value = value.replace(/^(\d{3})(\d{3})(\d{3})/, '$1.$2.$3-');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{3})(\d{3})/, '$1.$2.');
            } else if (value.length > 3) {
                value = value.replace(/^(\d{3})/, '$1.');
            }
            
            e.target.value = value;
        });
    }
    
    const telefoneInput = document.getElementById('telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 10) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{4})/, '($1) $2-');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})/, '($1) ');
            }
            
            e.target.value = value;
        });
    }
    
    // Validação de formulário
    const formDadosPessoais = document.getElementById('formDadosPessoais');
    if (formDadosPessoais) {
        formDadosPessoais.addEventListener('submit', function(e) {
            // Aqui você pode adicionar validações antes de enviar o formulário
            console.log('Formulário enviado');
        });
    }
});
