document.addEventListener('DOMContentLoaded', function() {
    // Variáveis para controlar o estado dos dados selecionados
    let servicoSelecionado = null;
    let profissionalSelecionado = null;
    let dataSelecionada = null;
    let horarioSelecionado = null;
    let duracaoServico = null;
    let valorServico = null;

    // Elementos DOM
    const stepsIndicator = document.querySelectorAll('.step');
    const stepContents = document.querySelectorAll('.step-content');

    // Variáveis globais
    let agendamentoData = {
        servicoId: null,
        servicoNome: '',
        servicoValor: 0,
        servicoDuracao: 0,
        profissionalId: null,
        profissionalNome: '',
        profissionalDias: [],
        dataSelecionada: null,
        horarioSelecionado: null
    };

    // Mapeamento dos dias da semana
    const diasSemana = {
        1: 'Segunda',
        2: 'Terça', 
        3: 'Quarta',
        4: 'Quinta',
        5: 'Sexta',
        6: 'Sábado',
        7: 'Domingo'
    };

    // Função para atualizar o progresso visual
    function updateStepProgress(currentStep) {
        stepsIndicator.forEach((step, index) => {
            if (index < currentStep) {
                step.classList.add('completed');
                step.classList.remove('active');
            } else if (index === currentStep) {
                step.classList.add('active');
                step.classList.remove('completed');
            } else {
                step.classList.remove('active', 'completed');
            }
        });
    }

    // Função para mostrar step específico
    function showStep(stepNumber) {
        stepContents.forEach(content => content.classList.remove('active'));
        document.getElementById(`step${stepNumber}-content`).classList.add('active');
        updateStepProgress(stepNumber - 1);
    }

    // Etapa 1: Seleção de serviço
    document.querySelectorAll('.btn-escolher-servico').forEach(btn => {
        btn.addEventListener('click', function() {
            servicoSelecionado = this.dataset.id;
            const servicoNome = this.dataset.nome;
            valorServico = parseFloat(this.dataset.valor);
            duracaoServico = parseInt(this.dataset.duracao);

            agendamentoData.servicoId = servicoSelecionado;
            agendamentoData.servicoNome = servicoNome;
            agendamentoData.servicoValor = valorServico;
            agendamentoData.servicoDuracao = duracaoServico;

            // Atualizar resumo
            document.getElementById('servico-nome').textContent = nomeServico;
            document.getElementById('servico-valor').textContent = valorServico.toFixed(2).replace('.', ',');
            document.getElementById('servico-duracao').textContent = duracaoServico;
            
            // Atualizar resumos
            document.getElementById('resumo-servico').textContent = agendamentoData.servicoNome;
            document.getElementById('resumo-servico2').textContent = agendamentoData.servicoNome;

            // Ir para próximo step
            showStep(2);
        });
    });

    // Etapa 2: Seleção de profissional
    document.querySelectorAll('.btn-escolher-profissional').forEach(btn => {
        btn.addEventListener('click', function() {
            profissionalSelecionado = this.dataset.id;
            const nomeProfissional = this.dataset.nome;

            agendamentoData.profissionalId = profissionalSelecionado;
            agendamentoData.profissionalNome = nomeProfissional;
            
            // Buscar dias de trabalho do profissional
            buscarDiasProfissional(profissionalSelecionado);

            // Atualizar resumo
            document.getElementById('resumo-servico').textContent = document.getElementById('servico-nome').textContent;
            document.getElementById('resumo-profissional').textContent = nomeProfissional;

            // Criar calendário simples para seleção de data
            criarCalendarioSelecao();

            // Ir para próximo step
            showStep(3);
        });
    });

    // Função para criar calendário de seleção
    function criarCalendarioSelecao() {
        const container = document.getElementById('datepicker');
        const hoje = new Date();
        const proximoMes = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0).getDate();
        
        // Limpar container
        container.innerHTML = '';

        // Criar calendário simples
        const calendar = document.createElement('div');
        calendar.className = 'calendar-grid';
        calendar.innerHTML = `
            <div class="calendar-header mb-3">
                <h6>Selecione uma data disponível</h6>
                <p class="text-muted">Clique em uma data para verificar horários</p>
            </div>
            <div class="dates-grid">
                ${gerarDiasDisponiveis()}
            </div>
        `;
        
        container.appendChild(calendar);
    }

    // Função para gerar dias disponíveis (próximos 30 dias)
    function gerarDiasDisponiveis() {
        const hoje = new Date();
        let html = '';
        
        for (let i = 1; i <= 30; i++) {
            const data = new Date(hoje);
            data.setDate(hoje.getDate() + i);
            
            const dataStr = data.toISOString().split('T')[0];
            const diaSemana = data.getDay();
            const diaNum = data.getDate();
            const mes = data.toLocaleDateString('pt-BR', { month: 'short' });
            const diaNome = data.toLocaleDateString('pt-BR', { weekday: 'short' });

            // Verificar se é dia útil (segunda a sexta por padrão)
            const isDisponivel = diaSemana >= 1 && diaSemana <= 5;
            
            html += `
                <div class="date-option ${isDisponivel ? 'disponivel' : 'indisponivel'}" 
                     data-date="${dataStr}" ${isDisponivel ? '' : 'disabled'}>
                    <div class="day-name">${diaNome}</div>
                    <div class="day-number">${diaNum}</div>
                    <div class="month-name">${mes}</div>
                </div>
            `;
        }
        
        return html;
    }

    // Event delegation para seleção de data
    document.addEventListener('click', function(e) {
        if (e.target.closest('.date-option.disponivel')) {
            const dateOption = e.target.closest('.date-option');
            dataSelecionada = dateOption.dataset.date;
            
            // Remover seleção anterior
            document.querySelectorAll('.date-option').forEach(opt => opt.classList.remove('selected'));
            dateOption.classList.add('selected');
            
            // Habilitar botão continuar
            document.getElementById('btn-confirmar-data').disabled = false;
            
            // Verificar se o profissional trabalha neste dia
            verificarDisponibilidadeDia();
        }
    });

    // Função para verificar disponibilidade do dia
    function verificarDisponibilidadeDia() {
        if (!profissionalSelecionado || !dataSelecionada) return;

        fetch(`cliente_agendar_servico.php?verificar_disponibilidade=1&action=verificar_dias_trabalho&id_profissional=${profissionalSelecionado}&data=${dataSelecionada}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.eDiaTrabalho) {
                    document.getElementById('btn-confirmar-data').disabled = false;
                } else {
                    alert('O profissional não trabalha neste dia da semana. Por favor, escolha outro dia.');
                    document.getElementById('btn-confirmar-data').disabled = true;
                    document.querySelectorAll('.date-option').forEach(opt => opt.classList.remove('selected'));
                    dataSelecionada = null;
                }
            })
            .catch(error => {
                console.error('Erro ao verificar disponibilidade:', error);
            });
    }

    // Botão confirmar data
    document.getElementById('btn-confirmar-data').addEventListener('click', function() {
        if (dataSelecionada) {
            // Atualizar resumo
            const dataFormatada = new Date(dataSelecionada).toLocaleDateString('pt-BR');
            document.getElementById('resumo-servico2').textContent = document.getElementById('servico-nome').textContent;
            document.getElementById('resumo-profissional2').textContent = document.getElementById('resumo-profissional').textContent;
            document.getElementById('resumo-data').textContent = dataFormatada;
            
            // Carregar horários disponíveis
            carregarHorariosDisponiveis();
            showStep(4);
        }
    });

    // Função para carregar horários disponíveis
    function carregarHorariosDisponiveis() {
        const horariosContainer = document.getElementById('horarios-disponiveis');
        horariosContainer.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-warning" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Carregando horários disponíveis...</p>
            </div>
        `;

        fetch(`cliente_agendar_servico.php?verificar_disponibilidade=1&action=verificar_horarios&id_servico=${servicoSelecionado}&id_profissional=${profissionalSelecionado}&data=${dataSelecionada}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.horarios && data.horarios.length > 0) {
                    let html = `
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            O serviço selecionado tem duração de ${data.detalhes?.duracao_servico || duracaoServico} minutos.
                        </div>
                        <div class="horarios-grid">
                    `;

                    data.horarios.forEach(horario => {
                        html += `
                            <button class="btn btn-outline-warning horario-btn" data-horario="${horario}">
                                ${horario}
                            </button>
                        `;
                    });

                    html += '</div>';
                    horariosContainer.innerHTML = html;

                    // Event listeners para seleção de horário
                    document.querySelectorAll('.horario-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            horarioSelecionado = this.dataset.horario;
                            
                            // Remover seleção anterior
                            document.querySelectorAll('.horario-btn').forEach(b => b.classList.remove('btn-warning'));
                            document.querySelectorAll('.horario-btn').forEach(b => b.classList.add('btn-outline-warning'));
                            
                            // Marcar como selecionado
                            this.classList.remove('btn-outline-warning');
                            this.classList.add('btn-warning');
                            
                            // Habilitar botão continuar
                            document.getElementById('btn-confirmar-horario').disabled = false;
                        });
                    });

                } else {
                    horariosContainer.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            ${data.error || 'Não há horários disponíveis para esta data.'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erro ao carregar horários:', error);
                horariosContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Erro ao carregar horários. Tente novamente.
                    </div>
                `;
            });
    }

    // Botão confirmar horário
    document.getElementById('btn-confirmar-horario').addEventListener('click', function() {
        if (horarioSelecionado) {
            // Atualizar confirmação final
            document.getElementById('confirm-servico').textContent = document.getElementById('servico-nome').textContent;
            document.getElementById('confirm-profissional').textContent = document.getElementById('resumo-profissional').textContent;
            document.getElementById('confirm-valor').textContent = valorServico.toFixed(2).replace('.', ',');
            document.getElementById('confirm-data').textContent = new Date(dataSelecionada).toLocaleDateString('pt-BR');
            document.getElementById('confirm-horario').textContent = horarioSelecionado;
            
            showStep(5);
        }
    });

    // Checkbox de concordância
    document.getElementById('concordo').addEventListener('change', function() {
        document.getElementById('btn-finalizar-agendamento').disabled = !this.checked;
    });

    // Finalizar agendamento
    document.getElementById('btn-finalizar-agendamento').addEventListener('click', function() {
        if (!servicoSelecionado || !profissionalSelecionado || !dataSelecionada || !horarioSelecionado) {
            alert('Dados incompletos para finalizar o agendamento.');
            return;
        }

        // Desabilitar botão e mostrar loading
        this.disabled = true;
        this.innerHTML = '<i class="bi bi-clock me-1"></i> Processando...';

        const dadosAgendamento = {
            finalizar_agendamento: true,
            id_servico: servicoSelecionado,
            id_profissional: profissionalSelecionado,
            data_agendamento: dataSelecionada,
            hora_agendamento: horarioSelecionado,
            observacoes: document.getElementById('observacoes').value
        };

        fetch('cliente_agendar_servico.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: Object.keys(dadosAgendamento).map(key => 
                encodeURIComponent(key) + '=' + encodeURIComponent(dadosAgendamento[key])
            ).join('&')
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar modal de sucesso
                document.getElementById('agendamento-id').textContent = data.id_agendamento;
                new bootstrap.Modal(document.getElementById('successModal')).show();
            } else {
                alert(data.message || 'Erro ao finalizar agendamento');
                // Restaurar botão
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-check-circle me-1"></i> Finalizar Agendamento';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao processar agendamento. Tente novamente.');
            // Restaurar botão
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-check-circle me-1"></i> Finalizar Agendamento';
        });
    });

    // Botões voltar
    document.querySelectorAll('.btn-voltar').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetStep = parseInt(this.dataset.step);
            showStep(targetStep);
        });
    });

    function buscarDiasProfissional(profissionalId) {
        // Fazer requisição para buscar dias de trabalho
        fetch(`?verificar_disponibilidade=true&action=verificar_dias_trabalho&id_profissional=${profissionalId}&data=${new Date().toISOString().split('T')[0]}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.debug) {
                    // Converter string de dias para array
                    agendamentoData.profissionalDias = data.debug.dias_array || [];
                    
                    // Atualizar interface com dias do profissional
                    atualizarDiasProfissional();
                }
            })
            .catch(error => {
                console.error('Erro ao buscar dias do profissional:', error);
            });
    }

    function atualizarDiasProfissional() {
        const container = $('#dias-profissional-container');
        const badgesContainer = $('#dias-profissional-badges');
        
        if (agendamentoData.profissionalDias.length > 0) {
            let badgesHtml = '';
            
            agendamentoData.profissionalDias.forEach(dia => {
                const nomeDia = diasSemana[dia] || `Dia ${dia}`;
                badgesHtml += `<span class="badge bg-success me-1 mb-1">${nomeDia}</span>`;
            });
            
            badgesContainer.html(badgesHtml);
            container.show();
        } else {
            container.hide();
        }
    }

    function verificarDataDisponivel(data) {
        if (!agendamentoData.profissionalId) return;
        
        // Verificar se o profissional trabalha neste dia
        fetch(`?verificar_disponibilidade=true&action=verificar_dias_trabalho&id_profissional=${agendamentoData.profissionalId}&data=${data}`)
            .then(response => response.json())
            .then(result => {
                const statusContainer = $('#status-data-selecionada');
                const btnConfirmar = $('#btn-confirmar-data');
                
                if (result.success) {
                    if (result.eDiaTrabalho) {
                        // Data disponível
                        agendamentoData.dataSelecionada = data;
                        
                        statusContainer.html(`
                            <div class="alert alert-success border-0">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                Data disponível! Você pode continuar.
                            </div>
                        `).show();
                        
                        btnConfirmar.prop('disabled', false);
                    } else {
                        // Data indisponível
                        agendamentoData.dataSelecionada = null;
                        
                        statusContainer.html(`
                            <div class="alert alert-warning border-0">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                O profissional não atende neste dia. Escolha outro dia.
                            </div>
                        `).show();
                        
                        btnConfirmar.prop('disabled', true);
                    }
                } else {
                    // Erro
                    statusContainer.html(`
                        <div class="alert alert-danger border-0">
                            <i class="bi bi-x-circle-fill me-2"></i>
                            Erro ao verificar disponibilidade: ${result.error}
                        </div>
                    `).show();
                    
                    btnConfirmar.prop('disabled', true);
                }
            })
            .catch(error => {
                console.error('Erro ao verificar data:', error);
                
                $('#status-data-selecionada').html(`
                    <div class="alert alert-danger border-0">
                        <i class="bi bi-x-circle-fill me-2"></i>
                        Erro de conexão. Tente novamente.
                    </div>
                `).show();
                
                $('#btn-confirmar-data').prop('disabled', true);
            });
    }

    function atualizarInfoServico() {
        $('#servico-nome').text(agendamentoData.servicoNome);
        $('#servico-valor').text(agendamentoData.servicoValor.toFixed(2).replace('.', ','));
        $('#servico-duracao').text(agendamentoData.servicoDuracao);
    }

    function atualizarInfoProfissional() {
        $('#resumo-profissional').text(agendamentoData.profissionalNome);
        $('#resumo-profissional2').text(agendamentoData.profissionalNome);
    }

    function carregarHorarios() {
        if (!agendamentoData.servicoId || !agendamentoData.profissionalId || !agendamentoData.dataSelecionada) {
            return;
        }
        
        const container = $('#horarios-disponiveis');
        container.html(`
            <div class="text-center py-4">
                <div class="spinner-border text-warning" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-2">Carregando horários disponíveis...</p>
            </div>
        `);
        
        // Fazer requisição para buscar horários
        fetch(`?verificar_disponibilidade=true&action=verificar_horarios&id_profissional=${agendamentoData.profissionalId}&id_servico=${agendamentoData.servicoId}&data=${agendamentoData.dataSelecionada}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.horarios) {
                    let horariosHtml = '';
                    
                    if (data.horarios.length > 0) {
                        horariosHtml = '<div class="row">';
                        data.horarios.forEach(horario => {
                            horariosHtml += `
                                <div class="col-md-3 col-sm-4 col-6 mb-2">
                                    <button class="btn btn-outline-warning btn-horario w-100" data-horario="${horario}">
                                        ${horario}
                                    </button>
                                </div>
                            `;
                        });
                        horariosHtml += '</div>';
                    } else {
                        horariosHtml = `
                            <div class="alert alert-warning text-center">
                                <i class="bi bi-clock me-2"></i>
                                Não há horários disponíveis para esta data.
                            </div>
                        `;
                    }
                    
                    container.html(horariosHtml);
                    
                    // Adicionar eventos aos botões de horário
                    $('.btn-horario').on('click', function() {
                        $('.btn-horario').removeClass('btn-warning').addClass('btn-outline-warning');
                        $(this).removeClass('btn-outline-warning').addClass('btn-warning');
                        
                        agendamentoData.horarioSelecionado = $(this).data('horario');
                        $('#btn-confirmar-horario').prop('disabled', false);
                    });
                    
                } else {
                    container.html(`
                        <div class="alert alert-danger text-center">
                            <i class="bi bi-x-circle me-2"></i>
                            Erro ao carregar horários: ${data.error || 'Erro desconhecido'}
                        </div>
                    `);
                }
            })
            .catch(error => {
                console.error('Erro ao carregar horários:', error);
                container.html(`
                    <div class="alert alert-danger text-center">
                        <i class="bi bi-x-circle me-2"></i>
                        Erro de conexão. Tente novamente.
                    </div>
                `);
            });
    }

    function irParaPasso(passo) {
        // Ocultar todos os conteúdos
        $('.step-content').removeClass('active');
        $('.step').removeClass('active');
        
        // Mostrar o passo selecionado
        $(`#step${passo}-content`).addClass('active');
        $(`#step${passo}`).addClass('active');
        
        // Marcar passos anteriores como concluídos
        for (let i = 1; i < passo; i++) {
            $(`#step${i}`).addClass('completed');
        }
        
        // Scroll para o topo
        $('html, body').animate({ scrollTop: 0 }, 300);
    }
});
