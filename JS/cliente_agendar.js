document.addEventListener('DOMContentLoaded', function() {
    // Variáveis para controlar o estado dos dados selecionados
    let servicoSelecionado = null;
    let profissionalSelecionado = null;
    let dataSelecionada = null;
    let horarioSelecionado = null;
    let duracaoServico = null;

    // Função para carregar horários disponíveis
    const carregarHorariosDisponiveis = () => {
        if (!servicoSelecionado || !profissionalSelecionado || !dataSelecionada) return;
        
        const horariosContainer = document.getElementById('horarios-disponiveis');
        horariosContainer.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-warning" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Carregando horários disponíveis...</p>
            </div>
        `;
        
        // Habilitar/desabilitar botão de continuar
        const btnConfirmarHorario = document.getElementById('btn-confirmar-horario');
        btnConfirmarHorario.disabled = true;
        
        fetch(`../Ajax/obter_horarios_disponiveis.php?profissional_id=${profissionalSelecionado}&data=${dataSelecionada}&servico_id=${servicoSelecionado}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                horariosContainer.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i> ${data.error}
                    </div>
                `;
                return;
            }
            
            // Armazenar a duração do serviço
            duracaoServico = data.duracao;
            
            if (data.horarios && data.horarios.length > 0) {
                let html = `
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        O serviço selecionado tem duração de ${duracaoServico} minutos.
                    </div>
                    <div class="horarios-grid">
                `;
                
                data.horarios.forEach(horario => {
                    html += `
                        <button type="button" class="btn btn-outline-secondary horario-btn" data-horario="${horario}">
                            ${horario}
                        </button>
                    `;
                });
                
                html += `</div>`;
                horariosContainer.innerHTML = html;
                
                // Adicionar eventos aos botões de horário
                document.querySelectorAll('.horario-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        // Remover seleção anterior
                        document.querySelectorAll('.horario-btn').forEach(b => b.classList.remove('active'));
                        
                        // Adicionar seleção atual
                        this.classList.add('active');
                        horarioSelecionado = this.getAttribute('data-horario');
                        
                        // Habilitar botão de continuar
                        btnConfirmarHorario.disabled = false;
                    });
                });
            } else {
                horariosContainer.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Não há horários disponíveis para esta data. Por favor, selecione outra data.
                    </div>
                `;
            }
        })
        .catch(error => {
            horariosContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle me-2"></i>
                    Erro ao carregar horários. Por favor, tente novamente.
                </div>
            `;
            console.error('Erro:', error);
        });
    };

    // Atualizar as seleções quando o serviço for escolhido
    const servicoSelect = document.getElementById('id_servico');
    if (servicoSelect) {
        servicoSelect.addEventListener('change', function() {
            servicoSelecionado = this.value;
            document.querySelectorAll('[id^="resumo-servico"]').forEach(el => {
                el.textContent = this.options[this.selectedIndex].text;
            });
        });
    }

    // Atualizar as seleções quando o profissional for escolhido
    const profissionalSelect = document.getElementById('id_profissional');
    if (profissionalSelect) {
        profissionalSelect.addEventListener('change', function() {
            profissionalSelecionado = this.value;
            document.querySelectorAll('[id^="resumo-profissional"]').forEach(el => {
                el.textContent = this.options[this.selectedIndex].text;
            });
        });
    }

    // Inicializar o datepicker e atualizar a data selecionada
    const initDatepicker = () => {
        const datepicker = document.getElementById('datepicker');
        if (!datepicker) return;
        
        // Configuração do datepicker será implementada aqui
        // ...
        
        // Quando uma data for selecionada:
        dataSelecionada = '2023-01-01'; // Exemplo - será a data selecionada no datepicker
        document.getElementById('resumo-data').textContent = '01/01/2023'; // Formato para exibição
        carregarHorariosDisponiveis();
    };

    // Inicializar o sistema de etapas
    const initSteps = () => {
        const btnContinuar = document.querySelectorAll('.btn-continuar');
        const btnVoltar = document.querySelectorAll('.btn-voltar');
        const steps = document.querySelectorAll('.step');
        const contents = document.querySelectorAll('.step-content');
        
        // Continuar para a próxima etapa
        btnContinuar.forEach(btn => {
            btn.addEventListener('click', function() {
                const currentStep = parseInt(this.closest('.step-content').id.split('-')[1]);
                const nextStep = currentStep + 1;
                
                // Esconder etapa atual
                document.getElementById(`step${currentStep}-content`).style.display = 'none';
                // Mostrar próxima etapa
                document.getElementById(`step${nextStep}-content`).style.display = 'block';
                
                // Atualizar indicadores de etapa
                steps.forEach(step => step.classList.remove('active'));
                document.getElementById(`step${nextStep}`).classList.add('active');
                
                // Carregar dados necessários para a etapa
                if (nextStep === 4) { // Etapa de horários
                    carregarHorariosDisponiveis();
                }
                
                // Atualizar resumo da confirmação
                if (nextStep === 5) {
                    document.getElementById('confirm-servico').textContent = document.getElementById('resumo-servico').textContent;
                    document.getElementById('confirm-profissional').textContent = document.getElementById('resumo-profissional').textContent;
                    document.getElementById('confirm-data').textContent = document.getElementById('resumo-data').textContent;
                    document.getElementById('confirm-horario').textContent = horarioSelecionado;
                }
            });
        });
        
        // Voltar para a etapa anterior
        btnVoltar.forEach(btn => {
            btn.addEventListener('click', function() {
                const targetStep = parseInt(this.getAttribute('data-step'));
                const currentStep = parseInt(this.closest('.step-content').id.split('-')[1]);
                
                // Esconder etapa atual
                document.getElementById(`step${currentStep}-content`).style.display = 'none';
                // Mostrar etapa anterior
                document.getElementById(`step${targetStep}-content`).style.display = 'block';
                
                // Atualizar indicadores de etapa
                steps.forEach(step => step.classList.remove('active'));
                document.getElementById(`step${targetStep}`).classList.add('active');
            });
        });
    };

    // Inicializar componentes
    initSteps();
    // initDatepicker(); - Descomentado quando implementado
});
