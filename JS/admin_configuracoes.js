/**
 * Script para as funcionalidades da página de configurações do sistema
 */

document.addEventListener('DOMContentLoaded', function() {
    // Máscara para telefone
    configurarMascaraTelefone();
    
    // Configurar funcionalidade de backup
    configurarBackup();
    
    // Validação de formulário
    configurarValidacaoFormulario();
});

/**
 * Configura a máscara para o campo de telefone
 */
function configurarMascaraTelefone() {
    const telefoneInput = document.getElementById('telefone_contato');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 10) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{4})/, '($1) $2-');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})/, '($1) ');
            }
            
            e.target.value = value;
        });
    }
}

/**
 * Configura a funcionalidade de backup
 */
function configurarBackup() {
    const btnBackup = document.getElementById('btnBackup');
    if (btnBackup) {
        btnBackup.addEventListener('click', function() {
            realizarBackup();
        });
    }
}

/**
 * Realiza o backup do sistema
 */
function realizarBackup() {
    const backupStatus = document.getElementById('backupStatus');
    backupStatus.innerHTML = '<div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Realizando backup...';
    
    // Simular processo de backup (em produção, isso seria uma chamada AJAX real)
    setTimeout(function() {
        backupStatus.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i> Backup realizado com sucesso!</span>';
        
        // Atualizar lista de backups
        atualizarListaBackups();
    }, 2000);
}

/**
 * Atualiza a lista de backups na interface
 */
function atualizarListaBackups() {
    const backupList = document.getElementById('backupList');
    const date = new Date();
    const formattedDate = date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR');
    
    // Criar uma nova linha de backup
    const novaLinha = document.createElement('tr');
    novaLinha.innerHTML = `
        <td>${formattedDate}</td>
        <td>2.4 MB</td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="baixarBackup('backup_${Date.now()}.sql')">
                <i class="bi bi-download"></i> Baixar
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="excluirBackup(this)">
                <i class="bi bi-trash"></i> Excluir
            </button>
        </td>
    `;
    
    // Remover a mensagem de "Nenhum backup encontrado" se existir
    const semBackupRow = backupList.querySelector('tr td[colspan="3"]');
    if (semBackupRow) {
        semBackupRow.closest('tr').remove();
    }
    
    // Adicionar a nova linha no início da tabela
    backupList.insertBefore(novaLinha, backupList.firstChild);
}

/**
 * Simulação de download de backup
 * @param {string} filename - Nome do arquivo para download
 */
function baixarBackup(filename) {
    alert(`Download do backup ${filename} iniciado.`);
    // Em uma implementação real, isso redirecionaria para um endpoint que gera o download
}

/**
 * Remove um backup da lista
 * @param {HTMLElement} button - Botão de exclusão que foi clicado
 */
function excluirBackup(button) {
    if (confirm('Tem certeza que deseja excluir este backup?')) {
        const row = button.closest('tr');
        row.classList.add('fade-out');
        
        setTimeout(() => {
            row.remove();
            
            // Se não houver mais backups, mostrar a mensagem de "Nenhum backup encontrado"
            const backupList = document.getElementById('backupList');
            if (backupList.children.length === 0) {
                const semBackupRow = document.createElement('tr');
                semBackupRow.innerHTML = '<td colspan="3" class="text-center">Nenhum backup encontrado.</td>';
                backupList.appendChild(semBackupRow);
            }
        }, 300);
    }
}

/**
 * Configura a validação do formulário
 */
function configurarValidacaoFormulario() {
    const configForm = document.getElementById('configForm');
    if (configForm) {
        configForm.addEventListener('submit', function(e) {
            const nomeEstabelecimento = document.getElementById('nome_estabelecimento');
            if (!nomeEstabelecimento.value.trim()) {
                e.preventDefault();
                alert('O nome do estabelecimento é obrigatório.');
                nomeEstabelecimento.focus();
                return false;
            }
            
            // Outras validações podem ser adicionadas aqui
            
            return true;
        });
    }
}
