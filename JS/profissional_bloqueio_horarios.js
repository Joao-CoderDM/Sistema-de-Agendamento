document.addEventListener('DOMContentLoaded', function() {
    // Configurar Flatpickr para seleção de data (calendário)
    const dataBloqueio = document.getElementById('data_bloqueio');
    if (dataBloqueio) {
        const dataPickr = flatpickr(dataBloqueio, {
            dateFormat: "Y-m-d",
            locale: "pt",
            minDate: "today",
            disableMobile: "true",
            onChange: function(selectedDates, dateStr) {
                if (selectedDates.length > 0) {
                    // Obter dia da semana (0 = domingo, 6 = sábado)
                    const diaSemana = selectedDates[0].getDay();
                    
                    // Verificar se há configuração de agenda para este dia
                    fetch(`../Ajax/verificar_agenda_profissional.php?data=${dateStr}&dia_semana=${diaSemana}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.horarios) {
                                // Atualizar campos de horário com base na agenda configurada
                                const horaInicio = document.getElementById('hora_inicio_bloqueio');
                                const horaFim = document.getElementById('hora_fim_bloqueio');
                                
                                if (horaInicio && horaFim) {
                                    horaInicio.min = data.horarios.hora_inicio;
                                    horaInicio.max = data.horarios.hora_fim;
                                    horaFim.min = data.horarios.hora_inicio;
                                    horaFim.max = data.horarios.hora_fim;
                                }
                                
                                // Mostrar informações da agenda
                                const infoElement = document.getElementById('info_agenda');
                                if (infoElement) {
                                    infoElement.innerHTML = `
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Horário de trabalho: ${data.horarios.hora_inicio} às ${data.horarios.hora_fim}
                                            (Intervalos de ${data.horarios.intervalo_minutos} minutos)
                                        </div>
                                    `;
                                }
                            } else {
                                // Dia não configurado para trabalho
                                const infoElement = document.getElementById('info_agenda');
                                if (infoElement) {
                                    infoElement.innerHTML = `
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            Você não trabalha neste dia da semana. 
                                            <a href="profissional_configurar_agenda.php">Configurar agenda</a>
                                        </div>
                                    `;
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao verificar agenda:', error);
                        });
                }
            }
        });
    }
    
    // Validação de horários
    const horaInicio = document.getElementById('hora_inicio_bloqueio');
    const horaFim = document.getElementById('hora_fim_bloqueio');
    
    if (horaInicio && horaFim) {
        horaInicio.addEventListener('change', function() {
            horaFim.min = this.value;
            if (horaFim.value && horaFim.value <= this.value) {
                horaFim.value = '';
            }
        });
        
        horaFim.addEventListener('change', function() {
            if (this.value <= horaInicio.value) {
                alert('A hora de fim deve ser posterior à hora de início.');
                this.value = '';
            }
        });
    }
});
