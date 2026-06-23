-- Migration: Add subject_id to electives for full elective subject integration

ALTER TABLE `electives`
  ADD COLUMN `subject_id` int(10) UNSIGNED DEFAULT NULL AFTER `academic_year_id`,
  ADD KEY `ix_elective_subject` (`subject_id`);

INSERT IGNORE INTO subjects (academic_year_id, kode, nama, category_id)
SELECT academic_year_id, kode, nama, category_id
FROM electives
WHERE subject_id IS NULL;

UPDATE electives e
JOIN subjects s ON s.academic_year_id = e.academic_year_id AND s.kode = e.kode
SET e.subject_id = s.id
WHERE e.subject_id IS NULL;

INSERT IGNORE INTO subject_jenjang_map (subject_id, jenjang)
SELECT e.subject_id, e.jenjang
FROM electives e
WHERE e.subject_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM subject_jenjang_map m
    WHERE m.subject_id = e.subject_id
      AND m.jenjang = e.jenjang
  );

ALTER TABLE `electives`
  ADD CONSTRAINT `fk_elective_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL;
