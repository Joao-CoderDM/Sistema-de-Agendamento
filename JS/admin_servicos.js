document.addEventListener('DOMContentLoaded', function() {
    // Configurar o modal de edição
    const editModal = document.getElementById('editServiceModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            document.getElementById('edit_id_servico').value = button.getAttribute('data-id');
            document.getElementById('edit_nome').value = button.getAttribute('data-nome');
            document.getElementById('edit_descricao').value = button.getAttribute('data-descricao');
            document.getElementById('edit_valor').value = button.getAttribute('data-valor');
            document.getElementById('edit_duracao').value = button.getAttribute('data-duracao');
            document.getElementById('edit_categoria').value = button.getAttribute('data-categoria');
        });
    }
    
    // Configurar o modal de exclusão
    const deleteModal = document.getElementById('deleteServiceModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            document.getElementById('delete_id_servico').value = button.getAttribute('data-id');
            document.getElementById('delete_nome_servico').textContent = button.getAttribute('data-nome');
        });
    }
});
