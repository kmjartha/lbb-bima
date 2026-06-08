-- Elective subjects feature
-- Creates tables for elective groups, options, rombel mappings, and assignments.

CREATE TABLE electives (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NOT NULL,
  nama VARCHAR(120) NOT NULL,
  jenjang ENUM('SD','SMP','SMA') NOT NULL,
  academic_year_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_elective_kode (kode),
  CONSTRAINT fk_elective_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE elective_rombels (
  elective_id INT UNSIGNED NOT NULL,
  rombel_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (elective_id, rombel_id),
  CONSTRAINT fk_er_e FOREIGN KEY (elective_id) REFERENCES electives(id) ON DELETE CASCADE,
  CONSTRAINT fk_er_r FOREIGN KEY (rombel_id) REFERENCES rombel(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE elective_classes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  elective_id INT UNSIGNED NOT NULL,
  nama VARCHAR(120) NOT NULL,
  kapasitas INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_ec_e FOREIGN KEY (elective_id) REFERENCES electives(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE elective_assignments (
  elective_id INT UNSIGNED NOT NULL,
  elective_class_id INT UNSIGNED NOT NULL,
  student_id INT UNSIGNED NOT NULL,
  semester ENUM('ganjil','genap') NOT NULL,
  assigned_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (elective_id, student_id, semester),
  CONSTRAINT fk_ea_e FOREIGN KEY (elective_id) REFERENCES electives(id) ON DELETE CASCADE,
  CONSTRAINT fk_ea_ec FOREIGN KEY (elective_class_id) REFERENCES elective_classes(id) ON DELETE CASCADE,
  CONSTRAINT fk_ea_s FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_ea_u FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
