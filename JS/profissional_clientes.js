/**
 * JavaScript para página de clientes do profissional
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicialização de componentes
    initComponents();
    
    // Formatação de elementos
    formatPhoneNumbers();
    
    // Configuração de eventos
    setupEventListeners();
});

/**
 * Inicializa componentes da interface
 */
function initComponents() {
    // Inicializar os tooltips do Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Adicionar classe para animar elementos na entrada
    document.querySelectorAll('.animate__animated').forEach((element, index) => {
        // Atraso baseado no índice para entrada sequencial
        element.style.animationDelay = `${index * 0.1}s`;
    });
}

/**
 * Formata números de telefone para melhor visualização
 */
function formatPhoneNumbers() {
    // Função para formatar números de telefone
    document.querySelectorAll('.telefone-formatado').forEach(element => {
        const telefone = element.textContent.trim();
        if (telefone.length === 11) {
            // Formato: (XX) XXXXX-XXXX
            element.textContent = `(${telefone.substring(0, 2)}) ${telefone.substring(2, 7)}-${telefone.substring(7)}`;
        } else if (telefone.length === 10) {
            // Formato: (XX) XXXX-XXXX
            element.textContent = `(${telefone.substring(0, 2)}) ${telefone.substring(2, 6)}-${telefone.substring(6)}`;
        }
    });
}

/**
 * Configura listeners de eventos para elementos interativos
 */
function setupEventListeners() {
    // Efeito de hover nas linhas da tabela
    const clienteRows = document.querySelectorAll('.cliente-row');
    clienteRows.forEach(row => {
        row.addEventListener('mouseover', () => {
            row.classList.add('row-hover');
        });
        row.addEventListener('mouseout', () => {
            row.classList.remove('row-hover');
        });
    });
    
    // Filtro de busca rápida (opcional)
    const buscarInput = document.querySelector('input[name="busca"]');
    if (buscarInput) {
        buscarInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
    }
}

/**
 * Exibe notificação temporária
 * @param {string} mensagem - Texto da mensagem
 * @param {string} tipo - Tipo de notificação (success, danger, warning)
 */
function mostrarNotificacao(mensagem, tipo = 'success') {
    const notificacao = document.createElement('div');
    notificacao.classList.add('toast', 'show', `bg-${tipo}`, 'text-white');
    notificacao.setAttribute('role', 'alert');
    notificacao.setAttribute('aria-live', 'assertive');
    notificacao.setAttribute('aria-atomic', 'true');
    
    notificacao.innerHTML = `
        <div class="toast-header bg-${tipo} text-white">
            <strong class="me-auto">Notificação</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            ${mensagem}
        </div>
    `;
    
    // Adicionar ao container de toasts
    const toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.classList.add('toast-container', 'position-fixed', 'bottom-0', 'end-0', 'p-3');
        document.body.appendChild(container);
        container.appendChild(notificacao);
    } else {
        toastContainer.appendChild(notificacao);
    }
    
    // Auto-remover após 3 segundos
    setTimeout(() => {
        notificacao.classList.remove('show');
        setTimeout(() => {
            notificacao.remove();
        }, 300);
    }, 3000);
}

/**
 * JavaScript para funcionalidades específicas da página de clientes
 */

document.addEventListener('DOMContentLoaded', function() {
    // Funcionalidade de busca de clientes
    const searchInput = document.querySelector('.search-box');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const clienteCards = document.querySelectorAll('.cliente-card');
            
            clienteCards.forEach(card => {
                const nome = card.querySelector('h5').textContent.toLowerCase();
                const telefone = card.querySelector('.bi-telephone').parentElement.nextElementSibling.textContent.toLowerCase();
                
                if (nome.includes(searchTerm) || telefone.includes(searchTerm)) {
                    card.closest('.col-md-6').style.display = 'block';
                } else {
                    card.closest('.col-md-6').style.display = 'none';
                }
            });
        });
    }
    
    // Filtro de seleção de clientes
    const filtroSelect = document.querySelector('.form-select');
    if (filtroSelect) {
        filtroSelect.addEventListener('change', function() {
            const clienteCards = document.querySelectorAll('.cliente-card');
            const opcao = this.value;
            
            clienteCards.forEach(card => {
                const visitas = parseInt(card.querySelector('.visitas-badge').textContent);
                const ultimaVisita = new Date(card.querySelector('.bi-calendar-check').parentElement.nextElementSibling.textContent.split('/').reverse().join('-'));
                const hoje = new Date();
                const diffDias = Math.floor((hoje - ultimaVisita) / (1000 * 60 * 60 * 24));
                
                if (opcao === 'Todos os clientes') {
                    card.closest('.col-md-6').style.display = 'block';
                } else if (opcao === 'Clientes frequentes (6+ visitas)' && visitas >= 6) {
                    card.closest('.col-md-6').style.display = 'block';
                } else if (opcao === 'Clientes novos (0-5 visitas)' && visitas <= 5) {
                    card.closest('.col-md-6').style.display = 'block';
                } else if (opcao === 'Sem visita nos últimos 60 dias' && diffDias > 60) {
                    card.closest('.col-md-6').style.display = 'block';
                } else {
                    card.closest('.col-md-6').style.display = 'none';
                }
            });
        });
    }
});
