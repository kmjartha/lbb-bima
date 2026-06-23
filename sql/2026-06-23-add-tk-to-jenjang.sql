-- Add TK to jenjang ENUM
-- Migration: 2026-06-23-add-tk-to-jenjang

ALTER TABLE `students` MODIFY COLUMN `jenjang` ENUM('TK','SD','SMP','SMA') NOT NULL;
