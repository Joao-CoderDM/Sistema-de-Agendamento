document.addEventListener('DOMContentLoaded', function() {
    // Alternar entre abas usando a lista lateral
    document.querySelectorAll('.list-group-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remover a classe active de todos os itens
            document.querySelectorAll('.list-group-item').forEach(i => {
                i.classList.remove('active');
            });
            
            // Adicionar a classe active ao item clicado
            this.classList.add('active');
            
            // Mostrar o conteúdo correspondente
            const target = this.getAttribute('href').substring(1);
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            document.getElementById(target).classList.add('show', 'active');
        });
    });
    
    // Checkbox para confirmar solicitação de dados
    const checkboxSolicitacao = document.getElementById('confirmar_solicitacao');
    const btnConfirmarSolicitacao = document.getElementById('btn-confirmar-solicitacao');
    
    if (checkboxSolicitacao && btnConfirmarSolicitacao) {
        checkboxSolicitacao.addEventListener('change', function() {
            btnConfirmarSolicitacao.disabled = !this.checked;
        });
    }
    
    // Checkbox para confirmar exclusão de conta
    const checkboxExclusao = document.getElementById('confirmar_exclusao');
    const btnConfirmarExclusao = document.getElementById('btn-confirmar-exclusao');
    
    if (checkboxExclusao && btnConfirmarExclusao) {
        checkboxExclusao.addEventListener('change', function() {
            btnConfirmarExclusao.disabled = !this.checked;
        });
    }
    
    // Botão para confirmar solicitação de dados
    if (btnConfirmarSolicitacao) {
        btnConfirmarSolicitacao.addEventListener('click', function() {
            alert('Sua solicitação foi registrada com sucesso! Você receberá seus dados no e-mail cadastrado em até 48 horas.');
            const modal = bootstrap.Modal.getInstance(document.getElementById('solicitarDadosModal'));
            modal.hide();
        });
    }
    
    // Botão para confirmar exclusão de conta
    if (btnConfirmarExclusao) {
        btnConfirmarExclusao.addEventListener('click', function() {
            alert('Conta excluída com sucesso! Você será redirecionado para a página inicial.');
            window.location.href = 'index.php';
        });
    }
});
