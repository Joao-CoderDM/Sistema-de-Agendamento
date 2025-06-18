document.addEventListener('DOMContentLoaded', function() {
    // Máscara para CPF
    const cpfInputs = document.querySelectorAll('#cpf, #edit_cpf');
    cpfInputs.forEach(input => {
        input.addEventListener('input', function(e) {
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
    });
    
    // Máscara para telefone
    const telefoneInputs = document.querySelectorAll('#telefone, #edit_telefone');
    telefoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
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
    });
    
    // Configurar o modal de edição
    const editModal = document.getElementById('editProfessionalModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            document.getElementById('edit_id_usuario').value = button.getAttribute('data-id');
            document.getElementById('edit_nome').value = button.getAttribute('data-nome');
            document.getElementById('edit_email').value = button.getAttribute('data-email');
            document.getElementById('edit_telefone').value = button.getAttribute('data-telefone');
            
            // Processar múltiplas especialidades
            const especialidades = button.getAttribute('data-especialidade').split(', ');
            const selectElement = document.getElementById('edit_especialidades');
            
            // Limpar seleções anteriores
            for(let i = 0; i < selectElement.options.length; i++) {
                selectElement.options[i].selected = false;
            }
            
            // Selecionar as especialidades do profissional
            for(let i = 0; i < selectElement.options.length; i++) {
                if(especialidades.includes(selectElement.options[i].value)) {
                    selectElement.options[i].selected = true;
                }
            }
            
            // Verifica se o profissional está ativo
            const status = button.closest('tr').querySelector('.badge').textContent.trim();
            document.getElementById('edit_disponivel').checked = (status === 'Ativo');
        });
    }
    
    // Configurar o modal de exclusão
    const deleteModal = document.getElementById('deleteProfessionalModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            document.getElementById('delete_id_usuario').value = button.getAttribute('data-id');
            document.getElementById('delete_nome_profissional').textContent = button.getAttribute('data-nome');
        });
    }
});
