<?php
session_start();

// Verifica se o usuário está logado e é profissional
if(!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'Profissional'){
    header('Location: login.php');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
include_once('../Conexao/conexao.php');

$profissional_id = $_SESSION['id_usuario'];
$mensagem = '';
$tipo_mensagem = '';

// Processar formulário de configuração
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        if (isset($_POST['salvar_horarios'])) {
            // Preparar dados para a tabela unificada
            $dados_agenda = [
                'profissional_id' => $profissional_id,
                'intervalo_agendamentos' => $_POST['intervalo_agendamentos'] ?? 30
            ];
            
            // Adicionar configurações por dia da semana
            $dias_semana = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];
            
            foreach ($dias_semana as $dia) {
                $dados_agenda["{$dia}_trabalha"] = isset($_POST["trabalha_$dia"]) ? 1 : 0;
                $dados_agenda["{$dia}_inicio"] = $_POST["inicio_$dia"] ?? '08:00:00';
                $dados_agenda["{$dia}_fim"] = $_POST["fim_$dia"] ?? '18:00:00';
                $dados_agenda["{$dia}_intervalo_inicio"] = $_POST["intervalo_inicio_$dia"] ?? '12:00:00';
                $dados_agenda["{$dia}_intervalo_fim"] = $_POST["intervalo_fim_$dia"] ?? '13:00:00';
            }
            
            // Verificar se já existe configuração
            $stmt = $pdo->prepare("SELECT id_agenda FROM profissional_agenda WHERE profissional_id = ?");
            $stmt->execute([$profissional_id]);
            $config_existente = $stmt->fetch();
            
            if ($config_existente) {
                // Atualizar configuração existente
                $campos_update = [];
                $valores_update = [];
                
                foreach ($dados_agenda as $campo => $valor) {
                    if ($campo !== 'profissional_id') {
                        $campos_update[] = "$campo = ?";
                        $valores_update[] = $valor;
                    }
                }
                $valores_update[] = $profissional_id;
                
                $sql = "UPDATE profissional_agenda SET " . implode(', ', $campos_update) . " WHERE profissional_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($valores_update);
            } else {
                // Inserir nova configuração
                $campos = array_keys($dados_agenda);
                $placeholders = array_fill(0, count($campos), '?');
                
                $sql = "INSERT INTO profissional_agenda (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_values($dados_agenda));
            }
            
            $mensagem = "Configurações de horário salvas com sucesso!";
            $tipo_mensagem = "success";
        }
        
        if (isset($_POST['bloquear_dia'])) {
            // Bloquear dia específico
            $data_bloqueio = $_POST['data_bloqueio'];
            $motivo = $_POST['motivo_bloqueio'] ?? 'Dia bloqueado';
            
            $stmt = $pdo->prepare("INSERT INTO dias_bloqueados (profissional_id, data_bloqueio, motivo) VALUES (?, ?, ?)");
            $stmt->execute([$profissional_id, $data_bloqueio, $motivo]);
            
            $mensagem = "Dia bloqueado com sucesso!";
            $tipo_mensagem = "success";
        }
        
        if (isset($_POST['desbloquear_dia'])) {
            // Desbloquear dia
            $id_bloqueio = $_POST['id_bloqueio'];
            
            $stmt = $pdo->prepare("DELETE FROM dias_bloqueados WHERE id = ? AND profissional_id = ?");
            $stmt->execute([$id_bloqueio, $profissional_id]);
            
            $mensagem = "Dia desbloqueado com sucesso!";
            $tipo_mensagem = "success";
        }
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollback();
        $mensagem = "Erro ao salvar configurações: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Buscar configurações existentes da tabela unificada
$stmt = $pdo->prepare("SELECT * FROM profissional_agenda WHERE profissional_id = ?");
$stmt->execute([$profissional_id]);
$agenda_config = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não existir, criar configuração padrão
if (!$agenda_config) {
    $agenda_config = [
        'intervalo_agendamentos' => 30,
        'segunda_trabalha' => 1, 'segunda_inicio' => '08:00:00', 'segunda_fim' => '18:00:00', 'segunda_intervalo_inicio' => '12:00:00', 'segunda_intervalo_fim' => '13:00:00',
        'terca_trabalha' => 1, 'terca_inicio' => '08:00:00', 'terca_fim' => '18:00:00', 'terca_intervalo_inicio' => '12:00:00', 'terca_intervalo_fim' => '13:00:00',
        'quarta_trabalha' => 1, 'quarta_inicio' => '08:00:00', 'quarta_fim' => '18:00:00', 'quarta_intervalo_inicio' => '12:00:00', 'quarta_intervalo_fim' => '13:00:00',
        'quinta_trabalha' => 1, 'quinta_inicio' => '08:00:00', 'quinta_fim' => '18:00:00', 'quinta_intervalo_inicio' => '12:00:00', 'quinta_intervalo_fim' => '13:00:00',
        'sexta_trabalha' => 1, 'sexta_inicio' => '08:00:00', 'sexta_fim' => '18:00:00', 'sexta_intervalo_inicio' => '12:00:00', 'sexta_intervalo_fim' => '13:00:00',
        'sabado_trabalha' => 1, 'sabado_inicio' => '08:00:00', 'sabado_fim' => '17:00:00', 'sabado_intervalo_inicio' => '12:00:00', 'sabado_intervalo_fim' => '13:00:00',
        'domingo_trabalha' => 0, 'domingo_inicio' => '08:00:00', 'domingo_fim' => '17:00:00', 'domingo_intervalo_inicio' => '12:00:00', 'domingo_intervalo_fim' => '13:00:00'
    ];
}

// Buscar dias bloqueados
$stmt = $pdo->prepare("SELECT * FROM dias_bloqueados WHERE profissional_id = ? AND data_bloqueio >= CURDATE() ORDER BY data_bloqueio");
$stmt->execute([$profissional_id]);
$dias_bloqueados = $stmt->fetchAll(PDO::FETCH_ASSOC);

include_once('topo_sistema_profissional.php');
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0"><i class="bi bi-calendar-week text-primary me-2"></i>Configurar Agenda</h2>
                    <p class="text-muted mb-0">Configure seus horários de trabalho e bloqueios</p>
                </div>
                <a href="sistema_profissional.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Voltar
                </a>
            </div>

            <!-- Mensagem -->
            <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="row">
                <!-- Configuração de Horários -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-clock me-2"></i>Horários de Trabalho
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">
                                            <i class="bi bi-stopwatch me-1"></i>Intervalo entre Agendamentos
                                        </label>
                                        <select name="intervalo_agendamentos" class="form-select">
                                            <option value="15" <?php echo $agenda_config['intervalo_agendamentos'] == 15 ? 'selected' : ''; ?>>15 minutos</option>
                                            <option value="30" <?php echo $agenda_config['intervalo_agendamentos'] == 30 ? 'selected' : ''; ?>>30 minutos</option>
                                            <option value="45" <?php echo $agenda_config['intervalo_agendamentos'] == 45 ? 'selected' : ''; ?>>45 minutos</option>
                                            <option value="60" <?php echo $agenda_config['intervalo_agendamentos'] == 60 ? 'selected' : ''; ?>>1 hora</option>
                                        </select>
                                        <small class="text-muted">Tempo mínimo entre um agendamento e outro</small>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="15%">Dia da Semana</th>
                                                <th width="10%">Trabalha</th>
                                                <th width="15%">Início</th>
                                                <th width="15%">Fim</th>
                                                <th width="15%">Intervalo Início</th>
                                                <th width="15%">Intervalo Fim</th>
                                                <th width="15%">Observações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $dias = [
                                                'segunda' => 'Segunda-feira',
                                                'terca' => 'Terça-feira', 
                                                'quarta' => 'Quarta-feira',
                                                'quinta' => 'Quinta-feira',
                                                'sexta' => 'Sexta-feira',
                                                'sabado' => 'Sábado',
                                                'domingo' => 'Domingo'
                                            ];
                                            
                                            foreach ($dias as $dia_key => $dia_nome):
                                            ?>
                                            <tr>
                                                <td class="fw-bold text-primary"><?php echo $dia_nome; ?></td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input dia-switch" type="checkbox" 
                                                               name="trabalha_<?php echo $dia_key; ?>" 
                                                               id="trabalha_<?php echo $dia_key; ?>"
                                                               <?php echo $agenda_config["{$dia_key}_trabalha"] ? 'checked' : ''; ?>
                                                               onchange="toggleDiaFields('<?php echo $dia_key; ?>')">
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="time" class="form-control form-control-sm dia-field" 
                                                           name="inicio_<?php echo $dia_key; ?>" 
                                                           id="inicio_<?php echo $dia_key; ?>"
                                                           value="<?php echo substr($agenda_config["{$dia_key}_inicio"], 0, 5); ?>"
                                                           <?php echo !$agenda_config["{$dia_key}_trabalha"] ? 'disabled' : ''; ?>>
                                                </td>
                                                <td>
                                                    <input type="time" class="form-control form-control-sm dia-field" 
                                                           name="fim_<?php echo $dia_key; ?>" 
                                                           id="fim_<?php echo $dia_key; ?>"
                                                           value="<?php echo substr($agenda_config["{$dia_key}_fim"], 0, 5); ?>"
                                                           <?php echo !$agenda_config["{$dia_key}_trabalha"] ? 'disabled' : ''; ?>>
                                                </td>
                                                <td>
                                                    <input type="time" class="form-control form-control-sm dia-field" 
                                                           name="intervalo_inicio_<?php echo $dia_key; ?>" 
                                                           id="intervalo_inicio_<?php echo $dia_key; ?>"
                                                           value="<?php echo substr($agenda_config["{$dia_key}_intervalo_inicio"], 0, 5); ?>"
                                                           <?php echo !$agenda_config["{$dia_key}_trabalha"] ? 'disabled' : ''; ?>>
                                                </td>
                                                <td>
                                                    <input type="time" class="form-control form-control-sm dia-field" 
                                                           name="intervalo_fim_<?php echo $dia_key; ?>" 
                                                           id="intervalo_fim_<?php echo $dia_key; ?>"
                                                           value="<?php echo substr($agenda_config["{$dia_key}_intervalo_fim"], 0, 5); ?>"
                                                           <?php echo !$agenda_config["{$dia_key}_trabalha"] ? 'disabled' : ''; ?>>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php if ($agenda_config["{$dia_key}_trabalha"]): ?>
                                                        <i class="bi bi-check-circle text-success"></i> Ativo
                                                        <?php else: ?>
                                                        <i class="bi bi-x-circle text-danger"></i> Inativo
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" name="salvar_horarios" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i>Salvar Configurações
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Bloqueio de Dias -->
                <div class="col-lg-4">
                    <!-- Bloquear Novo Dia -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-gradient-dark text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-calendar-x me-2"></i>Bloquear Dia
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Data</label>
                                    <input type="date" name="data_bloqueio" class="form-control" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Motivo</label>
                                    <select name="motivo_bloqueio" class="form-select">
                                        <option value="Férias">Férias</option>
                                        <option value="Falta">Falta</option>
                                        <option value="Compromisso pessoal">Compromisso pessoal</option>
                                        <option value="Manutenção">Manutenção</option>
                                        <option value="Outro">Outro</option>
                                    </select>
                                </div>
                                <button type="submit" name="bloquear_dia" class="btn btn-danger btn-sm w-100">
                                    <i class="bi bi-ban me-1"></i>Bloquear Dia
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Dias Bloqueados -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient-dark text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-list-ul me-2"></i>Dias Bloqueados
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($dias_bloqueados)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($dias_bloqueados as $bloqueio): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo date('d/m/Y', strtotime($bloqueio['data_bloqueio'])); ?></h6>
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle me-1"></i><?php echo htmlspecialchars($bloqueio['motivo']); ?>
                                        </small>
                                    </div>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_bloqueio" value="<?php echo $bloqueio['id']; ?>">
                                        <button type="submit" name="desbloquear_dia" class="btn btn-outline-success btn-sm"
                                                onclick="return confirm('Desbloquear este dia?')">
                                            <i class="bi bi-unlock"></i>
                                        </button>
                                    </form>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-check display-1 text-muted"></i>
                                <h6 class="text-muted mt-2">Nenhum dia bloqueado</h6>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumo das Configurações -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="bi bi-info-circle me-2"></i>Resumo das Configurações
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary">Dias de Trabalho:</h6>
                                    <ul class="list-unstyled">
                                        <?php foreach ($dias as $dia_key => $dia_nome): 
                                            if ($agenda_config["{$dia_key}_trabalha"]):
                                        ?>
                                        <li class="mb-1">
                                            <i class="bi bi-check-circle text-success me-2"></i>
                                            <strong><?php echo $dia_nome; ?>:</strong> 
                                            <?php echo substr($agenda_config["{$dia_key}_inicio"], 0, 5); ?> às <?php echo substr($agenda_config["{$dia_key}_fim"], 0, 5); ?>
                                            <br><small class="text-muted ms-4">Intervalo: <?php echo substr($agenda_config["{$dia_key}_intervalo_inicio"], 0, 5); ?> às <?php echo substr($agenda_config["{$dia_key}_intervalo_fim"], 0, 5); ?></small>
                                        </li>
                                        <?php endif; endforeach; ?>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-primary">Configurações Gerais:</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-stopwatch text-info me-2"></i><strong>Intervalo entre agendamentos:</strong> <?php echo $agenda_config['intervalo_agendamentos']; ?> minutos</li>
                                        <li><i class="bi bi-calendar-x text-warning me-2"></i><strong>Dias bloqueados:</strong> <?php echo count($dias_bloqueados); ?> dias</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-dark {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
}

.card {
    border: none;
    border-radius: 10px;
}

.form-control:focus, .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    background-color: #f8f9fa;
}

.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.dia-field:disabled {
    background-color: #f8f9fa;
    opacity: 0.6;
}

.list-group-item {
    border-left: none;
    border-right: none;
}

.list-group-item:first-child {
    border-top: none;
}

.list-group-item:last-child {
    border-bottom: none;
}
</style>

<script>
function toggleDiaFields(dia) {
    const checkbox = document.getElementById('trabalha_' + dia);
    const fields = document.querySelectorAll(`input[name*="${dia}"]`);
    
    fields.forEach(field => {
        if (field.type !== 'checkbox') {
            field.disabled = !checkbox.checked;
            if (!checkbox.checked) {
                field.style.backgroundColor = '#f8f9fa';
                field.style.opacity = '0.6';
            } else {
                field.style.backgroundColor = '';
                field.style.opacity = '';
            }
        }
    });
}

// Inicializar campos ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    const dias = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];
    dias.forEach(dia => {
        toggleDiaFields(dia);
    });
});

// Validação de horários
document.querySelectorAll('input[type="time"]').forEach(input => {
    input.addEventListener('change', function() {
        const diaKey = this.name.split('_').slice(-1)[0];
        const inicio = document.querySelector(`input[name="inicio_${diaKey}"]`);
        const fim = document.querySelector(`input[name="fim_${diaKey}"]`);
        const intervaloInicio = document.querySelector(`input[name="intervalo_inicio_${diaKey}"]`);
        const intervaloFim = document.querySelector(`input[name="intervalo_fim_${diaKey}"]`);
        
        // Validar se hora de fim é maior que início
        if (inicio.value && fim.value && inicio.value >= fim.value) {
            alert('A hora de fim deve ser maior que a hora de início!');
            fim.value = '';
            return;
        }
        
        // Validar intervalo
        if (intervaloInicio.value && intervaloFim.value) {
            if (intervaloInicio.value >= intervaloFim.value) {
                alert('A hora de fim do intervalo deve ser maior que a hora de início!');
                intervaloFim.value = '';
                return;
            }
            
            if (inicio.value && intervaloInicio.value <= inicio.value) {
                alert('O intervalo deve começar após o horário de início do trabalho!');
                intervaloInicio.value = '';
                return;
            }
            
            if (fim.value && intervaloFim.value >= fim.value) {
                alert('O intervalo deve terminar antes do horário de fim do trabalho!');
                intervaloFim.value = '';
                return;
            }
        }
    });
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Adiciona espaço no final da página -->
<div class="mb-5"></div>
