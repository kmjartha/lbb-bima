-- Add bintang and deskripsi columns to grades_daily table
ALTER TABLE `grades_daily`
ADD COLUMN `bintang` INT DEFAULT NULL AFTER `nilai_keterampilan`,
ADD COLUMN `deskripsi` TEXT DEFAULT NULL AFTER `bintang`;
