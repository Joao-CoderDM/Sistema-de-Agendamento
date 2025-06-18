document.addEventListener('DOMContentLoaded', function() {
    // Manipular modal de resposta
    const responderModal = document.getElementById('responderModal');
    if (responderModal) {
        responderModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nome = button.getAttribute('data-nome');
            const avaliacao = button.getAttribute('data-avaliacao');
            const comentario = button.getAttribute('data-comentario');
            const data = button.getAttribute('data-data');
            const servico = button.getAttribute('data-servico');
            const resposta = button.getAttribute('data-resposta');
            
            // Atualizar campos do modal
            document.getElementById('id_avaliacao').value = id;
            document.getElementById('modal-nome-cliente').textContent = nome;
            document.getElementById('modal-data-servico').textContent = data;
            document.getElementById('modal-nome-servico').textContent = servico;
            
            // Atualizar a exibição de estrelas
            const avaliacaoCliente = document.getElementById('modal-avaliacao-cliente');
            avaliacaoCliente.innerHTML = '';
            for (let i = 1; i <= 5; i++) {
                const star = document.createElement('i');
                star.classList.add('bi', i <= avaliacao ? 'bi-star-fill' : 'bi-star', 'text-warning', 'me-1');
                avaliacaoCliente.appendChild(star);
            }
            
            // Exibir comentário do cliente, se houver
            const comentarioContainer = document.getElementById('modal-comentario-container');
            const comentarioCliente = document.getElementById('modal-comentario-cliente');
            
            if (comentario && comentario.trim() !== '') {
                comentarioContainer.style.display = 'block';
                comentarioCliente.textContent = comentario;
            } else {
                comentarioContainer.style.display = 'block';
                comentarioCliente.textContent = 'Sem comentários adicionais.';
                comentarioCliente.classList.add('text-muted', 'fst-italic');
            }
            
            // Se tiver resposta anterior, mostrá-la
            if (resposta) {
                document.getElementById('resposta-anterior-container').style.display = 'block';
                document.getElementById('resposta-anterior').textContent = resposta;
                document.getElementById('resposta').value = resposta;
            } else {
                document.getElementById('resposta-anterior-container').style.display = 'none';
                document.getElementById('resposta').value = '';
            }
        });
    }
    
    // Validações de formulário
    const formResponder = document.getElementById('formResponder');
    if (formResponder) {
        formResponder.addEventListener('submit', function(event) {
            const resposta = document.getElementById('resposta').value.trim();
            if (resposta.length < 5) {
                event.preventDefault();
                alert('Por favor, escreva uma resposta com pelo menos 5 caracteres.');
            }
        });
    }
});
