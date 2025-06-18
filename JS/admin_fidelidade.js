document.addEventListener('DOMContentLoaded', function() {
    // Auto-calcular pontos com base no valor do serviço selecionado
    document.getElementById('serviceSelect').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const valor = parseFloat(selectedOption.getAttribute('data-valor'));
            const pontosPorReal = parseFloat(document.getElementById('pointsPerCurrency').value);
            const pontos = Math.floor(valor * pontosPorReal);
            document.getElementById('pointsValue').value = pontos;
        } else {
            document.getElementById('pointsValue').value = 0;
        }
    });
    
    // Carregar detalhes do cartão fidelidade
    document.querySelectorAll('.view-loyalty').forEach(btn => {
        btn.addEventListener('click', function() {
            const clienteId = this.getAttribute('data-id');
            const clienteNome = this.getAttribute('data-nome');
            const clientePontos = this.getAttribute('data-pontos');
            
            document.getElementById('cliente-nome').textContent = clienteNome;
            document.getElementById('pontos-atuais').textContent = clientePontos;
            
            // Mostrar loading
            document.getElementById('loyalty-details').style.display = 'none';
            document.querySelector('.spinner-border').parentElement.style.display = 'block';
            
            // Carregar os detalhes do cliente via AJAX
            fetch('../Ajax/obter_detalhe_fidelidade.php?id_cliente=' + clienteId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Preencher os dados
                        document.getElementById('cliente-desde').textContent = data.data_cadastro;
                        document.getElementById('resumo-pontos-atuais').textContent = data.pontos_atuais;
                        document.getElementById('resumo-total-acumulado').textContent = data.pontos_acumulados;
                        document.getElementById('resumo-pontos-utilizados').textContent = data.pontos_resgatados;
                        document.getElementById('resumo-recompensas').textContent = data.total_resgates;
                        document.getElementById('resumo-pontos-expirar').textContent = '0'; // Implementação futura
                        
                        // Calcular progresso para próxima recompensa
                        const pontosNecessarios = parseInt(document.getElementById('pointsToReward').value);
                        const porcentagem = Math.min(100, Math.floor((data.pontos_atuais % pontosNecessarios) / pontosNecessarios * 100));
                        const pontosFaltantes = pontosNecessarios - (data.pontos_atuais % pontosNecessarios);
                        
                        document.getElementById('progress-bar').style.width = porcentagem + '%';
                        document.getElementById('progress-bar').setAttribute('aria-valuenow', porcentagem);
                        document.getElementById('pontos-proxima-recompensa').textContent = 
                            `${pontosFaltantes} pontos para próxima recompensa`;
                        
                        // Preencher o histórico
                        const historicoBody = document.getElementById('historico-pontos-body');
                        historicoBody.innerHTML = '';
                        
                        if (data.historico && data.historico.length > 0) {
                            data.historico.forEach((item, index) => {
                                const row = document.createElement('tr');
                                
                                const dataCell = document.createElement('td');
                                dataCell.textContent = item.data;
                                row.appendChild(dataCell);
                                
                                const descricaoCell = document.createElement('td');
                                descricaoCell.textContent = item.descricao;
                                row.appendChild(descricaoCell);
                                
                                const pontosCell = document.createElement('td');
                                pontosCell.innerHTML = `<span class="badge ${item.tipo_operacao === 'credito' ? 'bg-success' : 'bg-danger'}">
                                    ${item.tipo_operacao === 'credito' ? '+' : '-'}${item.pontos}
                                </span>`;
                                row.appendChild(pontosCell);
                                
                                const saldoCell = document.createElement('td');
                                saldoCell.textContent = item.saldo;
                                row.appendChild(saldoCell);
                                
                                historicoBody.appendChild(row);
                            });
                        } else {
                            const row = document.createElement('tr');
                            const cell = document.createElement('td');
                            cell.colSpan = 4;
                            cell.textContent = 'Nenhum histórico encontrado';
                            cell.className = 'text-center';
                            row.appendChild(cell);
                            historicoBody.appendChild(row);
                        }
                        
                        // Esconder loading e mostrar detalhes
                        document.querySelector('.spinner-border').parentElement.style.display = 'none';
                        document.getElementById('loyalty-details').style.display = 'block';
                    } else {
                        alert('Erro ao carregar os dados: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Ocorreu um erro ao carregar os dados. Por favor, tente novamente.');
                });
        });
    });
    
    // Pré-selecionar cliente para adicionar pontos
    document.querySelectorAll('.add-points').forEach(btn => {
        btn.addEventListener('click', function() {
            const clienteId = this.getAttribute('data-id');
            document.getElementById('clientSelect').value = clienteId;
        });
    });
    
    // Pré-preencher dados para resgate de recompensa
    document.querySelectorAll('.redeem-points').forEach(btn => {
        btn.addEventListener('click', function() {
            const clienteId = this.getAttribute('data-id');
            const clienteNome = this.getAttribute('data-nome');
            const clientePontos = this.getAttribute('data-pontos');
            
            document.getElementById('redeemClient').value = clienteNome;
            document.getElementById('availablePoints').value = clientePontos;
            document.getElementById('cliente_id').value = clienteId;
            
            // Definir pontos a utilizar com base na recompensa selecionada
            const rewardSelect = document.getElementById('rewardType');
            if (rewardSelect.options.length > 0) {
                const pontosNecessarios = rewardSelect.options[rewardSelect.selectedIndex].getAttribute('data-pontos');
                document.getElementById('pointsToUse').value = pontosNecessarios;
            }
        });
    });
    
    // Atualizar pontos a utilizar quando a recompensa é alterada
    document.getElementById('rewardType').addEventListener('change', function() {
        const pontosNecessarios = this.options[this.selectedIndex].getAttribute('data-pontos');
        document.getElementById('pointsToUse').value = pontosNecessarios;
    });
    
    // Submit do formulário de adicionar pontos
    document.getElementById('submit-add-points').addEventListener('click', function() {
        document.getElementById('addPointsForm').submit();
    });
    
    // Submit do formulário de resgate de recompensa
    document.getElementById('submit-redeem-points').addEventListener('click', function() {
        document.getElementById('redeemPointsForm').submit();
    });
});
