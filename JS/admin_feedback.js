document.addEventListener('DOMContentLoaded', function() {
    // Passar dados para o modal de visualização
    document.querySelectorAll('.view-feedback').forEach(function(button) {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nome = this.getAttribute('data-nome');
            const email = this.getAttribute('data-email');
            const avaliacao = this.getAttribute('data-avaliacao');
            const mensagem = this.getAttribute('data-mensagem');
            const data = this.getAttribute('data-data');
            const resposta = this.getAttribute('data-resposta');
            
            document.getElementById('modal-id-feedback').value = id;
            document.getElementById('modal-nome').textContent = nome;
            document.getElementById('modal-email').textContent = email;
            document.getElementById('modal-data').textContent = data;
            document.getElementById('modal-mensagem').textContent = mensagem;
            
            // Renderizar estrelas para a avaliação
            let estrelasHtml = '';
            for (let i = 1; i <= 5; i++) {
                const estrela = (i <= avaliacao) ? 'bi-star-fill' : 'bi-star';
                estrelasHtml += `<i class="bi ${estrela} text-warning"></i>`;
            }
            document.getElementById('modal-avaliacao').innerHTML = estrelasHtml;
            
            // Tratar resposta existente
            if (resposta && resposta.trim() !== '') {
                document.getElementById('modal-resposta').value = resposta;
                document.getElementById('resposta-anterior').textContent = resposta;
                document.getElementById('resposta-anterior-container').style.display = 'block';
            } else {
                document.getElementById('modal-resposta').value = '';
                document.getElementById('resposta-anterior-container').style.display = 'none';
            }
        });
    });
    
    // Passar id para o modal de exclusão
    document.querySelectorAll('.delete-feedback').forEach(function(button) {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            document.getElementById('delete-id-feedback').value = id;
        });
    });
});
