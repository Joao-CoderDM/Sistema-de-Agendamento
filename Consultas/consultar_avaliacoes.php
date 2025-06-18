<?php
include_once('../Conexao/conexao.php');

// Função para depuração (só exibe se DEBUG = true)
function debug_log($message, $data = null) {
    $DEBUG = false; // Definir como true para ativar logs
    if ($DEBUG) {
        echo "<!-- DEBUG: " . $message;
        if ($data !== null) {
            echo " | Data: ";
            if (is_array($data) || is_object($data)) {
                echo json_encode($data);
            } else {
                echo $data;
            }
        }
        echo " -->\n";
    }
}

// Função para obter apenas os dois primeiros nomes
function primeirosNomes($nomeCompleto) {
    $partes = explode(' ', trim($nomeCompleto));
    
    if (count($partes) <= 2) {
        return $nomeCompleto;
    } else {
        return $partes[0] . ' ' . $partes[1];
    }
}

// Consulta para buscar avaliações com 5 estrelas
function obterAvaliacoesCincoEstrelas($limite = 9) {
    global $pdo;
    
    debug_log("Iniciando consulta de avaliações");
    
    try {
        // Verificar se há conexão com o banco
        if (!isset($pdo)) {
            debug_log("Erro na conexão PDO");
            return obterAvaliacoesExemplo($limite);
        }
        
        // Verificar se a tabela feedback existe
        $check_table = $pdo->query("SHOW TABLES LIKE 'feedback'");
        if (!$check_table || $check_table->rowCount() === 0) {
            debug_log("A tabela 'feedback' não existe no banco de dados");
            criarTabelaFeedback();
            inserirAvaliacoesExemplo();
        }
        
        // Verificar se a coluna avaliacao existe
        $check_column = $pdo->query("SHOW COLUMNS FROM feedback LIKE 'avaliacao'");
        if (!$check_column || $check_column->rowCount() === 0) {
            debug_log("A coluna 'avaliacao' não existe na tabela 'feedback'");
            $pdo->query("ALTER TABLE feedback ADD COLUMN avaliacao INT DEFAULT 5");
            $pdo->query("UPDATE feedback SET avaliacao = 5"); // Define avaliação 5 para todos os feedbacks existentes
        }
        
        // Query para buscar avaliações com 5 estrelas e texto de até 280 caracteres
        $query = "SELECT nome, mensagem, avaliacao FROM feedback 
                  WHERE avaliacao = 5 AND LENGTH(mensagem) <= 280 
                  ORDER BY RAND() LIMIT :limite";
        
        $stmt = $pdo->prepare($query);
        if (!$stmt) {
            debug_log("Erro ao preparar query");
            return obterAvaliacoesExemplo($limite);
        }
        
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        $avaliacoes = [];
        
        if ($stmt->rowCount() > 0) {
            debug_log("Encontradas " . $stmt->rowCount() . " avaliações");
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Processar o nome para obter apenas os dois primeiros
                $row['nome'] = primeirosNomes($row['nome']);
                $avaliacoes[] = $row;
            }
            return $avaliacoes;
        } else {
            debug_log("Nenhuma avaliação encontrada no banco, inserindo exemplos");
            inserirAvaliacoesExemplo();
            return obterAvaliacoesExemplo($limite);
        }
        
    } catch (PDOException $e) {
        debug_log("Exceção PDO capturada", $e->getMessage());
        return obterAvaliacoesExemplo($limite);
    } catch (Exception $e) {
        debug_log("Exceção genérica capturada", $e->getMessage());
        return obterAvaliacoesExemplo($limite);
    }
}

// Função para contar total de avaliações de 5 estrelas
function contarAvaliacoesCincoEstrelas() {
    global $pdo;
    
    if (!isset($pdo)) {
        debug_log("Erro na conexão PDO");
        return 0;
    }
    
    try {
        $query = "SELECT COUNT(*) as total FROM feedback WHERE avaliacao = 5 AND LENGTH(mensagem) <= 280";
        $result = $pdo->query($query);
        if ($result) {
            $row = $result->fetch(PDO::FETCH_ASSOC);
            return $row['total'];
        }
    } catch (Exception $e) {
        debug_log("Erro ao contar avaliações", $e->getMessage());
    }
    
    return 0;
}

// Função para criar tabela feedback se não existir
function criarTabelaFeedback() {
    global $pdo;
    
    if (!isset($pdo)) {
        return false;
    }
    
    $query = "CREATE TABLE IF NOT EXISTS feedback (
              id_feedback INT AUTO_INCREMENT PRIMARY KEY,
              nome VARCHAR(100) NOT NULL,
              email VARCHAR(100) NOT NULL,
              mensagem TEXT NOT NULL,
              avaliacao INT DEFAULT 5,
              resposta TEXT NULL,
              data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              data_resposta TIMESTAMP NULL
              )";
    
    try {
        $pdo->exec($query);
        debug_log("Tabela feedback criada ou já existente");
        return true;
    } catch (Exception $e) {
        debug_log("Erro ao criar tabela feedback", $e->getMessage());
        return false;
    }
}

// Função para inserir avaliações de exemplo
function inserirAvaliacoesExemplo() {
    global $pdo;
    
    if (!isset($pdo)) {
        return false;
    }
    
    try {
        // Verificar se já existem avaliações de 5 estrelas
        $check = $pdo->query("SELECT COUNT(*) as total FROM feedback WHERE avaliacao = 5");
        $row = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($row['total'] >= 6) {
            debug_log("Já existem avaliações 5 estrelas suficientes");
            return true;
        }
        
        // Exemplos de avaliações para inserir
        $exemplos = [
            ['Carlos Silva', 'carlos.silva@email.com', 'Atendimento excepcional! O corte ficou incrível e me senti renovado. Equipe muito atenciosa e ambiente agradável.', 5],
            ['Marina Oliveira', 'marina.oliveira@email.com', 'Primeira vez nessa barbearia e já virei cliente. Profissionalismo e qualidade impressionantes!', 5],
            ['Roberto Almeida', 'roberto.almeida@email.com', 'Meu barbeiro entendeu exatamente o que eu queria. Resultado perfeito e atendimento de primeira.', 5],
            ['Fernando Costa', 'fernando.costa@email.com', 'Profissionais muito qualificados! O resultado superou minhas expectativas. Ambiente bem cuidado e confortável.', 5],
            ['Marcelo Lima', 'marcelo.lima@email.com', 'Excelência em cada detalhe. Desde a recepção até o resultado final, tudo perfeito!', 5],
            ['Gabriel Ferreira', 'gabriel.ferreira@email.com', 'Fiz um degradê e ficou perfeito! Melhor barbearia que já fui. Ambiente moderno e barbeiros habilidosos.', 5]
        ];
        
        $query = "INSERT INTO feedback (nome, email, mensagem, avaliacao) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        
        if (!$stmt) {
            debug_log("Erro ao preparar a inserção de exemplos");
            return false;
        }
        
        $contador = 0;
        
        foreach ($exemplos as $exemplo) {
            // Verificar se esta avaliação já existe para evitar duplicatas
            $check = $pdo->prepare("SELECT id_feedback FROM feedback WHERE email = ? AND mensagem = ?");
            $check->execute([$exemplo[1], $exemplo[2]]);
            
            if ($check->rowCount() == 0) {
                $stmt->execute($exemplo);
                $contador++;
            }
        }
        
        debug_log("Inseridas $contador avaliações de exemplo");
        return true;
    } catch (Exception $e) {
        debug_log("Erro ao inserir avaliações de exemplo", $e->getMessage());
        return false;
    }
}

// Função para obter avaliações de exemplo quando o banco falha
function obterAvaliacoesExemplo($limite = 9) {
    $exemplos = [
        ['nome' => 'Carlos Silva', 'mensagem' => 'Atendimento excepcional! O corte ficou incrível e me senti renovado. Equipe muito atenciosa e ambiente agradável.', 'avaliacao' => 5],
        ['nome' => 'Marina Oliveira', 'mensagem' => 'Primeira vez nessa barbearia e já virei cliente. Profissionalismo e qualidade impressionantes!', 'avaliacao' => 5],
        ['nome' => 'Roberto Almeida', 'mensagem' => 'Meu barbeiro entendeu exatamente o que eu queria. Resultado perfeito e atendimento de primeira.', 'avaliacao' => 5],
        ['nome' => 'Fernando Costa', 'mensagem' => 'Profissionais muito qualificados! O resultado superou minhas expectativas. Ambiente bem cuidado e confortável.', 'avaliacao' => 5],
        ['nome' => 'Marcelo Lima', 'mensagem' => 'Excelência em cada detalhe. Desde a recepção até o resultado final, tudo perfeito!', 'avaliacao' => 5],
        ['nome' => 'Gabriel Ferreira', 'mensagem' => 'Fiz um degradê e ficou perfeito! Melhor barbearia que já fui. Ambiente moderno e barbeiros habilidosos.', 'avaliacao' => 5]
    ];
    
    return array_slice($exemplos, 0, min($limite, count($exemplos)));
}
?>
