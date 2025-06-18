document.addEventListener('DOMContentLoaded', function() {
    // Formatação da data atual
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const today = new Date();
    document.getElementById('current-date').textContent = today.toLocaleDateString('pt-BR', options);
    
    // Função para visualizar detalhes do agendamento
    window.verDetalhesAgendamento = function(id) {
        console.log('Ver detalhes do agendamento ID:', id);
        // Aqui você pode abrir um modal com os detalhes ou redirecionar para uma página de detalhes
        window.location.href = `admin_agendamentos.php?id=${id}`;
    };
});
