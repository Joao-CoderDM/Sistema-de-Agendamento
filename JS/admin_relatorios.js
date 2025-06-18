document.addEventListener('DOMContentLoaded', function() {
    // Verificar se as variáveis de dados existem antes de criar os gráficos
    if (typeof semanasDados !== 'undefined' && semanasDados.length > 0) {
        // Gráfico de faturamento por período
        const salesCtx = document.getElementById('salesChart');
        if (salesCtx) {
            const salesChart = new Chart(salesCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: semanasDados,
                    datasets: [{
                        label: 'Faturamento (R$)',
                        data: valoresSemanaisDados,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    }
                }
            });
        }
    }
    
    if (typeof servicosDados !== 'undefined' && servicosDados.length > 0) {
        // Gráfico de serviços mais populares
        const servicesCtx = document.getElementById('servicesChart');
        if (servicesCtx) {
            const servicesChart = new Chart(servicesCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: servicosDados,
                    datasets: [{
                        label: 'Quantidade',
                        data: contagemServicosDados,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
    }
    
    if (typeof diasDados !== 'undefined' && diasDados.length > 0) {
        // Gráfico de agendamentos por dia da semana
        const weekdayCtx = document.getElementById('weekdayChart');
        if (weekdayCtx) {
            const weekdayChart = new Chart(weekdayCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: diasDados,
                    datasets: [{
                        label: 'Agendamentos',
                        data: contagemDiasDados,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
    }
    
    if (typeof profissionaisDados !== 'undefined' && profissionaisDados.length > 0) {
        // Gráfico de desempenho dos profissionais
        const employeeCtx = document.getElementById('employeeChart');
        if (employeeCtx) {
            const employeeChart = new Chart(employeeCtx.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: profissionaisDados,
                    datasets: [{
                        data: atendimentosProfissionaisDados,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }
    
    // Controle do formulário de período
    const reportTypeSelect = document.getElementById('reportType');
    if (reportTypeSelect) {
        reportTypeSelect.addEventListener('change', function() {
            const today = new Date();
            const startInput = document.getElementById('data_inicio');
            const endInput = document.getElementById('data_fim');
            
            if (startInput && endInput) {
                switch(this.value) {
                    case 'daily':
                        startInput.value = formatDate(today);
                        endInput.value = formatDate(today);
                        break;
                    case 'weekly':
                        const weekStart = new Date(today);
                        weekStart.setDate(today.getDate() - today.getDay());
                        startInput.value = formatDate(weekStart);
                        endInput.value = formatDate(today);
                        break;
                    case 'monthly':
                        startInput.value = formatDate(new Date(today.getFullYear(), today.getMonth(), 1));
                        endInput.value = formatDate(today);
                        break;
                    case 'yearly':
                        startInput.value = formatDate(new Date(today.getFullYear(), 0, 1));
                        endInput.value = formatDate(today);
                        break;
                }
            }
        });
    }
    
    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }
});
