-- ======================================================
-- KONGO ARENA - ESTRUTURA COMPLETA DA BASE DE DADOS
-- Prefixo: cong_
-- Motor: MySQL (InnoDB)
-- ======================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------
-- 1. UTILIZADORES (login e permissões)
-- ------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cong_utilizadores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_completo` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL UNIQUE,
  `telefone` varchar(20) DEFAULT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `tipo` enum('admin','gestor','equipa','atleta','scout','visitante') NOT NULL,
  `status` enum('ativo','pendente','inativo') DEFAULT 'pendente',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acesso` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------
-- 2. MODALIDADES (dinâmicas)
-- ------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cong_modalidades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL UNIQUE,
  `descricao` text DEFAULT NULL,
  `icone` varchar(50) DEFAULT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------
-- 3. ATLETAS (Kongo ID)
-- ------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cong_atletas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kongo_id` varchar(20) NOT NULL UNIQUE,
  `nome_completo` varchar(150) NOT NULL,
  `data_nascimento` date NOT NULL,
  `genero` enum('M','F','Outro') DEFAULT NULL,
  `fotografia` varchar(255) DEFAULT NULL,
  `altura` decimal(5,2) DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `pe_dominante` enum('Esquerdo','Direito','Ambidestro') DEFAULT NULL,
  `nacionalidade` varchar(50) DEFAULT 'Angola',
  `cidade` varchar(100) DEFAULT NULL,
  `biografia` text DEFAULT NULL,
  `status_licenca` enum('ativa','renovacao','expirada') DEFAULT 'expirada',
  `utilizador_id` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_atleta_utilizador` (`utilizador_id`),
  CONSTRAINT `fk_atleta_utilizador` FOREIGN KEY (`utilizador_id`) REFERENCES `cong_utilizadores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------
-- 4. ATLETA_MODALIDADES (relação N:N)
-- ------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cong_atleta_modalidades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `atleta_id` int(11) NOT NULL,
  `modalidade_id` int(11) NOT NULL,
  `posicao` varchar(50) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_atleta_modalidade` (`atleta_id`,`modalidade_id`),
  KEY `fk_atleta_mod_atleta` (`atleta_id`),
  KEY `fk_atleta_mod_modalidade` (`modalidade_id`),
  CONSTRAINT `fk_atleta_mod_atleta` FOREIGN KEY (`atleta_id`) REFERENCES `cong_atletas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_atleta_mod_modalidade` FOREIGN KEY (`modalidade_id`) REFERENCES `cong_modalidades` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------
-- 5. CLUBES
-- ------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cong_clubes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `logotipo` varchar(255) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `responsavel` varchar(150) DEFAULT NULL,
  `contacto` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------
-- 6. EQUIPAS
-- ------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cong_equipas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `clube_id` int(11) DEFAULT NULL,
  `modalidade_id` int(11) NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `treinador` varchar(150) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_equipa_clube` (`clube_id`),
  KEY `fk_equipa_modalidade` (`modalidade_id`),
  CONSTRAINT `fk_equipa_clube` FOREIGN KEY (`clube_id`) REFERENCES `cong_clubes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_equipa_modalidade` FOREIGN KEY (`modalidade_id`) REFERENCES `cong_modalidades` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------
-- 7. EQUIPA_ATLETAS (histórico)
-- ------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cong_equipa_atletas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipa_id` int(11) NOT NULL,
  `atleta_id` int(11) NOT NULL,
  `data_entrada` date NOT NULL,
  `data_saida` date DEFAULT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_atleta_equipa` (`atleta_id`,`equipa_id`,`data_entrada`),
  KEY `fk_ea_equipa` (`equipa_id`),
  KEY `fk_ea_atleta` (`atleta_id`),
  CONSTRAINT `fk_ea_equipa` FOREIGN KEY (`equipa_id`) REFERENCES `cong_equipas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ea_atleta` FOREIGN KEY (`atleta_id`) REFERENCES `cong_atletas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------
-- 8. TEMPORADAS
-- ------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cong_temporadas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL UNIQUE,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `status` enum('ativa','encerrada') DEFAULT 'ativa',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------
-- 9. COMPETIÇÕES
-- ------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cong_competicoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `modalidade_id` int(11) NOT NULL,
  `temporada_id` int(11) NOT NULL,
  `formato` enum('Liga','Torneio','Eliminatorias','Misto') NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `imagem_capa` varchar(255) DEFAULT NULL,
  `status` enum('breve','ativa','encerrada') DEFAULT 'breve',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_comp_modalidade` (`modalidade_id`),
  KEY `fk_comp_temporada` (`temporada_id`),
  CONSTRAINT `fk_comp_modalidade` FOREIGN KEY (`modalidade_id`) REFERENCES `cong_modalidades` (`id`),
  CONSTRAINT `fk_comp_temporada` FOREIGN KEY (`temporada_id`) REFERENCES `cong_temporadas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------
-- 10. COMPETICAO_EQUIPAS
-- ------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cong_competicao_equipas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `competicao_id` int(11) NOT NULL,
  `equipa_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_comp_equipa` (`competicao_id`,`equipa_id`),
  KEY `fk_ce_competicao` (`competicao_id`),
  KEY `fk_ce_equipa` (`equipa_id`),
  CONSTRAINT `fk_ce_competicao` FOREIGN KEY (`competicao_id`) REFERENCES `cong_competicoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ce_equipa` FOREIGN KEY (`equipa_id`) REFERENCES `cong_equipas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------
-- 11. JOGOS (com resultado em JSON)
-- ------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cong_jogos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `competicao_id` int(11) NOT NULL,
  `temporada_id` int(11) NOT NULL,
  `equipa_casa_id` int(11) NOT NULL,
  `equipa_fora_id` int(11) NOT NULL,
  `data_hora` datetime NOT NULL,
  `local` varchar(255) DEFAULT NULL,
  `resultado` json DEFAULT NULL,
  `status` enum('agendado','a_decorrer','finalizado','cancelado') DEFAULT 'agendado',
  PRIMARY KEY (`id`),
  KEY `fk_jogo_competicao` (`competicao_id`),
  KEY `fk_jogo_temporada` (`temporada_id`),
  KEY `fk_jogo_casa` (`equipa_casa_id`),
  KEY `fk_jogo_fora` (`equipa_fora_id`),
  CONSTRAINT `fk_jogo_competicao` FOREIGN KEY (`competicao_id`) REFERENCES `cong_competicoes` (`id`),
  CONSTRAINT `fk_jogo_temporada` FOREIGN KEY (`temporada_id`) REFERENCES `cong_temporadas` (`id`),
  CONSTRAINT `fk_jogo_casa` FOREIGN KEY (`equipa_casa_id`) REFERENCES `cong_equipas` (`id`),
  CONSTRAINT `fk_jogo_fora` FOREIGN KEY (`equipa_fora_id`) REFERENCES `cong_equipas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------
-- 12. ESTATISTICAS_JOGO (individuais, flexíveis)
-- ------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cong_estatisticas_jogo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jogo_id` int(11) NOT NULL,
  `atleta_id` int(11) NOT NULL,
  `equipa_id` int(11) NOT NULL,
  `tipo_estatistica` varchar(50) NOT NULL,
  `valor` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_est_jogo` (`jogo_id`),
  KEY `fk_est_atleta` (`atleta_id`),
  KEY `fk_est_equipa` (`equipa_id`),
  CONSTRAINT `fk_est_jogo` FOREIGN KEY (`jogo_id`) REFERENCES `cong_jogos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_est_atleta` FOREIGN KEY (`atleta_id`) REFERENCES `cong_atletas` (`id`),
  CONSTRAINT `fk_est_equipa` FOREIGN KEY (`equipa_id`) REFERENCES `cong_equipas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------
-- 13. RANKING_ATLETAS
-- ------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cong_ranking_atletas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `atleta_id` int(11) NOT NULL,
  `competicao_id` int(11) NOT NULL,
  `modalidade_id` int(11) NOT NULL,
  `pontos` int(11) DEFAULT 0,
  `posicao` int(11) DEFAULT NULL,
  `metricas` json DEFAULT NULL,
  `ultima_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_atleta_competicao` (`atleta_id`,`competicao_id`),
  KEY `fk_rank_atleta` (`atleta_id`),
  KEY `fk_rank_competicao` (`competicao_id`),
  KEY `fk_rank_modalidade` (`modalidade_id`),
  CONSTRAINT `fk_rank_atleta` FOREIGN KEY (`atleta_id`) REFERENCES `cong_atletas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rank_competicao` FOREIGN KEY (`competicao_id`) REFERENCES `cong_competicoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rank_modalidade` FOREIGN KEY (`modalidade_id`) REFERENCES `cong_modalidades` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------
-- 14. LICENCAS (Kongo ID ativo)
-- ------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cong_licencas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `atleta_id` int(11) NOT NULL UNIQUE,
  `data_emissao` date NOT NULL,
  `data_expiracao` date NOT NULL,
  `status` enum('ativa','renovacao','expirada') DEFAULT 'ativa',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_licenca_atleta` FOREIGN KEY (`atleta_id`) REFERENCES `cong_atletas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ======================================================
-- DADOS INICIAIS (opcionais, para testes)
-- ======================================================

-- Inserir modalidades base
INSERT INTO `cong_modalidades` (`nome`, `descricao`, `status`) VALUES
('Futebol', 'Futebol de campo 11', 'ativo'),
('Futsal', 'Futebol de salão', 'ativo'),
('Basquetebol', 'Basquetebol 5x5', 'ativo'),
('Andebol', 'Andebol de campo', 'ativo'),
('Garrafinhas', 'Modalidade de garrafinhas', 'ativo');

-- Inserir temporada
INSERT INTO `cong_temporadas` (`nome`, `data_inicio`, `data_fim`, `status`) VALUES
('2026', '2026-01-01', '2026-12-31', 'ativa');

-- Inserir utilizador administrador (senha: admin123)
-- Hash bcrypt gerado com password_hash() do PHP — compatível com password_verify()
-- usado em api/models/Utilizador.php. IMPORTANTE: altera esta senha assim que
-- fizeres o primeiro login.
INSERT INTO `cong_utilizadores` (`nome_completo`, `email`, `senha_hash`, `tipo`, `status`) VALUES
('Administrador Kongo Arena', 'admin@kongarena.com', '$2b$12$q5atYEUvVgpKWjlTe6XIb.TuHwU6YAVadzjBDYajYJACe9w7X1PmW', 'admin', 'ativo');

-- Inserir um clube exemplo
INSERT INTO `cong_clubes` (`nome`, `cidade`, `responsavel`, `contacto`, `email`) VALUES
('Kongo Sports Club', 'Luanda', 'João Silva', '923456789', 'kongo@clube.com');

-- Inserir uma equipa exemplo
INSERT INTO `cong_equipas` (`nome`, `clube_id`, `modalidade_id`, `categoria`, `treinador`) VALUES
('Kongo Futsal Sénior', 1, 2, 'Sénior', 'Pedro Mendes');

-- ======================================================
-- FIM DO SCRIPT
-- ======================================================