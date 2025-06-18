document.addEventListener('DOMContentLoaded', function() {
    // Formatação da data atual
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        timeZone: 'America/Sao_Paulo'
    };
    const today = new Date();
    const dateElement = document.getElementById('current-date');
    if (dateElement) {
        dateElement.textContent = today.toLocaleDateString('pt-BR', options);
    }

    // Animação dos cards ao carregar a página
    animateCards();

    // Verificar notificações
    verificarNotificacoes();

    // Atualizar informações em tempo real
    setInterval(atualizarInformacoes, 30000); // Atualizar a cada 30 segundos

    // Event listeners para ações rápidas
    setupEventListeners();
});

// Função para animar os cards
function animateCards() {
    const cards = document.querySelectorAll('.overview-card, .feature-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Função para verificar notificações
function verificarNotificacoes() {
    // Simular verificação de notificações
    // Em uma implementação real, isso seria uma chamada AJAX
    console.log('Verificando notificações...');
}

// Função para atualizar informações em tempo real
function atualizarInformacoes() {
    // Atualizar próximo agendamento
    atualizarProximoAgendamento();
    
    // Atualizar pontos de fidelidade
    atualizarPontosFidelidade();
}

// Função para atualizar próximo agendamento
function atualizarProximoAgendamento() {
    fetch('ajax/buscar_proximo_agendamento.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.agendamento) {
                // Atualizar interface com novo agendamento
                console.log('Próximo agendamento atualizado:', data.agendamento);
            }
        })
        .catch(error => {
            console.error('Erro ao buscar próximo agendamento:', error);
        });
}

// Função para atualizar pontos de fidelidade
function atualizarPontosFidelidade() {
    fetch('ajax/buscar_pontos_fidelidade.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualizar pontos na interface
                const pontosElement = document.querySelector('.overview-card:nth-child(2) h3');
                if (pontosElement) {
                    pontosElement.textContent = data.pontos_atuais;
                }
                
                const proximaRecompensaElement = document.querySelector('.overview-card:nth-child(3) h3');
                if (proximaRecompensaElement) {
                    proximaRecompensaElement.textContent = data.proxima_recompensa;
                }
            }
        })
        .catch(error => {
            console.error('Erro ao buscar pontos de fidelidade:', error);
        });
}

// Configurar event listeners
function setupEventListeners() {
    // Botões de confirmação/cancelamento de agendamento
    document.querySelectorAll('.btn-outline-success').forEach(btn => {
        if (btn.textContent.includes('Confirmar')) {
            btn.addEventListener('click', confirmarAgendamento);
        }
    });

    document.querySelectorAll('.btn-outline-danger').forEach(btn => {
        if (btn.textContent.includes('Cancelar')) {
            btn.addEventListener('click', cancelarAgendamento);
        }
    });

    // Hover effects nos cards
    document.querySelectorAll('.overview-card, .feature-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.07)';
        });
    });

    // Marcar notificações como lidas ao clicar
    document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => {
        item.addEventListener('click', function() {
            // Marcar como lida
            this.style.opacity = '0.7';
        });
    });
}

// Função para confirmar agendamento
function confirmarAgendamento(event) {
    event.preventDefault();
    
    const agendamentoId = this.closest('.appointment-card').dataset.agendamentoId;
    
    if (confirm('Deseja confirmar este agendamento?')) {
        fetch('ajax/confirmar_agendamento.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                agendamento_id: agendamentoId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Agendamento confirmado com sucesso!', 'success');
                // Atualizar interface
                const badge = this.closest('.appointment-card').querySelector('.badge');
                badge.className = 'badge bg-success';
                badge.textContent = 'Confirmado';
                this.style.display = 'none';
            } else {
                showToast('Erro ao confirmar agendamento: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showToast('Erro ao confirmar agendamento', 'error');
        });
    }
}

// Função para cancelar agendamento
function cancelarAgendamento(event) {
    event.preventDefault();
    
    const agendamentoId = this.closest('.appointment-card').dataset.agendamentoId;
    
    if (confirm('Tem certeza que deseja cancelar este agendamento?')) {
        const motivo = prompt('Motivo do cancelamento (opcional):');
        
        fetch('ajax/cancelar_agendamento.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                agendamento_id: agendamentoId,
                motivo: motivo || ''
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Agendamento cancelado com sucesso!', 'info');
                // Remover ou ocultar o agendamento
                this.closest('.appointment-card').style.opacity = '0.5';
                const badge = this.closest('.appointment-card').querySelector('.badge');
                badge.className = 'badge bg-danger';
                badge.textContent = 'Cancelado';
                this.style.display = 'none';
                this.previousElementSibling.style.display = 'none';
            } else {
                showToast('Erro ao cancelar agendamento: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showToast('Erro ao cancelar agendamento', 'error');
        });
    }
}

// Função para exibir toast notifications
function showToast(message, type = 'info') {
    // Remover toast anterior se existir
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }

    // Criar novo toast
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="bi bi-${getToastIcon(type)} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;

    // Adicionar estilos
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;

    // Definir cor baseada no tipo
    switch(type) {
        case 'success':
            toast.style.backgroundColor = '#28a745';
            break;
        case 'error':
            toast.style.backgroundColor = '#dc3545';
            break;
        case 'warning':
            toast.style.backgroundColor = '#ffc107';
            toast.style.color = '#212529';
            break;
        default:
            toast.style.backgroundColor = '#17a2b8';
    }

    document.body.appendChild(toast);

    // Animar entrada
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 100);

    // Remover automaticamente após 5 segundos
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// Função para obter ícone do toast
function getToastIcon(type) {
    switch(type) {
        case 'success': return 'check-circle';
        case 'error': return 'exclamation-triangle';
        case 'warning': return 'exclamation-triangle';
        default: return 'info-circle';
    }
}

// Função para formatar datas
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

// Função para formatar horários
function formatTime(timeString) {
    return timeString.substring(0, 5); // HH:MM
}

// Função para formatar valores monetários
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

// Função para calcular tempo até agendamento
function timeUntilAppointment(appointmentDate, appointmentTime) {
    const now = new Date();
    const appointment = new Date(`${appointmentDate}T${appointmentTime}`);
    const diff = appointment - now;

    if (diff < 0) return 'Agendamento passou';

    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

    if (days > 0) return `${days} dia${days > 1 ? 's' : ''}`;
    if (hours > 0) return `${hours} hora${hours > 1 ? 's' : ''}`;
    return `${minutes} minuto${minutes > 1 ? 's' : ''}`;
}

// Função para adicionar classe ativa ao menu baseado na página atual
function setActiveMenu() {
    const currentPath = window.location.pathname;
    const menuItems = document.querySelectorAll('.navbar-nav .nav-link');
    
    menuItems.forEach(item => {
        const href = item.getAttribute('href');
        if (currentPath.includes(href)) {
            item.classList.add('active');
        }
    });
}

// Executar quando a página carregar
document.addEventListener('DOMContentLoaded', setActiveMenu);
