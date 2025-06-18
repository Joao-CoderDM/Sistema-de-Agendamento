document.addEventListener('DOMContentLoaded', function() {
    // Modal para atualizar status do agendamento
    const statusModal = document.getElementById('statusModal');
    if (statusModal) {
        statusModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            document.getElementById('id_agendamento_status').value = id;
            
            // Limpar seleção anterior e observação
            document.getElementById('novo_status').selectedIndex = 0;
            document.getElementById('observacao').value = '';
        });
    }

    // Modal para cancelar agendamento
    const cancelarModal = document.getElementById('cancelarModal');
    if (cancelarModal) {
        cancelarModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nome = button.getAttribute('data-nome');
            document.getElementById('id_agendamento_cancelar').value = id;
            document.getElementById('cliente_nome').textContent = nome;
            
            // Limpar campo de motivo
            document.getElementById('motivo_cancelamento').value = '';
        });
    }

    // Modal para excluir agendamento
    const excluirModal = document.getElementById('excluirModal');
    if (excluirModal) {
        excluirModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nome = button.getAttribute('data-nome');
            document.getElementById('id_agendamento_excluir').value = id;
            document.getElementById('cliente_nome_excluir').textContent = nome;
        });
    }

    // Botão de confirmar cancelamento
    const confirmarCancelamento = document.getElementById('confirmarCancelamento');
    if (confirmarCancelamento) {
        confirmarCancelamento.addEventListener('click', function() {
            const motivoInput = document.getElementById('motivo_cancelamento');
            if (!motivoInput.value.trim()) {
                alert('Por favor, informe o motivo do cancelamento.');
                return;
            }
            document.getElementById('formCancelar').submit();
        });
    }

    // Botão de confirmar exclusão
    const confirmarExclusao = document.getElementById('confirmarExclusao');
    if (confirmarExclusao) {
        confirmarExclusao.addEventListener('click', function() {
            document.getElementById('formExcluir').submit();
        });
    }

    // Validação do formulário de atualização de status
    const formAtualizarStatus = document.getElementById('formAtualizarStatus');
    if (formAtualizarStatus) {
        formAtualizarStatus.addEventListener('submit', function(e) {
            const status = document.getElementById('novo_status').value;
            if (!status) {
                e.preventDefault();
                alert('Por favor, selecione um status.');
            }
        });
    }

    // Atualizar calendário automaticamente
    const autoRefresh = () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('norefresh')) {
            setTimeout(() => {
                window.location.reload();
            }, 5 * 60 * 1000); // 5 minutos
        }
    };
    autoRefresh();

    // Realce o dia atual
    const highlightToday = () => {
        const today = new Date();
        const todayStr = today.getFullYear() + '-' + 
                        String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                        String(today.getDate()).padStart(2, '0');
        
        document.querySelectorAll('.calendar-day').forEach(day => {
            if (day.getAttribute('href') && day.getAttribute('href').includes(todayStr)) {
                day.classList.add('today');
            }
        });
    };
    highlightToday();

    // Mensagem sobre a duração do serviço
    const serviceDurationMessage = () => {
        const durationElements = document.querySelectorAll('[data-duracao]');
        durationElements.forEach(el => {
            const duration = el.getAttribute('data-duracao');
            if (duration) {
                el.insertAdjacentHTML('beforeend', `<span class="duracao-servico">Duração: ${duration} minutos</span>`);
            }
        });
    };
    serviceDurationMessage();

    // Função para arrastar e soltar agendamentos (opcional)
    const setupDragDrop = () => {
        const timeSlots = document.querySelectorAll('.time-slot');
        if (timeSlots.length === 0) return;
        
        // Inicializar funcionalidade de drag and drop
        // Código de drag and drop seria implementado aqui caso necessário
    };
    // setupDragDrop(); // Descomentado se implementada essa funcionalidade
});
