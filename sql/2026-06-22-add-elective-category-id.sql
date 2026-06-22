-- Migration: Add category_id to electives for subject category support
ALTER TABLE `electives`
  ADD COLUMN `category_id` int(10) UNSIGNED DEFAULT NULL AFTER `deskripsi`;

ALTER TABLE `electives`
  ADD KEY `ix_elective_category` (`category_id`);

ALTER TABLE `electives`
  ADD CONSTRAINT `fk_elective_category` FOREIGN KEY (`category_id`) REFERENCES `subject_categories` (`id`) ON DELETE SET NULL;
