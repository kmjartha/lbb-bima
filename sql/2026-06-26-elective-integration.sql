-- Migration: Integrasi penempatan mapel pilihan dengan penilaian harian
-- Tanggal: 2026-06-26
--
-- Perubahan:
-- 1. Tambahkan kolom elective_class_id pada tabel subjects
--    (shadow subject dari elective_classes agar nilai harian ter-scope ke siswa yang tepat)
-- 2. Tambahkan index untuk performa query filter siswa per elective class

-- Tambahkan elective_class_id ke subjects jika belum ada
ALTER TABLE `subjects`
  ADD COLUMN IF NOT EXISTS `elective_class_id` int(10) UNSIGNED DEFAULT NULL
    COMMENT 'Jika diisi, subject ini adalah shadow dari opsi mapel pilihan (elective_classes). Nilai NULL = subject biasa.'
    AFTER `category_id`;

-- Index untuk mempercepat lookup elective shadow subjects
ALTER TABLE `subjects`
  ADD KEY IF NOT EXISTS `ix_subjects_elective_class` (`elective_class_id`);

-- Foreign key ke elective_classes (soft constraint, boleh NULL untuk subject biasa)
-- Jika FK belum ada, tambahkan
ALTER TABLE `subjects`
  ADD CONSTRAINT IF NOT EXISTS `fk_subjects_elective_class`
    FOREIGN KEY (`elective_class_id`) REFERENCES `elective_classes` (`id`)
    ON DELETE SET NULL;
