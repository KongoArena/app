-- ======================================================
-- KONGO ARENA - MIGRAÇÃO: ligar atleta ao clube
-- Rode este script UMA VEZ no seu banco MySQL já existente
-- (phpMyAdmin, Adminer, ou linha de comando)
-- ======================================================

ALTER TABLE `cong_atletas`
  ADD COLUMN `clube_id` INT(11) DEFAULT NULL AFTER `utilizador_id`,
  ADD KEY `fk_atleta_clube` (`clube_id`),
  ADD CONSTRAINT `fk_atleta_clube` FOREIGN KEY (`clube_id`)
    REFERENCES `cong_clubes` (`id`) ON DELETE SET NULL;

-- Liga também a conta de login (utilizador) a um clube, para o
-- painel do cliente saber de qual clube mostrar os dados.
ALTER TABLE `cong_utilizadores`
  ADD COLUMN `clube_id` INT(11) DEFAULT NULL AFTER `tipo`,
  ADD KEY `fk_utilizador_clube` (`clube_id`),
  ADD CONSTRAINT `fk_utilizador_clube` FOREIGN KEY (`clube_id`)
    REFERENCES `cong_clubes` (`id`) ON DELETE SET NULL;
