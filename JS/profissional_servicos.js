document.addEventListener('DOMContentLoaded', function() {
    // Evento para os botões de registrar serviço nos cards
    const btnRegistrarServicos = document.querySelectorAll('.btn-registrar-servico');
    btnRegistrarServicos.forEach(btn => {
        btn.addEventListener('click', function() {
            const servicoId = this.getAttribute('data-id');
            const servicoNome = this.getAttribute('data-nome');
            
            // Abrir modal
            const modal = new bootstrap.Modal(document.getElementById('modalManual'));
            modal.show();
            
            // Preencher automaticamente o serviço selecionado
            document.getElementById('servico').value = servicoId;
        });
    });
});
