// JavaScript para funcionalidades relacionadas ao topo do sistema cliente
// Este arquivo está vazio por enquanto e será utilizado para futuras implementações

document.addEventListener('DOMContentLoaded', function() {
    // Formatação de data para o painel
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const today = new Date();
    const currentDateElement = document.getElementById('current-date');
    if (currentDateElement) {
        currentDateElement.textContent = today.toLocaleDateString('pt-BR', options);
    }
    
    // Ativar tooltips do Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Garantir que os dropdowns funcionem em dispositivos móveis
    const dropdowns = document.querySelectorAll('.dropdown');
    if (window.innerWidth < 992) {
        dropdowns.forEach(dropdown => {
            dropdown.addEventListener('click', function(e) {
                const dropdownMenu = this.querySelector('.dropdown-menu');
                if (!dropdownMenu.classList.contains('show')) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdownMenu.classList.add('show');
                }
            });
        });
        
        // Fechar dropdown quando clicar fora dele
        document.addEventListener('click', function(e) {
            dropdowns.forEach(dropdown => {
                const dropdownMenu = dropdown.querySelector('.dropdown-menu');
                if (!dropdown.contains(e.target) && dropdownMenu.classList.contains('show')) {
                    dropdownMenu.classList.remove('show');
                }
            });
        });
    }
    
    // Destacar item de menu ativo
    const currentPath = window.location.pathname;
    const filename = currentPath.substring(currentPath.lastIndexOf('/') + 1);
    
    document.querySelectorAll('.navbar-nav .nav-link').forEach(function(link) {
        const href = link.getAttribute('href');
        if (href && href.includes(filename)) {
            link.classList.add('active');
        }
    });
});
