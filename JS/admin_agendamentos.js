document.addEventListener('DOMContentLoaded', function() {
    // Manipular o checkbox "Mostrar todos"
    const mostrarTodosCheckbox = document.getElementById('mostrarTodos');
    const dataInput = document.querySelector('input[name="data"]');
    
    if (mostrarTodosCheckbox) {
        mostrarTodosCheckbox.addEventListener('change', function() {
            dataInput.disabled = this.checked;
        });
    }
    
    // Carregar dados para o modal de edição
    const editButtons = document.querySelectorAll('.btn-editar');
    if (editButtons.length > 0) {
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const data = this.getAttribute('data-data');
                const hora = this.getAttribute('data-hora');
                const profissional = this.getAttribute('data-profissional');
                const status = this.getAttribute('data-status');
                
                // Preencher o formulário do modal
                document.getElementById('id_agendamento_editar').value = id;
                document.getElementById('data_agendamento').value = data;
                document.getElementById('hora_agendamento').value = hora;
                
                // Selecionar o profissional
                const profissionalSelect = document.getElementById('id_profissional');
                for (let i = 0; i < profissionalSelect.options.length; i++) {
                    if (profissionalSelect.options[i].value === profissional) {
                        profissionalSelect.selectedIndex = i;
                        break;
                    }
                }
                
                // Selecionar o status
                const statusSelect = document.getElementById('status');
                for (let i = 0; i < statusSelect.options.length; i++) {
                    if (statusSelect.options[i].value === status) {
                        statusSelect.selectedIndex = i;
                        break;
                    }
                }
            });
        });
    }
    
    // Configurar modal de cancelamento
    const cancelButtons = document.querySelectorAll('.btn-cancelar');
    if (cancelButtons.length > 0) {
        cancelButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const cliente = this.getAttribute('data-cliente');
                const servico = this.getAttribute('data-servico');
                
                document.getElementById('id_agendamento_cancelar').value = id;
                document.getElementById('cliente_cancelar').textContent = cliente;
                document.getElementById('servico_cancelar').textContent = servico;
            });
        });
    }
    
    // Fechar alertas automaticamente após 5 segundos
    const alertas = document.querySelectorAll('.alert:not(.alert-info)');
    if (alertas.length > 0) {
        alertas.forEach(alerta => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alerta);
                bsAlert.close();
            }, 5000);
        });
    }
    
    // Validação de formulário de edição
    const formEditarAgendamento = document.getElementById('formEditarAgendamento');
    if (formEditarAgendamento) {
        formEditarAgendamento.addEventListener('submit', function(event) {
            const data = document.getElementById('data_agendamento').value;
            const hora = document.getElementById('hora_agendamento').value;
            
            if (!data || !hora) {
                event.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios.');
            }
        });
    }
});
