/**
 * Script para funcionalidades da página de perfil do administrador
 */

document.addEventListener('DOMContentLoaded', function() {
    // Configuração do preview da imagem de perfil
    configureImagePreview();
    
    // Configuração da validação de formulário
    configureFormValidation();
});

/**
 * Configura a previsualização da imagem quando uma nova foto for selecionada
 */
function configureImagePreview() {
    const inputFotoPerfil = document.getElementById('profile-pic-input');
    
    if (inputFotoPerfil) {
        inputFotoPerfil.addEventListener('change', function(e) {
            const previewContainer = document.querySelector('.profile-pic-preview');
            const reader = new FileReader();
            
            reader.onload = function(e) {
                // Remover o conteúdo atual
                while (previewContainer.firstChild) {
                    previewContainer.removeChild(previewContainer.firstChild);
                }
                
                // Criar a nova imagem de preview
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Preview';
                img.className = 'img-thumbnail rounded-circle';
                img.style.width = '100px';
                img.style.height = '100px';
                img.style.objectFit = 'cover';
                
                previewContainer.appendChild(img);
            }
            
            // Ler o arquivo selecionado como URL de dados
            if (this.files && this.files[0]) {
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
}

/**
 * Configura validações de formulário
 */
function configureFormValidation() {
    // Formulário de atualização de perfil
    const formPerfil = document.getElementById('edit-profile').querySelector('form');
    if (formPerfil) {
        formPerfil.addEventListener('submit', function(e) {
            const nome = document.getElementById('nome').value.trim();
            if (nome.length < 3) {
                e.preventDefault();
                alert('Por favor, insira um nome válido (mínimo de 3 caracteres).');
                return false;
            }
        });
    }
    
    // Formulário de alteração de senha
    const formSenha = document.getElementById('security').querySelector('form');
    if (formSenha) {
        formSenha.addEventListener('submit', function(e) {
            const senhaAtual = document.getElementById('senha_atual').value;
            const novaSenha = document.getElementById('nova_senha').value;
            const confirmarSenha = document.getElementById('confirmar_senha').value;
            
            if (senhaAtual.length < 1) {
                e.preventDefault();
                alert('Por favor, insira sua senha atual.');
                return false;
            }
            
            if (novaSenha.length < 8) {
                e.preventDefault();
                alert('A nova senha deve ter pelo menos 8 caracteres.');
                return false;
            }
            
            if (novaSenha !== confirmarSenha) {
                e.preventDefault();
                alert('A nova senha e a confirmação não coincidem.');
                return false;
            }
        });
    }
}

/**
 * Função para formatação de telefone
 */
function formatarTelefone() {
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
}

// Inicializar formatação de telefone quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', formatarTelefone);
