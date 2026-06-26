-- Add TK to jenjang ENUM
-- Migration: 2026-06-23-add-tk-to-jenjang

ALTER TABLE `students` MODIFY COLUMN `jenjang` ENUM('TK','SD','SMP','SMA') NOT NULL;
ALTER TABLE `rombel` MODIFY COLUMN `jenjang` ENUM('TK','SD','SMP','SMA') NOT NULL;
UPDATE `students` SET `jenjang` = 'TK' WHERE `jenjang` IS NULL OR `jenjang` = '';
UPDATE `rombel` SET `jenjang` = 'TK' WHERE `jenjang` IS NULL OR `jenjang` = '';
UPDATE `report_templates` SET `jenjang` = 'TK' WHERE `jenjang` IS NULL OR `jenjang` = '';
ALTER TABLE `character_aspects` MODIFY COLUMN `jenjang` ENUM('TK','SD','SMP','SMA') NOT NULL DEFAULT 'SD';
ALTER TABLE `electives` MODIFY COLUMN `jenjang` ENUM('TK','SD','SMP','SMA') NOT NULL;
ALTER TABLE `kkm_settings` MODIFY COLUMN `jenjang` ENUM('TK','SD','SMP','SMA') NOT NULL;
ALTER TABLE `report_templates` MODIFY COLUMN `jenjang` ENUM('TK','SD','SMP','SMA') NOT NULL;
ALTER TABLE `report_signatures` MODIFY COLUMN `jenjang` ENUM('TK','SD','SMP','SMA') NOT NULL;
ALTER TABLE `subject_jenjang_map` MODIFY COLUMN `jenjang` ENUM('TK','SD','SMP','SMA') NOT NULL;
