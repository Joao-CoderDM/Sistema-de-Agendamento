// Atualizar a página a cada 5 minutos para manter os dados sincronizados
setTimeout(function() {
    window.location.reload();
}, 300000); // 300000 ms = 5 minutos

// Quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Formatação da data atual
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const today = new Date();
    document.getElementById('current-date').textContent = today.toLocaleDateString('pt-BR', options);
});
