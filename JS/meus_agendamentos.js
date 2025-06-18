// Funcionalidade para avaliação com estrelas
document.addEventListener('DOMContentLoaded', function() {
    // Variáveis globais
    let idAgendamentoAtual = null;
    
    // Função para ver detalhes do agendamento
    window.verDetalhesAgendamento = function(id) {
        if (!id) return;
        
        // Mostrar spinner enquanto carrega
        const detalhesContent = document.getElementById('detalhes-agendamento');
        detalhesContent.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-warning" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p>Carregando informações...</p>
            </div>
        `;
        
        // Buscar detalhes do agendamento via AJAX
        fetch(`../Ajax/processar_agendamento_cliente.php?acao=detalhes&id_agendamento=${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na requisição');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Definir o agendamento ID para o botão "Agendar novamente"
                    const btnAgendarNovamente = document.getElementById('btn-agendar-novamente');
                    if (btnAgendarNovamente) {
                        btnAgendarNovamente.setAttribute('data-id', id);
                        btnAgendarNovamente.onclick = function() {
                            window.location.href = `cliente_agendar_servico.php?reagendar=${id}`;
                        };
                    }
                    
                    // Formatar o status com cores adequadas
                    let statusHtml = '';
                    switch (data.agendamento.status) {
                        case 'agendado':
                            statusHtml = '<span class="badge bg-warning">Agendado</span>';
                            break;
                        case 'confirmado':
                            statusHtml = '<span class="badge bg-success">Confirmado</span>';
                            break;
                        case 'concluído':
                            statusHtml = '<span class="badge bg-secondary">Concluído</span>';
                            break;
                        case 'cancelado':
                            statusHtml = '<span class="badge bg-danger">Cancelado</span>';
                            break;
                        default:
                            statusHtml = `<span class="badge bg-primary">${data.agendamento.status_formatado}</span>`;
                    }
                    
                    // Construir o HTML com os detalhes do agendamento
                    const html = `
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Serviço:</strong></p>
                                <p>${data.agendamento.nome_servico}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Profissional:</strong></p>
                                <p>${data.agendamento.nome_profissional}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Data:</strong></p>
                                <p>${data.agendamento.data_formatada}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Horário:</strong></p>
                                <p>${data.agendamento.hora_formatada}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Valor:</strong></p>
                                <p>R$ ${data.agendamento.valor_formatado}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Status:</strong></p>
                                <p>${statusHtml}</p>
                            </div>
                        </div>
                    `;
                    
                    detalhesContent.innerHTML = html;
                    
                    // Mostrar/esconder botão "Agendar novamente" com base no status
                    if (btnAgendarNovamente) {
                        if (data.agendamento.status === 'cancelado' || data.agendamento.status === 'concluído') {
                            btnAgendarNovamente.classList.remove('d-none');
                        } else {
                            btnAgendarNovamente.classList.add('d-none');
                        }
                    }
                } else {
                    detalhesContent.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                detalhesContent.innerHTML = `<div class="alert alert-danger">Erro ao carregar detalhes do agendamento. Tente novamente.</div>`;
            });
    };
    
    // Configurar cancelamento de agendamento
    const cancelarModal = document.getElementById('cancelarAgendamentoModal');
    if (cancelarModal) {
        cancelarModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            
            if (!id) return;
            
            idAgendamentoAtual = id;
            document.getElementById('id_agendamento_cancelar').value = id;
        });
        
        const btnConfirmarCancelamento = document.getElementById('btn-confirmar-cancelamento');
        if (btnConfirmarCancelamento) {
            btnConfirmarCancelamento.addEventListener('click', function() {
                const motivo = document.getElementById('motivo_cancelamento').value;
                const observacao = document.getElementById('observacao_cancelamento').value;
                
                if (!motivo) {
                    alert('Por favor, selecione um motivo para o cancelamento.');
                    return;
                }
                
                const dados = {
                    id_agendamento: idAgendamentoAtual,
                    motivo: motivo,
                    observacao: observacao
                };
                
                // Mostrar indicador de carregamento
                btnConfirmarCancelamento.disabled = true;
                btnConfirmarCancelamento.innerHTML = `
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Processando...
                `;
                
                // Enviar requisição
                fetch('../Ajax/processar_agendamento_cliente.php?acao=cancelar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(dados)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Fechar modal e recarregar página para mostrar mudanças
                        const modal = bootstrap.Modal.getInstance(cancelarModal);
                        modal.hide();
                        
                        // Exibir mensagem de sucesso e recarregar após 2 segundos
                        Swal.fire({
                            title: 'Sucesso!',
                            text: 'Agendamento cancelado com sucesso',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        // Exibir mensagem de erro
                        Swal.fire({
                            title: 'Erro',
                            text: data.message || 'Ocorreu um erro ao cancelar o agendamento',
                            icon: 'error'
                        });
                        
                        // Restaurar botão
                        btnConfirmarCancelamento.disabled = false;
                        btnConfirmarCancelamento.innerHTML = 'Confirmar Cancelamento';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Ocorreu um erro ao processar a requisição. Tente novamente.');
                    
                    // Restaurar botão
                    btnConfirmarCancelamento.disabled = false;
                    btnConfirmarCancelamento.innerHTML = 'Confirmar Cancelamento';
                });
            });
        }
    }
    
    // Configurar reagendamento
    const editarModal = document.getElementById('editarAgendamentoModal');
    if (editarModal) {
        editarModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const profissionalId = button.getAttribute('data-profissional');
            const servicoId = button.getAttribute('data-servico');
            
            if (!id) return;
            
            idAgendamentoAtual = id;
            document.getElementById('id_agendamento_editar').value = id;
            document.getElementById('profissional_id').value = profissionalId;
            document.getElementById('servico_id').value = servicoId;
            
            // Limpar campos
            document.getElementById('nova_data').value = '';
            const horarioSelect = document.getElementById('novo_horario');
            horarioSelect.innerHTML = '<option value="">Selecione um horário</option>';
            horarioSelect.disabled = true;
        });
        
        // Ao mudar a data, buscar horários disponíveis
        const novaData = document.getElementById('nova_data');
        if (novaData) {
            novaData.addEventListener('change', function() {
                const data = this.value;
                const horarioSelect = document.getElementById('novo_horario');
                
                if (!data) {
                    horarioSelect.innerHTML = '<option value="">Selecione um horário</option>';
                    horarioSelect.disabled = true;
                    return;
                }
                
                // Verificar se a data não é anterior ao dia atual
                const hoje = new Date();
                hoje.setHours(0, 0, 0, 0);
                const dataSelecionada = new Date(data);
                
                if (dataSelecionada < hoje) {
                    alert('Por favor, selecione uma data futura para reagendamento.');
                    this.value = '';
                    return;
                }
                
                // Mostrar indicador de carregamento
                horarioSelect.innerHTML = '<option value="">Carregando horários...</option>';
                horarioSelect.disabled = true;
                
                // Buscar horários disponíveis via AJAX
                fetch(`../HTML/cliente_agendar_servico.php?verificar_disponibilidade=1&action=verificar_horarios_reagendamento&id_agendamento=${idAgendamentoAtual}&id_profissional=${document.getElementById('profissional_id').value}&id_servico=${document.getElementById('servico_id').value}&data=${data}`)
                    .then(response => response.json())
                    .then(data => {
                        horarioSelect.innerHTML = '<option value="">Selecione um horário</option>';
                        
                        if (data.success && data.horarios.length > 0) {
                            // Preencher select com horários disponíveis
                            data.horarios.forEach(horario => {
                                const option = document.createElement('option');
                                option.value = horario + ':00'; // Adicionar segundos para compatibilidade
                                option.textContent = horario;
                                horarioSelect.appendChild(option);
                            });
                            horarioSelect.disabled = false;
                        } else {
                            if (data.message) {
                                horarioSelect.innerHTML = `<option value="">${data.message}</option>`;
                            } else {
                                horarioSelect.innerHTML = '<option value="">Nenhum horário disponível nesta data</option>';
                            }
                            horarioSelect.disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        horarioSelect.innerHTML = '<option value="">Erro ao carregar horários</option>';
                        horarioSelect.disabled = true;
                    });
            });
        }
        
        // Configurar o botão de confirmar reagendamento
        const btnConfirmarReagendamento = document.getElementById('btn-confirmar-reagendamento');
        if (btnConfirmarReagendamento) {
            btnConfirmarReagendamento.addEventListener('click', function() {
                const novaData = document.getElementById('nova_data').value;
                const novoHorario = document.getElementById('novo_horario').value;
                const observacao = document.getElementById('observacao_reagendamento')?.value || '';
                
                if (!novaData) {
                    alert('Por favor, selecione uma data para o reagendamento.');
                    return;
                }
                
                if (!novoHorario) {
                    alert('Por favor, selecione um horário para o reagendamento.');
                    return;
                }
                
                const dados = {
                    id_agendamento: idAgendamentoAtual,
                    nova_data: novaData,
                    novo_horario: novoHorario,
                    observacao: observacao
                };
                
                // Mostrar indicador de carregamento
                btnConfirmarReagendamento.disabled = true;
                btnConfirmarReagendamento.innerHTML = `
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Processando...
                `;
                
                // Enviar requisição
                fetch('../Ajax/processar_agendamento_cliente.php?acao=reagendar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(dados)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Fechar modal e recarregar página para mostrar mudanças
                        const modal = bootstrap.Modal.getInstance(editarModal);
                        modal.hide();
                        
                        // Exibir mensagem de sucesso e recarregar após 2 segundos
                        Swal.fire({
                            title: 'Sucesso!',
                            text: 'Agendamento reagendado com sucesso',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        // Exibir mensagem de erro
                        Swal.fire({
                            title: 'Erro',
                            text: data.message || 'Ocorreu um erro ao reagendar o agendamento',
                            icon: 'error'
                        });
                        
                        // Restaurar botão
                        btnConfirmarReagendamento.disabled = false;
                        btnConfirmarReagendamento.innerHTML = 'Confirmar Reagendamento';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Ocorreu um erro ao processar a requisição. Tente novamente.');
                    
                    // Restaurar botão
                    btnConfirmarReagendamento.disabled = false;
                    btnConfirmarReagendamento.innerHTML = 'Confirmar Reagendamento';
                });
            });
        }
    }
    
    // Configurar avaliação de serviço concluído
    const avaliarModal = document.getElementById('avaliarServicoModal');
    if (avaliarModal) {
        avaliarModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            
            if (!id) return;
            
            idAgendamentoAtual = id;
            document.getElementById('id_agendamento_avaliar').value = id;
            
            // Limpar avaliação anterior
            const stars = document.querySelectorAll('.estrela');
            stars.forEach(star => star.classList.remove('active'));
            document.getElementById('avaliacao_valor').value = 0;
            document.getElementById('comentario_avaliacao').value = '';
        });
        
        // Configurar sistema de avaliação por estrelas
        const stars = document.querySelectorAll('.estrela');
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const valor = parseInt(this.getAttribute('data-valor'));
                document.getElementById('avaliacao_valor').value = valor;
                
                // Atualizar visual
                stars.forEach(s => {
                    const sValor = parseInt(s.getAttribute('data-valor'));
                    if (sValor <= valor) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });
        
        // Configurar envio da avaliação
        const formAvaliar = document.getElementById('formAvaliar');
        if (formAvaliar) {
            formAvaliar.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const avaliacao = parseInt(document.getElementById('avaliacao_valor').value);
                const comentario = document.getElementById('comentario_avaliacao').value;
                
                if (avaliacao === 0) {
                    alert('Por favor, selecione uma avaliação de 1 a 5 estrelas.');
                    return;
                }
                
                const dados = {
                    id_agendamento: idAgendamentoAtual,
                    avaliacao: avaliacao,
                    comentario: comentario
                };
                
                // Mostrar indicador de carregamento
                const btnEnviarAvaliacao = document.querySelector('#formAvaliar button[type="submit"]');
                btnEnviarAvaliacao.disabled = true;
                btnEnviarAvaliacao.innerHTML = `
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Enviando...
                `;
                
                // Enviar requisição
                fetch('../Ajax/processar_agendamento_cliente.php?acao=avaliar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(dados)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Fechar modal e recarregar página para mostrar mudanças
                        const modal = bootstrap.Modal.getInstance(avaliarModal);
                        modal.hide();
                        
                        // Exibir mensagem de sucesso e recarregar após 2 segundos
                        Swal.fire({
                            title: 'Obrigado!',
                            text: 'Sua avaliação foi registrada com sucesso',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        // Exibir mensagem de erro
                        Swal.fire({
                            title: 'Erro',
                            text: data.message || 'Ocorreu um erro ao enviar sua avaliação',
                            icon: 'error'
                        });
                        
                        // Restaurar botão
                        btnEnviarAvaliacao.disabled = false;
                        btnEnviarAvaliacao.innerHTML = 'Enviar Avaliação';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Ocorreu um erro ao processar sua avaliação. Tente novamente.');
                    
                    // Restaurar botão
                    btnEnviarAvaliacao.disabled = false;
                    btnEnviarAvaliacao.innerHTML = 'Enviar Avaliação';
                });
            });
        }
    }
});
