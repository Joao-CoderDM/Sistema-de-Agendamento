-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 13/06/2025 às 16:43
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `barbd1`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `agendamentos`
--

CREATE TABLE `agendamentos` (
  `id_agendamento` int(11) NOT NULL,
  `data_agendamento` date NOT NULL,
  `hora_agendamento` time NOT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('agendado','confirmado','concluido','cancelado') NOT NULL DEFAULT 'agendado',
  `valor` decimal(10,2) NOT NULL,
  `motivo_cancelamento` text DEFAULT NULL,
  `cliente_id` int(11) NOT NULL,
  `profissional_id` int(11) NOT NULL,
  `servico_id` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `agendamentos`
--

INSERT INTO `agendamentos` (`id_agendamento`, `data_agendamento`, `hora_agendamento`, `observacoes`, `status`, `valor`, `motivo_cancelamento`, `cliente_id`, `profissional_id`, `servico_id`, `data_criacao`) VALUES
(3, '2025-06-17', '08:00:00', '', 'concluido', 100.00, NULL, 12, 11, 31, '2025-06-05 13:47:26'),
(4, '2025-06-17', '09:30:00', '', 'agendado', 150.00, NULL, 6, 11, 30, '2025-06-05 13:47:50'),
(5, '2025-06-17', '17:30:00', '', 'cancelado', 20.00, 'falta', 7, 11, 10, '2025-06-05 13:57:45'),
(6, '2025-06-17', '13:30:00', '', 'agendado', 70.00, NULL, 6, 11, 35, '2025-06-05 13:58:35');

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nome`, `descricao`, `ativo`, `data_criacao`) VALUES
(1, 'Cabelo', 'Serviços relacionados ao corte e tratamento de cabelo', 1, '2025-06-03 11:48:38'),
(2, 'Barba', 'Serviços de barba e bigode', 1, '2025-06-03 11:48:38'),
(3, 'Combo', 'Pacotes combinados de serviços', 1, '2025-06-03 11:48:38'),
(4, 'Acabamento', 'Serviços de finalização e acabamento', 1, '2025-06-03 11:48:38'),
(5, 'Tratamentos', 'Tratamentos especiais e terapêuticos', 1, '2025-06-03 11:48:38'),
(6, 'Premium', 'Serviços premium e exclusivos', 1, '2025-06-03 11:48:38'),
(7, 'Infantil', 'Serviços especializados para crianças', 1, '2025-06-03 11:48:38'),
(8, 'Noivo', 'Pacotes especiais para noivos', 1, '2025-06-03 11:48:38'),
(9, 'Relaxamento', 'Serviços de relaxamento e bem-estar', 1, '2025-06-03 11:48:38'),
(10, 'Coloração', 'Serviços de pintura e coloração', 1, '2025-06-03 11:48:38'),
(11, 'Estética', 'Serviços de estética facial e corporal', 1, '2025-06-03 11:48:38'),
(12, 'Eventos', 'Serviços para ocasiões especiais', 1, '2025-06-03 11:48:38');

-- --------------------------------------------------------

--
-- Estrutura para tabela `config_fid`
--

CREATE TABLE `config_fid` (
  `id_config` int(11) NOT NULL,
  `pontos_por_real` decimal(5,2) NOT NULL DEFAULT 1.00,
  `pontos_expiracao_dias` int(11) NOT NULL DEFAULT 365,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `config_fid`
--

INSERT INTO `config_fid` (`id_config`, `pontos_por_real`, `pontos_expiracao_dias`, `ativo`) VALUES
(1, 1.00, 365, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `dias_bloqueados`
--

CREATE TABLE `dias_bloqueados` (
  `id` int(11) NOT NULL,
  `profissional_id` int(11) NOT NULL,
  `data_bloqueio` date NOT NULL,
  `motivo` varchar(255) DEFAULT 'Dia bloqueado',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `dias_bloqueados`
--

INSERT INTO `dias_bloqueados` (`id`, `profissional_id`, `data_bloqueio`, `motivo`, `data_criacao`) VALUES
(2, 11, '2025-06-10', 'Férias', '2025-06-05 03:34:03');

-- --------------------------------------------------------

--
-- Estrutura para tabela `feedback`
--

CREATE TABLE `feedback` (
  `id_feedback` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mensagem` text NOT NULL COMMENT 'Comentário sobre o serviço',
  `avaliacao` int(11) DEFAULT 5 COMMENT 'Avaliação do serviço de 1 a 5 estrelas',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `resposta` text DEFAULT NULL,
  `data_resposta` timestamp NULL DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `agendamento_id` int(11) DEFAULT NULL,
  `avaliacao_profissional` int(11) DEFAULT 5 COMMENT 'Avaliação do profissional de 1 a 5 estrelas',
  `comentario_profissional` text DEFAULT NULL COMMENT 'Comentário específico sobre o profissional'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `fidelidade`
--

CREATE TABLE `fidelidade` (
  `id_fidelidade` int(11) NOT NULL,
  `pontos_atuais` int(11) DEFAULT 0,
  `pontos_acumulados` int(11) DEFAULT 0,
  `pontos_resgatados` int(11) DEFAULT 0,
  `config_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `fidelidade`
--

INSERT INTO `fidelidade` (`id_fidelidade`, `pontos_atuais`, `pontos_acumulados`, `pontos_resgatados`, `config_id`, `usuario_id`) VALUES
(2, 0, 0, 0, 1, 12);

-- --------------------------------------------------------

--
-- Estrutura para tabela `profissional_agenda`
--

CREATE TABLE `profissional_agenda` (
  `id_agenda` int(11) NOT NULL,
  `profissional_id` int(11) NOT NULL,
  `segunda_trabalha` tinyint(1) DEFAULT 1,
  `segunda_inicio` time DEFAULT '09:00:00',
  `segunda_fim` time DEFAULT '18:00:00',
  `segunda_intervalo_inicio` time DEFAULT '12:00:00',
  `segunda_intervalo_fim` time DEFAULT '13:00:00',
  `terca_trabalha` tinyint(1) DEFAULT 1,
  `terca_inicio` time DEFAULT '09:00:00',
  `terca_fim` time DEFAULT '18:00:00',
  `terca_intervalo_inicio` time DEFAULT '12:00:00',
  `terca_intervalo_fim` time DEFAULT '13:00:00',
  `quarta_trabalha` tinyint(1) DEFAULT 1,
  `quarta_inicio` time DEFAULT '09:00:00',
  `quarta_fim` time DEFAULT '18:00:00',
  `quarta_intervalo_inicio` time DEFAULT '12:00:00',
  `quarta_intervalo_fim` time DEFAULT '13:00:00',
  `quinta_trabalha` tinyint(1) DEFAULT 1,
  `quinta_inicio` time DEFAULT '09:00:00',
  `quinta_fim` time DEFAULT '18:00:00',
  `quinta_intervalo_inicio` time DEFAULT '12:00:00',
  `quinta_intervalo_fim` time DEFAULT '13:00:00',
  `sexta_trabalha` tinyint(1) DEFAULT 1,
  `sexta_inicio` time DEFAULT '09:00:00',
  `sexta_fim` time DEFAULT '18:00:00',
  `sexta_intervalo_inicio` time DEFAULT '12:00:00',
  `sexta_intervalo_fim` time DEFAULT '13:00:00',
  `sabado_trabalha` tinyint(1) DEFAULT 0,
  `sabado_inicio` time DEFAULT '09:00:00',
  `sabado_fim` time DEFAULT '17:00:00',
  `sabado_intervalo_inicio` time DEFAULT '12:00:00',
  `sabado_intervalo_fim` time DEFAULT '13:00:00',
  `domingo_trabalha` tinyint(1) DEFAULT 0,
  `domingo_inicio` time DEFAULT '09:00:00',
  `domingo_fim` time DEFAULT '17:00:00',
  `domingo_intervalo_inicio` time DEFAULT '12:00:00',
  `domingo_intervalo_fim` time DEFAULT '13:00:00',
  `intervalo_agendamentos` int(11) DEFAULT 30,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `profissional_agenda`
--

INSERT INTO `profissional_agenda` (`id_agenda`, `profissional_id`, `segunda_trabalha`, `segunda_inicio`, `segunda_fim`, `segunda_intervalo_inicio`, `segunda_intervalo_fim`, `terca_trabalha`, `terca_inicio`, `terca_fim`, `terca_intervalo_inicio`, `terca_intervalo_fim`, `quarta_trabalha`, `quarta_inicio`, `quarta_fim`, `quarta_intervalo_inicio`, `quarta_intervalo_fim`, `quinta_trabalha`, `quinta_inicio`, `quinta_fim`, `quinta_intervalo_inicio`, `quinta_intervalo_fim`, `sexta_trabalha`, `sexta_inicio`, `sexta_fim`, `sexta_intervalo_inicio`, `sexta_intervalo_fim`, `sabado_trabalha`, `sabado_inicio`, `sabado_fim`, `sabado_intervalo_inicio`, `sabado_intervalo_fim`, `domingo_trabalha`, `domingo_inicio`, `domingo_fim`, `domingo_intervalo_inicio`, `domingo_intervalo_fim`, `intervalo_agendamentos`, `data_criacao`, `data_atualizacao`) VALUES
(1, 9, 1, '09:00:00', '18:00:00', '12:00:00', '13:00:00', 1, '09:00:00', '18:00:00', '12:00:00', '13:00:00', 1, '09:00:00', '18:00:00', '12:00:00', '13:00:00', 1, '09:00:00', '18:00:00', '12:00:00', '13:00:00', 1, '09:00:00', '18:00:00', '12:00:00', '13:00:00', 0, '09:00:00', '17:00:00', '12:00:00', '13:00:00', 0, '09:00:00', '17:00:00', '12:00:00', '13:00:00', 30, '2025-06-05 13:17:12', '2025-06-05 13:17:12'),
(2, 11, 0, '08:00:00', '18:00:00', '12:00:00', '13:00:00', 1, '08:00:00', '18:00:00', '12:00:00', '13:00:00', 0, '08:00:00', '18:00:00', '12:00:00', '13:00:00', 1, '08:00:00', '18:00:00', '12:00:00', '13:00:00', 0, '08:00:00', '18:00:00', '12:00:00', '13:00:00', 1, '08:00:00', '18:00:00', '12:00:00', '13:00:00', 1, '10:00:00', '14:00:00', '12:00:00', '13:00:00', 30, '2025-06-05 13:17:12', '2025-06-05 13:29:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `recompensas`
--

CREATE TABLE `recompensas` (
  `id_recompensa` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `pontos_necessarios` int(11) NOT NULL,
  `tipo_recompensa` enum('desconto','servico','combo','produto') NOT NULL,
  `valor_desconto` decimal(5,2) DEFAULT NULL COMMENT 'Percentual de desconto (0-100)',
  `ativo` tinyint(1) DEFAULT 1,
  `servico_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `recompensas`
--

INSERT INTO `recompensas` (`id_recompensa`, `nome`, `descricao`, `pontos_necessarios`, `tipo_recompensa`, `valor_desconto`, `ativo`, `servico_id`) VALUES
(1, 'Desconto de 10%', 'Desconto de 10% em qualquer serviço', 100, 'desconto', 10.00, 1, NULL),
(2, 'Desconto de 20%', 'Desconto de 20% em qualquer serviço', 200, 'desconto', 20.00, 1, NULL),
(3, 'Barba Gratuita', 'Uma barba completa gratuita', 150, 'servico', NULL, 1, 2),
(4, 'Corte + Barba', 'Combo Corte + Barba com desconto especial', 300, 'combo', 30.00, 1, 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `servicos`
--

CREATE TABLE `servicos` (
  `id_servico` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `duracao` int(11) NOT NULL DEFAULT 30 COMMENT 'Duração em minutos',
  `categoria` varchar(50) DEFAULT 'Geral',
  `ativo` tinyint(1) DEFAULT 1,
  `categorias_id_categoria` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `servicos`
--

INSERT INTO `servicos` (`id_servico`, `nome`, `descricao`, `valor`, `duracao`, `categoria`, `ativo`, `categorias_id_categoria`) VALUES
(1, 'Corte Masculino Tradicional', 'Corte de cabelo masculino clássico com máquina e tesoura', 35.00, 30, 'Cabelo', 1, 1),
(2, 'Corte Moderno', 'Corte de cabelo seguindo tendências atuais', 45.00, 40, 'Cabelo', 1, 1),
(3, 'Corte Degradê', 'Corte com degradê nas laterais e nuca', 40.00, 35, 'Cabelo', 1, 1),
(4, 'Corte Social', 'Corte elegante para ambiente profissional', 50.00, 45, 'Cabelo', 1, 1),
(5, 'Corte Americano', 'Estilo americano com topete', 55.00, 50, 'Cabelo', 1, 1),
(6, 'Barba Completa', 'Aparar e modelar toda a barba', 25.00, 20, 'Barba', 1, 2),
(7, 'Barba Desenhada', 'Desenho e contorno da barba', 30.00, 25, 'Barba', 1, 2),
(8, 'Bigode', 'Aparar e modelar apenas o bigode', 15.00, 10, 'Barba', 1, 2),
(9, 'Barba Russa', 'Estilo de barba volumosa', 35.00, 30, 'Barba', 1, 2),
(10, 'Cavanhaque', 'Modelagem de cavanhaque', 20.00, 15, 'Barba', 1, 2),
(11, 'Corte + Barba', 'Combo tradicional corte e barba', 55.00, 45, 'Combo', 1, 3),
(12, 'Corte + Barba + Sobrancelha', 'Pacote completo com sobrancelha', 70.00, 60, 'Combo', 1, 3),
(13, 'Combo Premium', 'Corte + barba + hidratação + massagem', 90.00, 90, 'Combo', 1, 3),
(14, 'Combo Express', 'Corte rápido + barba básica', 45.00, 35, 'Combo', 1, 3),
(15, 'Sobrancelha Masculina', 'Modelagem de sobrancelhas', 20.00, 15, 'Acabamento', 1, 4),
(16, 'Limpeza de Pele', 'Limpeza facial básica', 40.00, 30, 'Acabamento', 1, 4),
(17, 'Pelos do Nariz', 'Remoção de pelos do nariz', 10.00, 5, 'Acabamento', 1, 4),
(18, 'Finalização com Cera', 'Acabamento com cera modeladora', 15.00, 10, 'Acabamento', 1, 4),
(19, 'Hidratação Capilar', 'Tratamento hidratante para cabelos', 45.00, 40, 'Tratamentos', 1, 5),
(20, 'Cauterização', 'Tratamento de reconstrução capilar', 60.00, 50, 'Tratamentos', 1, 5),
(21, 'Botox Capilar', 'Tratamento anti-idade para cabelos', 70.00, 60, 'Tratamentos', 1, 5),
(22, 'Tratamento Anticaspa', 'Tratamento específico para caspa', 35.00, 30, 'Tratamentos', 1, 5),
(23, 'Corte VIP', 'Atendimento exclusivo com produtos importados', 80.00, 60, 'Premium', 1, 6),
(24, 'Barba Vintage', 'Estilo clássico com produtos premium', 55.00, 45, 'Premium', 1, 6),
(25, 'Relaxamento Premium', 'Massagem + aromaterapia + bebida', 100.00, 90, 'Premium', 1, 6),
(26, 'Pacote Executivo', 'Serviço completo para executivos', 120.00, 75, 'Premium', 1, 6),
(27, 'Corte Infantil', 'Corte especial para crianças até 12 anos', 25.00, 25, 'Infantil', 1, 7),
(28, 'Corte Teen', 'Corte moderno para adolescentes', 35.00, 30, 'Infantil', 1, 7),
(29, 'Pacote Primeira Vez', 'Desconto especial para primeiro corte', 20.00, 30, 'Infantil', 1, 7),
(30, 'Noivo Completo', 'Pacote especial para noivos', 150.00, 120, 'Noivo', 1, 8),
(31, 'Padrinho Premium', 'Serviço especial para padrinhos', 100.00, 90, 'Noivo', 1, 8),
(32, 'Massagem Relaxante', 'Massagem facial e capilar', 45.00, 30, 'Relaxamento', 1, 9),
(33, 'Aromaterapia', 'Tratamento com óleos essenciais', 55.00, 40, 'Relaxamento', 1, 9),
(34, 'Luzes Masculinas', 'Mechas e luzes para cabelo masculino', 80.00, 90, 'Coloração', 1, 10),
(35, 'Tintura Completa', 'Coloração completa do cabelo', 70.00, 80, 'Coloração', 1, 10),
(36, 'Retoque de Raiz', 'Retoque da coloração na raiz', 45.00, 40, 'Coloração', 1, 10),
(37, 'Limpeza Profunda', 'Limpeza facial completa', 60.00, 45, 'Estética', 1, 11),
(38, 'Peeling Facial', 'Renovação celular facial', 80.00, 50, 'Estética', 1, 11),
(39, 'Depilação Sobrancelha', 'Depilação com cera', 25.00, 15, 'Estética', 1, 11),
(40, 'Formatura', 'Penteado e barba para formatura', 100.00, 80, 'Eventos', 1, 12),
(41, 'Casamento Convidado', 'Preparação para convidados', 70.00, 60, 'Eventos', 1, 12),
(42, 'Foto Profissional', 'Preparo para ensaio fotográfico', 90.00, 70, 'Eventos', 1, 12),
(43, 'Manutenção Mensal', 'Pacote de manutenção regular', 40.00, 35, 'Combo', 1, 3),
(44, 'Trio Perfeito', 'Corte + Barba + Sobrancelha', 65.00, 55, 'Combo', 1, 3),
(45, 'Weekend Special', 'Promoção de fim de semana', 50.00, 45, 'Combo', 1, 3),
(46, 'Barboterapia', 'Tratamento relaxante completo', 110.00, 100, 'Relaxamento', 1, 9),
(47, 'Express Total', 'Serviço rápido e completo', 55.00, 40, 'Combo', 1, 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `tipo_usuario` enum('Cliente','Profissional','Administrador') NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `data_nascimento` date NOT NULL,
  `senha` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT NULL COMMENT 'Caminho para a foto do perfil',
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1,
  `biografia` text DEFAULT NULL,
  `anos_experiencia` int(11) DEFAULT NULL,
  `disponivel` tinyint(1) DEFAULT NULL,
  `categorias_id_categoria` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `tipo_usuario`, `nome`, `email`, `cpf`, `telefone`, `data_nascimento`, `senha`, `foto`, `data_cadastro`, `ativo`, `biografia`, `anos_experiencia`, `disponivel`, `categorias_id_categoria`) VALUES
(6, 'Cliente', 'João Silva', 'joao@email.com', '11111111111', '(11) 98888-8888', '1995-05-15', '$2y$10$LTHig/f/bBAgClmNTJ.ZV.wkRnKp0SlAniugHN4R8RJSk4T1n1aTq', NULL, '2025-06-02 11:28:25', 1, NULL, NULL, NULL, 1),
(7, 'Cliente', 'Maria Santos', 'maria@email.com', '22222222222', '(11) 97777-7777', '1988-12-20', '$2y$10$LTHig/f/bBAgClmNTJ.ZV.wkRnKp0SlAniugHN4R8RJSk4T1n1aTq', NULL, '2025-06-02 11:28:25', 1, NULL, NULL, NULL, 1),
(9, 'Profissional', 'Carlos Barbeiro', 'carlos@email.com', '44444444444', '(11) 95555-5555', '1985-08-30', '$2y$10$LTHig/f/bBAgClmNTJ.ZV.wkRnKp0SlAniugHN4R8RJSk4T1n1aTq', NULL, '2025-06-02 11:28:25', 1, 'Barbeiro profissional com 10 anos de experiência', 10, 1, 1),
(11, 'Profissional', 'Elias Davi Cauã Gonçalves', 'eliasdavigoncalves@gmail.com', '64282458763', '(83) 98911-7101', '1950-04-02', '$2y$10$BOVzFwsC.sEnwBZKOUxgOuBb7PRXUf8DI/MGMlRJotortxVAkRwpW', NULL, '2025-06-03 11:53:43', 1, 'Elias Davi é barbeiro com 15 anos de experiência em cortes modernos e design de barba. Formada em Estética pela Escola Técnica de São Paulo, atua em sua própria barbearia oferecendo atendimento personalizado e orientações de cuidados.', 15, 1, 1),
(12, 'Cliente', 'João Victor Dionizio De Mesquita', 'jm5477650@gmail.com', '05765673104', '61995807828', '2009-05-30', '$2y$10$LTHig/f/bBAgClmNTJ.ZV.wkRnKp0SlAniugHN4R8RJSk4T1n1aTq', NULL, '2025-06-03 11:53:43', 1, NULL, NULL, NULL, 1),
(14, 'Administrador', 'Master Admin', 'masteradmin@gmail.com', '33333333333', '(99) 99999-9999', '1111-11-11', '$2y$10$h4DS35RXqZjTeI9pXV2xUerGc6Kcl93zwqOEcnu6zypeJQ3NDisl2', '../uploads/perfil/perfil_14_1748955316.jpg', '2025-06-03 12:54:10', 1, NULL, NULL, NULL, 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD PRIMARY KEY (`id_agendamento`),
  ADD KEY `idx_agendamentos_data` (`data_agendamento`),
  ADD KEY `idx_agendamentos_status` (`status`),
  ADD KEY `idx_agendamentos_cliente` (`cliente_id`),
  ADD KEY `idx_agendamentos_profissional` (`profissional_id`),
  ADD KEY `idx_agendamentos_servico` (`servico_id`);

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `uk_categorias_nome` (`nome`),
  ADD KEY `idx_categorias_ativo` (`ativo`);

--
-- Índices de tabela `config_fid`
--
ALTER TABLE `config_fid`
  ADD PRIMARY KEY (`id_config`);

--
-- Índices de tabela `dias_bloqueados`
--
ALTER TABLE `dias_bloqueados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_bloqueio_profissional_data` (`profissional_id`,`data_bloqueio`),
  ADD KEY `idx_bloqueio_profissional` (`profissional_id`),
  ADD KEY `idx_bloqueio_data` (`data_bloqueio`);

--
-- Índices de tabela `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id_feedback`),
  ADD KEY `idx_feedback_usuario` (`usuario_id`),
  ADD KEY `idx_feedback_agendamento` (`agendamento_id`);

--
-- Índices de tabela `fidelidade`
--
ALTER TABLE `fidelidade`
  ADD PRIMARY KEY (`id_fidelidade`),
  ADD UNIQUE KEY `uk_fidelidade_usuario` (`usuario_id`),
  ADD KEY `idx_fidelidade_config` (`config_id`);

--
-- Índices de tabela `profissional_agenda`
--
ALTER TABLE `profissional_agenda`
  ADD PRIMARY KEY (`id_agenda`),
  ADD UNIQUE KEY `uk_profissional_agenda` (`profissional_id`);

--
-- Índices de tabela `recompensas`
--
ALTER TABLE `recompensas`
  ADD PRIMARY KEY (`id_recompensa`),
  ADD KEY `idx_recompensas_servico` (`servico_id`);

--
-- Índices de tabela `servicos`
--
ALTER TABLE `servicos`
  ADD PRIMARY KEY (`id_servico`),
  ADD KEY `idx_servicos_categoria` (`categoria`),
  ADD KEY `idx_servicos_ativo` (`ativo`),
  ADD KEY `idx_servicos_categorias` (`categorias_id_categoria`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `uk_usuario_email` (`email`),
  ADD UNIQUE KEY `uk_usuario_cpf` (`cpf`),
  ADD KEY `idx_usuario_tipo` (`tipo_usuario`),
  ADD KEY `idx_usuario_ativo` (`ativo`),
  ADD KEY `idx_usuario_categorias` (`categorias_id_categoria`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  MODIFY `id_agendamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `config_fid`
--
ALTER TABLE `config_fid`
  MODIFY `id_config` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `dias_bloqueados`
--
ALTER TABLE `dias_bloqueados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id_feedback` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de tabela `fidelidade`
--
ALTER TABLE `fidelidade`
  MODIFY `id_fidelidade` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `profissional_agenda`
--
ALTER TABLE `profissional_agenda`
  MODIFY `id_agenda` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `recompensas`
--
ALTER TABLE `recompensas`
  MODIFY `id_recompensa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `servicos`
--
ALTER TABLE `servicos`
  MODIFY `id_servico` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD CONSTRAINT `fk_agendamentos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_agendamentos_profissional` FOREIGN KEY (`profissional_id`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_agendamentos_servico` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id_servico`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `dias_bloqueados`
--
ALTER TABLE `dias_bloqueados`
  ADD CONSTRAINT `fk_bloqueio_profissional` FOREIGN KEY (`profissional_id`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `fk_feedback_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id_agendamento`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_feedback_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `fidelidade`
--
ALTER TABLE `fidelidade`
  ADD CONSTRAINT `fk_fidelidade_config` FOREIGN KEY (`config_id`) REFERENCES `config_fid` (`id_config`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fidelidade_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `profissional_agenda`
--
ALTER TABLE `profissional_agenda`
  ADD CONSTRAINT `fk_profissional_agenda_usuario` FOREIGN KEY (`profissional_id`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `recompensas`
--
ALTER TABLE `recompensas`
  ADD CONSTRAINT `fk_recompensas_servicos` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id_servico`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `servicos`
--
ALTER TABLE `servicos`
  ADD CONSTRAINT `fk_servicos_categorias` FOREIGN KEY (`categorias_id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Restrições para tabelas `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `fk_usuario_categorias` FOREIGN KEY (`categorias_id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
