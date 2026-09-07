-- Migration: 2026_09_general_eval_review
-- Purpose  : Menambahkan alur review (approve/revise) oleh Kepsek untuk
--            General Description / Narrative (tabel general_evaluations),
--            mengikuti pola yang sudah ada di final_grades.
-- Cara pakai: jalankan sekali di database yang SUDAH ada (instalasi lama).
--            Untuk instalasi baru, skema ini sudah termasuk di
--            sql/sekolah_grading.sql — jangan jalankan file ini lagi di sana.

ALTER TABLE `general_evaluations`
  ADD COLUMN `status` enum('draft','submitted','revised','approved') NOT NULL DEFAULT 'draft' AFTER `narasi`,
  ADD COLUMN `submitted_by` int(10) UNSIGNED DEFAULT NULL AFTER `status`,
  ADD COLUMN `reviewed_by` int(10) UNSIGNED DEFAULT NULL AFTER `submitted_by`,
  ADD COLUMN `reviewed_at` datetime DEFAULT NULL AFTER `reviewed_by`,
  ADD COLUMN `created_at` timestamp NULL DEFAULT current_timestamp() AFTER `reviewed_at`,
  ADD COLUMN `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`;

-- Baris lama yang sudah punya narasi dianggap "submitted" (menunggu
-- verifikasi kepsek) supaya langsung muncul di antrean Verifikasi >
-- Deskripsi Umum. Baris yang narasinya kosong tetap 'draft'.
UPDATE `general_evaluations`
   SET `status` = 'submitted'
 WHERE `narasi` IS NOT NULL AND TRIM(`narasi`) <> '';

ALTER TABLE `general_evaluations`
  ADD KEY `fk_ge_u` (`reviewed_by`),
  ADD KEY `fk_ge_submitted_by` (`submitted_by`);

ALTER TABLE `general_evaluations`
  ADD CONSTRAINT `fk_ge_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ge_u` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
