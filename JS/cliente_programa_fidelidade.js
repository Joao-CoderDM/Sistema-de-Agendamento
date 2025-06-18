document.addEventListener('DOMContentLoaded', function() {
    // Lidar com o clique nos botões de resgate
    const botoesResgatar = document.querySelectorAll('.btn-resgatar');
    
    botoesResgatar.forEach(botao => {
        botao.addEventListener('click', function() {
            const idRecompensa = this.getAttribute('data-id');
            const recompensa = recompensas.find(r => r.id_recompensa == idRecompensa);
            
            if (recompensa) {
                document.querySelector('.recompensa-nome').textContent = recompensa.nome;
                document.querySelector('.recompensa-descricao').textContent = recompensa.descricao;
                document.getElementById('confirmarResgate').setAttribute('data-id', idRecompensa);
            }
        });
    });
    
    // Simular o resgate (em produção, isso seria uma requisição AJAX)
    document.getElementById('confirmarResgate').addEventListener('click', function() {
        // Gerar um código aleatório para demonstração
        const codigo = gerarCodigoAleatorio();
        document.querySelector('.codigo-resgate').textContent = codigo;
        
        // Fechar o modal de resgate
        const resgatarModal = bootstrap.Modal.getInstance(document.getElementById('resgatarModal'));
        resgatarModal.hide();
        
        // Mostrar o modal de sucesso
        setTimeout(() => {
            const sucessoModal = new bootstrap.Modal(document.getElementById('sucessoModal'));
            sucessoModal.show();
        }, 500);
    });
    
    // Função para gerar um código aleatório
    function gerarCodigoAleatorio() {
        const caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let codigo = '';
        for (let i = 0; i < 8; i++) {
            codigo += caracteres.charAt(Math.floor(Math.random() * caracteres.length));
        }
        return codigo;
    }
});
