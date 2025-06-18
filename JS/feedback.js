document.addEventListener('DOMContentLoaded', function() {
    console.log('Feedback JS carregado');
    
    // Buscar o formulário de feedback
    const feedbackForm = document.getElementById('feedbackForm');
    
    if (feedbackForm) {
        console.log('Formulário de feedback encontrado');
        
        // Remover todos os event listeners existentes para evitar duplicação
        const newForm = feedbackForm.cloneNode(true);
        feedbackForm.parentNode.replaceChild(newForm, feedbackForm);
        
        // Interceptar o envio do formulário - APENAS UM LISTENER
        newForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Formulário de feedback enviado');
            
            // Verificar se já está sendo enviado para evitar duplo envio
            if (this.dataset.sending === 'true') {
                console.log('Envio já em andamento, ignorando...');
                return;
            }
            
            // Marcar como enviando
            this.dataset.sending = 'true';
            
            // Criar FormData com os dados do formulário
            const formData = new FormData(this);
            
            // Debug: mostrar dados que serão enviados
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }
            
            // Desabilitar botão de envio para evitar duplo clique
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Enviando...';
            
            // Enviar dados via fetch
            fetch('../Validar_Forms/valida_feedback.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                console.log('Resposta recebida:', response);
                console.log('Status:', response.status);
                console.log('Content-Type:', response.headers.get('content-type'));
                
                // Verificar se a resposta é um redirecionamento (status 3xx)
                if (response.redirected) {
                    console.log('Redirecionamento detectado para:', response.url);
                    // Para redirecionamentos, considerar como sucesso
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Sucesso!',
                            text: 'Feedback enviado com sucesso!',
                            icon: 'success',
                            confirmButtonColor: '#28a745'
                        }).then(() => {
                            window.location.href = response.url;
                        });
                    } else {
                        alert('Feedback enviado com sucesso!');
                        window.location.href = response.url;
                    }
                    return;
                }
                
                // Verificar se a resposta é ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Verificar Content-Type para decidir como processar
                const contentType = response.headers.get('content-type');
                console.log('Content-Type da resposta:', contentType);
                
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    return response.text();
                }
            })
            .then(data => {
                console.log('Dados processados:', data);
                console.log('Tipo dos dados:', typeof data);
                
                // Se é um objeto JSON
                if (typeof data === 'object' && data !== null) {
                    if (data.status === 'sucesso') {
                        // Sucesso
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Sucesso!',
                                text: data.mensagem || 'Feedback enviado com sucesso!',
                                icon: 'success',
                                confirmButtonColor: '#28a745'
                            }).then(() => {
                                // Limpar formulário
                                newForm.reset();
                                
                                // Redirecionamento se especificado
                                if (data.redirect) {
                                    window.location.href = data.redirect;
                                } else {
                                    window.location.reload();
                                }
                            });
                        } else {
                            alert(data.mensagem || 'Feedback enviado com sucesso!');
                            newForm.reset();
                            
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                window.location.reload();
                            }
                        }
                    } else {
                        // Erro retornado pelo servidor
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Erro!',
                                text: data.mensagem || 'Erro ao enviar feedback',
                                icon: 'error',
                                confirmButtonColor: '#dc3545'
                            });
                        } else {
                            alert(data.mensagem || 'Erro ao enviar feedback');
                        }
                    }
                } else if (typeof data === 'string') {
                    console.log('Resposta como string:', data.substring(0, 200) + '...');
                    
                    // Verificar se contém indicadores de sucesso
                    if (data.includes('sucesso=1') || 
                        data.includes('sucesso_feedback') || 
                        data.includes('Obrigado pelo seu feedback') ||
                        data.includes('registrada com sucesso')) {
                        
                        console.log('Sucesso detectado na resposta');
                        
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Sucesso!',
                                text: 'Feedback enviado com sucesso!',
                                icon: 'success',
                                confirmButtonColor: '#28a745'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            alert('Feedback enviado com sucesso!');
                            window.location.reload();
                        }
                    } else if (data.includes('<!DOCTYPE html>') || data.includes('<html>')) {
                        // É uma página HTML completa - assumir sucesso se não há erro visível
                        console.log('Resposta contém HTML completo');
                        
                        // Verificar se há mensagens de erro no HTML
                        if (data.includes('erro_feedback') || data.includes('alert-danger')) {
                            console.log('Erro detectado no HTML');
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Erro!',
                                    text: 'Erro ao processar feedback. Verifique os dados e tente novamente.',
                                    icon: 'error',
                                    confirmButtonColor: '#dc3545'
                                });
                            } else {
                                alert('Erro ao processar feedback. Verifique os dados e tente novamente.');
                            }
                        } else {
                            // Assumir sucesso
                            console.log('Assumindo sucesso - recarregando página');
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Sucesso!',
                                    text: 'Feedback enviado com sucesso!',
                                    icon: 'success',
                                    confirmButtonColor: '#28a745'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                alert('Feedback enviado com sucesso!');
                                window.location.reload();
                            }
                        }
                    } else {
                        // Resposta de texto simples - assumir sucesso
                        console.log('Resposta de texto simples - assumindo sucesso');
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Sucesso!',
                                text: 'Feedback enviado com sucesso!',
                                icon: 'success',
                                confirmButtonColor: '#28a745'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            alert('Feedback enviado com sucesso!');
                            window.location.reload();
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Erro no envio:', error);
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Erro!',
                        text: 'Erro ao enviar feedback. Tente novamente.',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                } else {
                    alert('Erro ao enviar feedback. Tente novamente.');
                }
            })
            .finally(() => {
                // Reabilitar botão
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                
                // Remover marcação de envio
                newForm.dataset.sending = 'false';
            });
        });
    } else {
        console.log('Formulário de feedback não encontrado');
    }
    
    // Contador de caracteres para mensagem
    const mensagemTextarea = document.getElementById('mensagem');
    const contadorCaracteres = document.querySelector('.contador-caracteres');
    const caracteresRestantes = document.querySelector('.caracteres-restantes');
    
    if (mensagemTextarea) {
        console.log('Textarea de mensagem encontrado');
        
        function atualizarContadores() {
            const caracteresDigitados = mensagemTextarea.value.length;
            const caracteresRestantesCount = 280 - caracteresDigitados;
            
            // Atualizar contador mínimo
            if (contadorCaracteres) {
                if (caracteresDigitados < 10) {
                    contadorCaracteres.textContent = `${caracteresDigitados}/10 caracteres mínimos`;
                    contadorCaracteres.className = 'text-danger contador-caracteres';
                } else {
                    contadorCaracteres.textContent = `✓ Mínimo atingido (${caracteresDigitados} caracteres)`;
                    contadorCaracteres.className = 'text-success contador-caracteres';
                }
            }
            
            // Atualizar contador máximo
            if (caracteresRestantes) {
                caracteresRestantes.textContent = `${caracteresRestantesCount} caracteres restantes`;
                if (caracteresRestantesCount < 50) {
                    caracteresRestantes.className = 'text-warning caracteres-restantes';
                } else {
                    caracteresRestantes.className = 'text-muted caracteres-restantes';
                }
            }
            
            // Controlar estado do botão de envio
            const submitBtn = document.querySelector('.btn-feedback-submit');
            if (submitBtn) {
                if (caracteresDigitados < 10) {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.6';
                } else {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                }
            }
        }

        mensagemTextarea.addEventListener('input', atualizarContadores);
        // Verificar estado inicial
        atualizarContadores();
    } else {
        console.log('Textarea de mensagem não encontrado');
    }
});
