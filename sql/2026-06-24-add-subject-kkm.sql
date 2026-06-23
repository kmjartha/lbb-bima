-- Migration: Add subject_kkm table for KKM (Kriteria Ketuntasan Minimal) per subject + tingkat kelas
-- KKM is stored per (subject_id, tingkat) where tingkat is the numeric grade level (1-12).
-- This is intentionally separate from `kkm_settings`, which is actually the predikat
-- (grade-letter) scale table and was kept as-is to avoid a risky rename of existing data.

CREATE TABLE `subject_kkm` (
  `subject_id` int(10) UNSIGNED NOT NULL,
  `tingkat` tinyint(3) UNSIGNED NOT NULL,
  `kkm` decimal(5,2) NOT NULL DEFAULT 70.00,
  PRIMARY KEY (`subject_id`, `tingkat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `subject_kkm`
  ADD CONSTRAINT `fk_subject_kkm_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
