-- =====================================================================
--  Student Grading Dashboard SD–SMA — Database Schema
--  MySQL 8 / MariaDB 10.4+. Import via PHPMyAdmin into a fresh database.
--  Default DB name (configurable in includes/config.php): sekolah_grading
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Reset (safe to run on a fresh DB; drops in dependency order)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS report_signatures;
DROP TABLE IF EXISTS report_templates;
DROP TABLE IF EXISTS wali_notes;
DROP TABLE IF EXISTS achievements;
DROP TABLE IF EXISTS extracurricular_grades;
DROP TABLE IF EXISTS general_evaluations;
DROP TABLE IF EXISTS character_evaluations;
DROP TABLE IF EXISTS character_aspects;
DROP TABLE IF EXISTS final_grades;
DROP TABLE IF EXISTS grade_descriptions;
DROP TABLE IF EXISTS grades_daily;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS subject_topics;
DROP TABLE IF EXISTS rombel_subject_teachers;
DROP TABLE IF EXISTS rombel_members;
DROP TABLE IF EXISTS rombel;
DROP TABLE IF EXISTS teacher_subjects;
DROP TABLE IF EXISTS subject_jenjang_map;
DROP TABLE IF EXISTS subject_categories;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS extracurriculars;
DROP TABLE IF EXISTS kkm_settings;
DROP TABLE IF EXISTS semesters_state;
DROP TABLE IF EXISTS academic_years;
DROP TABLE IF EXISTS parent_remember_tokens;
DROP TABLE IF EXISTS user_remember_tokens;
DROP TABLE IF EXISTS parents_auth;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS teachers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS school_profile;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. School profile (singleton row)
-- ---------------------------------------------------------------------
CREATE TABLE school_profile (
  id            INT UNSIGNED PRIMARY KEY DEFAULT 1,
  nama          VARCHAR(160) NOT NULL DEFAULT 'Sekolah Saya',
  npsn          VARCHAR(20)  NULL,
  alamat        TEXT NULL,
  kota          VARCHAR(80) NULL,
  provinsi      VARCHAR(80) NULL,
  kode_pos      VARCHAR(10) NULL,
  telp          VARCHAR(30) NULL,
  email         VARCHAR(120) NULL,
  website       VARCHAR(160) NULL,
  kepala_dirut  VARCHAR(120) NULL,
  logo_path     VARCHAR(255) NULL,
  updated_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO school_profile (id, nama, kota) VALUES (1, 'Sekolah Saya', 'Jakarta');

-- ---------------------------------------------------------------------
-- 2. Users (staff: administrator, admin, kepsek, guru)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  niy             VARCHAR(20) NOT NULL,
  nama            VARCHAR(120) NOT NULL,
  email           VARCHAR(120) NULL,
  password_hash   VARCHAR(255) NOT NULL,
  role            ENUM('administrator','admin','kepsek','guru') NOT NULL,
  jenjang         ENUM('SD','SMP','SMA') NULL,           -- only for kepsek
  is_wali         TINYINT(1) NOT NULL DEFAULT 0,         -- only meaningful for guru
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  must_change_pw  TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at   DATETIME NULL,
  created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at      DATETIME NULL,
  UNIQUE KEY uq_users_niy (niy),
  KEY ix_users_role (role),
  KEY ix_users_jenjang (jenjang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_remember_tokens (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  selector    CHAR(32) NOT NULL,
  validator_hash CHAR(64) NOT NULL,
  expires_at  DATETIME NOT NULL,
  created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_selector (selector),
  KEY ix_user (user_id),
  CONSTRAINT fk_urt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 3. Teachers (extra fields linked 1:1 to users)
-- ---------------------------------------------------------------------
CREATE TABLE teachers (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  nip         VARCHAR(30) NULL,
  phone       VARCHAR(30) NULL,
  created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_teacher_user (user_id),
  CONSTRAINT fk_teachers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4. Students + parent auth
-- ---------------------------------------------------------------------
CREATE TABLE students (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nisn            VARCHAR(10) NOT NULL,
  nis             VARCHAR(7)  NOT NULL,
  nama            VARCHAR(120) NOT NULL,
  jenjang         ENUM('SD','SMP','SMA') NOT NULL,
  tingkat         TINYINT UNSIGNED NOT NULL,
  jk              ENUM('L','P') NOT NULL,
  tempat_lahir    VARCHAR(80) NULL,
  tgl_lahir       DATE NOT NULL,
  alamat          TEXT NULL,
  nama_ayah       VARCHAR(120) NULL,
  nama_ibu        VARCHAR(120) NULL,
  pekerjaan_ayah  VARCHAR(80)  NULL,
  pekerjaan_ibu   VARCHAR(80)  NULL,
  telp_ortu       VARCHAR(30)  NULL,
  foto_path       VARCHAR(255) NULL,
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at      DATETIME NULL,
  UNIQUE KEY uq_nisn (nisn),
  UNIQUE KEY uq_nis (nis),
  KEY ix_students_jt (jenjang, tingkat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE parents_auth (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id      INT UNSIGNED NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  must_change_pw  TINYINT(1) NOT NULL DEFAULT 1,
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at   DATETIME NULL,
  created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pa_student (student_id),
  CONSTRAINT fk_pa_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE parent_remember_tokens (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_auth_id  INT UNSIGNED NOT NULL,
  selector        CHAR(32) NOT NULL,
  validator_hash  CHAR(64) NOT NULL,
  expires_at      DATETIME NOT NULL,
  created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_psel (selector),
  KEY ix_prt_parent (parent_auth_id),
  CONSTRAINT fk_prt_pa FOREIGN KEY (parent_auth_id) REFERENCES parents_auth(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 5. Academic year & semester state (PTS/PAS lock flags)
-- ---------------------------------------------------------------------
CREATE TABLE academic_years (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  label       VARCHAR(9) NOT NULL,                       -- e.g. 2025/2026
  is_active   TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_year (label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE semesters_state (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  academic_year_id  INT UNSIGNED NOT NULL,
  semester          ENUM('ganjil','genap') NOT NULL,
  pts_locked        TINYINT(1) NOT NULL DEFAULT 0,
  pas_locked        TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_state (academic_year_id, semester),
  CONSTRAINT fk_ss_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 6. KKM settings (per jenjang grade scale)
-- ---------------------------------------------------------------------
CREATE TABLE kkm_settings (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jenjang   ENUM('SD','SMP','SMA') NOT NULL,
  grade     VARCHAR(5)  NOT NULL,
  min_val   DECIMAL(5,2) NOT NULL,
  max_val   DECIMAL(5,2) NOT NULL,
  predikat  VARCHAR(40) NOT NULL,
  UNIQUE KEY uq_kkm_grade (jenjang, grade),
  KEY ix_kkm_jenjang (jenjang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 7. Subjects (mapel) + categories + jenjang mapping
-- ---------------------------------------------------------------------
CREATE TABLE subject_categories (
  id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama  VARCHAR(80) NOT NULL,
  UNIQUE KEY uq_cat_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subjects (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode        VARCHAR(20) NOT NULL,
  nama        VARCHAR(120) NOT NULL,
  category_id INT UNSIGNED NULL,
  created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at  DATETIME NULL,
  UNIQUE KEY uq_subject_kode (kode),
  CONSTRAINT fk_subj_cat FOREIGN KEY (category_id) REFERENCES subject_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subject_jenjang_map (
  subject_id  INT UNSIGNED NOT NULL,
  jenjang     ENUM('SD','SMP','SMA') NOT NULL,
  PRIMARY KEY (subject_id, jenjang),
  CONSTRAINT fk_sjm_subj FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE teacher_subjects (
  teacher_id  INT UNSIGNED NOT NULL,
  subject_id  INT UNSIGNED NOT NULL,
  PRIMARY KEY (teacher_id, subject_id),
  CONSTRAINT fk_ts_t FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
  CONSTRAINT fk_ts_s FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 8. Extracurriculars
-- ---------------------------------------------------------------------
CREATE TABLE extracurriculars (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama        VARCHAR(120) NOT NULL,
  pembina     VARCHAR(120) NULL,
  jadwal      VARCHAR(120) NULL,
  deskripsi   TEXT NULL,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 9. Rombel & members & subject teachers (Stage 3 core tables)
-- ---------------------------------------------------------------------
CREATE TABLE rombel (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  academic_year_id  INT UNSIGNED NOT NULL,
  jenjang           ENUM('SD','SMP','SMA') NOT NULL,
  tingkat           TINYINT UNSIGNED NOT NULL,
  nama              VARCHAR(40) NOT NULL,                -- e.g. 1A, 7-Bilal
  wali_id           INT UNSIGNED NULL,                   -- users.id (must be guru)
  kapasitas         SMALLINT UNSIGNED NOT NULL DEFAULT 40,
  created_at        TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at        DATETIME NULL,
  UNIQUE KEY uq_rombel (academic_year_id, jenjang, tingkat, nama),
  KEY ix_rombel_year (academic_year_id),
  KEY ix_rombel_wali (wali_id),
  CONSTRAINT fk_rombel_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
  CONSTRAINT fk_rombel_wali FOREIGN KEY (wali_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rombel_members (
  rombel_id   INT UNSIGNED NOT NULL,
  student_id  INT UNSIGNED NOT NULL,
  joined_at   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (rombel_id, student_id),
  KEY ix_rm_student (student_id),
  CONSTRAINT fk_rm_rombel FOREIGN KEY (rombel_id) REFERENCES rombel(id) ON DELETE CASCADE,
  CONSTRAINT fk_rm_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rombel_subject_teachers (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rombel_id   INT UNSIGNED NOT NULL,
  subject_id  INT UNSIGNED NOT NULL,
  teacher_id  INT UNSIGNED NOT NULL,                     -- teachers.id
  semester    ENUM('ganjil','genap') NULL,               -- NULL = berlaku 2 semester
  created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rst (rombel_id, subject_id, semester),
  KEY ix_rst_teacher (teacher_id),
  CONSTRAINT fk_rst_r FOREIGN KEY (rombel_id) REFERENCES rombel(id) ON DELETE CASCADE,
  CONSTRAINT fk_rst_s FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_rst_t FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 10. Subject topics (Stage 3 — chapters / penilaian configuration)
--     Used as the "Subjek Penilaian" list teachers create per mapel.
--     Bobot kategori (tugas/ulangan/proyek) supported via 'kategori' + 'bobot'.
-- ---------------------------------------------------------------------
CREATE TABLE subject_topics (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rombel_id    INT UNSIGNED NOT NULL,
  subject_id   INT UNSIGNED NOT NULL,
  semester     ENUM('ganjil','genap') NOT NULL,
  kode         VARCHAR(20)  NULL,                        -- e.g. T1, U2
  judul        VARCHAR(160) NOT NULL,
  ranah        ENUM('sikap','pengetahuan','keterampilan') NOT NULL DEFAULT 'pengetahuan',
  kategori     ENUM('tugas','ulangan','proyek','praktek','portofolio','produk','lainnya') NOT NULL DEFAULT 'tugas',
  bobot        DECIMAL(5,2) NOT NULL DEFAULT 1.00,        -- weight inside its ranah
  deskripsi    TEXT NULL,
  created_by   INT UNSIGNED NULL,                         -- users.id
  created_at   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at   DATETIME NULL,
  KEY ix_st_rss (rombel_id, subject_id, semester),
  CONSTRAINT fk_st_r FOREIGN KEY (rombel_id) REFERENCES rombel(id) ON DELETE CASCADE,
  CONSTRAINT fk_st_s FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_st_u FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 11. Stage 4+ tables (created now so FK relationships are stable;
--     pages will be added in later stages)
-- ---------------------------------------------------------------------
CREATE TABLE attendance (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rombel_id   INT UNSIGNED NOT NULL,
  student_id  INT UNSIGNED NOT NULL,
  tanggal     DATE NOT NULL,
  status      ENUM('H','I','S','A') NOT NULL,            -- Hadir/Izin/Sakit/Alpa
  catatan     VARCHAR(160) NULL,
  recorded_by INT UNSIGNED NULL,
  created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_att (rombel_id, student_id, tanggal),
  CONSTRAINT fk_att_r FOREIGN KEY (rombel_id) REFERENCES rombel(id) ON DELETE CASCADE,
  CONSTRAINT fk_att_s FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_att_u FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE grades_daily (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rombel_id     INT UNSIGNED NOT NULL,
  subject_id    INT UNSIGNED NOT NULL,
  topic_id      INT UNSIGNED NULL,
  student_id    INT UNSIGNED NOT NULL,
  semester      ENUM('ganjil','genap') NOT NULL,
  period_bucket ENUM('tengah_ganjil','ganjil','tengah_genap','genap') NOT NULL,
  tanggal       DATE NOT NULL,
  nilai_sikap         DECIMAL(5,2) NULL,
  nilai_pengetahuan   DECIMAL(5,2) NULL,
  nilai_keterampilan  DECIMAL(5,2) NULL,
  recorded_by   INT UNSIGNED NULL,
  created_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY ix_gd_search (rombel_id, subject_id, semester, period_bucket),
  CONSTRAINT fk_gd_r FOREIGN KEY (rombel_id) REFERENCES rombel(id) ON DELETE CASCADE,
  CONSTRAINT fk_gd_s FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_gd_st FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_gd_t FOREIGN KEY (topic_id) REFERENCES subject_topics(id) ON DELETE SET NULL,
  CONSTRAINT fk_gd_u FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE grade_descriptions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rombel_id   INT UNSIGNED NOT NULL,
  subject_id  INT UNSIGNED NOT NULL,
  student_id  INT UNSIGNED NOT NULL,
  semester    ENUM('ganjil','genap') NOT NULL,
  period_bucket ENUM('tengah_ganjil','ganjil','tengah_genap','genap') NOT NULL,
  ranah       ENUM('sikap','pengetahuan','keterampilan') NOT NULL,
  deskripsi   TEXT NULL,
  UNIQUE KEY uq_gdesc (rombel_id, subject_id, student_id, semester, period_bucket, ranah),
  CONSTRAINT fk_gdesc_r FOREIGN KEY (rombel_id) REFERENCES rombel(id) ON DELETE CASCADE,
  CONSTRAINT fk_gdesc_s FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_gdesc_st FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE final_grades (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rombel_id     INT UNSIGNED NOT NULL,
  subject_id    INT UNSIGNED NOT NULL,
  student_id    INT UNSIGNED NOT NULL,
  semester      ENUM('ganjil','genap') NOT NULL,
  period_kind   ENUM('PTS','PAS') NOT NULL,
  nilai_sikap         DECIMAL(5,2) NULL,
  nilai_pengetahuan   DECIMAL(5,2) NULL,
  nilai_keterampilan  DECIMAL(5,2) NULL,
  catatan_guru  TEXT NULL,
  status        ENUM('draft','submitted','revised','approved','published') NOT NULL DEFAULT 'draft',
  reviewed_by   INT UNSIGNED NULL,
  reviewed_at   DATETIME NULL,
  created_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_final (rombel_id, subject_id, student_id, semester, period_kind),
  CONSTRAINT fk_fg_r FOREIGN KEY (rombel_id) REFERENCES rombel(id) ON DELETE CASCADE,
  CONSTRAINT fk_fg_s FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_fg_st FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_fg_u FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE character_aspects (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama      VARCHAR(120) NOT NULL,
  kategori  ENUM('spiritual','sosial') NOT NULL,
  UNIQUE KEY uq_aspect_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE character_evaluations (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rombel_id   INT UNSIGNED NOT NULL,
  student_id  INT UNSIGNED NOT NULL,
  aspect_id   INT UNSIGNED NOT NULL,
  semester    ENUM('ganjil','genap') NOT NULL,
  period_kind ENUM('PTS','PAS') NOT NULL,
  scale       ENUM('NI','SI','WI','PR') NOT NULL,
  remark      TEXT NULL,
  created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ce (rombel_id, student_id, aspect_id, semester, period_kind),
  CONSTRAINT fk_ce_r FOREIGN KEY (rombel_id) REFERENCES rombel(id) ON DELETE CASCADE,
  CONSTRAINT fk_ce_s FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_ce_a FOREIGN KEY (aspect_id) REFERENCES character_aspects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE general_evaluations (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rombel_id   INT UNSIGNED NOT NULL,
  student_id  INT UNSIGNED NOT NULL,
  semester    ENUM('ganjil','genap') NOT NULL,
  period_kind ENUM('PTS','PAS') NOT NULL,
  narasi      TEXT NULL,
  UNIQUE KEY uq_ge (rombel_id, student_id, semester, period_kind),
  CONSTRAINT fk_ge_r FOREIGN KEY (rombel_id) REFERENCES rombel(id) ON DELETE CASCADE,
  CONSTRAINT fk_ge_s FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE extracurricular_grades (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  extracurricular_id INT UNSIGNED NOT NULL,
  student_id      INT UNSIGNED NOT NULL,
  semester        ENUM('ganjil','genap') NOT NULL,
  academic_year_id INT UNSIGNED NOT NULL,
  predikat        VARCHAR(40) NULL,
  catatan         TEXT NULL,
  UNIQUE KEY uq_eg (extracurricular_id, student_id, semester, academic_year_id),
  CONSTRAINT fk_eg_e FOREIGN KEY (extracurricular_id) REFERENCES extracurriculars(id) ON DELETE CASCADE,
  CONSTRAINT fk_eg_s FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_eg_y FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE achievements (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id  INT UNSIGNED NOT NULL,
  semester    ENUM('ganjil','genap') NOT NULL,
  academic_year_id INT UNSIGNED NOT NULL,
  jenis       VARCHAR(80) NULL,
  judul       VARCHAR(160) NOT NULL,
  tingkat     VARCHAR(60) NULL,
  CONSTRAINT fk_ach_s FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_ach_y FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE wali_notes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rombel_id   INT UNSIGNED NOT NULL,
  student_id  INT UNSIGNED NOT NULL,
  semester    ENUM('ganjil','genap') NOT NULL,
  period_kind ENUM('PTS','PAS') NOT NULL,
  catatan     TEXT NULL,
  UNIQUE KEY uq_wn (rombel_id, student_id, semester, period_kind),
  CONSTRAINT fk_wn_r FOREIGN KEY (rombel_id) REFERENCES rombel(id) ON DELETE CASCADE,
  CONSTRAINT fk_wn_s FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE report_templates (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jenjang   ENUM('SD','SMP','SMA') NOT NULL,
  layout_json JSON NULL,
  header_img VARCHAR(255) NULL,
  footer_img VARCHAR(255) NULL,
  UNIQUE KEY uq_tpl_jenjang (jenjang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE report_signatures (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jenjang     ENUM('SD','SMP','SMA') NOT NULL,
  slot        ENUM('wali','kepsek','direktur','parent') NOT NULL,
  nama        VARCHAR(120) NULL,
  jabatan     VARCHAR(120) NULL,
  ttd_path    VARCHAR(255) NULL,
  UNIQUE KEY uq_sig (jenjang, slot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_log (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NULL,
  user_label  VARCHAR(120) NULL,
  action      VARCHAR(60) NOT NULL,
  target      VARCHAR(160) NULL,
  meta_json   JSON NULL,
  ip          VARCHAR(45) NULL,
  created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_audit_when (created_at),
  KEY ix_audit_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
--  SEED DATA
-- =====================================================================

-- Academic year
INSERT INTO academic_years (label, is_active) VALUES ('2025/2026', 1);
INSERT INTO semesters_state (academic_year_id, semester) VALUES (1, 'ganjil'), (1, 'genap');

-- Users (passwords are placeholder; run /install.php to hash properly).
-- The plain default for every seeded account = last 4 digits of NIY.
INSERT INTO users (niy, nama, email, password_hash, role, jenjang, is_active, must_change_pw) VALUES
 ('1990010001', 'Administrator',  'admin@sekolah.id',     '$2y$10$placeholderplaceholderplaceholderplaceholderpla', 'administrator', NULL, 1, 1),
 ('1990020002', 'Operator Admin', 'operator@sekolah.id',  '$2y$10$placeholderplaceholderplaceholderplaceholderpla', 'admin',         NULL, 1, 1),
 ('1990030003', 'Kepsek SD',      'kepsek.sd@sekolah.id', '$2y$10$placeholderplaceholderplaceholderplaceholderpla', 'kepsek',        'SD', 1, 1),
 ('1990040004', 'Kepsek SMP',     'kepsek.smp@sekolah.id','$2y$10$placeholderplaceholderplaceholderplaceholderpla', 'kepsek',        'SMP',1, 1),
 ('1990050005', 'Kepsek SMA',     'kepsek.sma@sekolah.id','$2y$10$placeholderplaceholderplaceholderplaceholderpla', 'kepsek',        'SMA',1, 1),
 ('1990060006', 'Bu Sari (Wali 1A)', 'sari@sekolah.id',   '$2y$10$placeholderplaceholderplaceholderplaceholderpla', 'guru',          NULL, 1, 1),
 ('1990070007', 'Pak Budi (Guru MTK)','budi@sekolah.id',  '$2y$10$placeholderplaceholderplaceholderplaceholderpla', 'guru',          NULL, 1, 1);

UPDATE users SET is_wali = 1 WHERE niy = '1990060006';

INSERT INTO teachers (user_id, nip) VALUES
 ((SELECT id FROM users WHERE niy='1990060006'), '198001012010012001'),
 ((SELECT id FROM users WHERE niy='1990070007'), '198501012010012002');

-- KKM (default scale per spec, applied to all 3 jenjang)
INSERT INTO kkm_settings (jenjang, grade, min_val, max_val, predikat) VALUES
 ('SD','A+',100.00,100.00,'Perfect'),
 ('SD','A', 96.00, 99.99,'Excellent'),
 ('SD','A-',91.00, 95.99,'Excellent'),
 ('SD','B+',86.00, 90.99,'Terrific'),
 ('SD','B', 81.00, 85.99,'Good'),
 ('SD','B-',76.00, 80.99,'Good'),
 ('SD','C', 70.00, 75.99,'Average'),
 ('SD','D',  0.00, 69.99,'Below Average'),
 ('SMP','A+',100.00,100.00,'Perfect'),
 ('SMP','A', 96.00, 99.99,'Excellent'),
 ('SMP','A-',91.00, 95.99,'Excellent'),
 ('SMP','B+',86.00, 90.99,'Terrific'),
 ('SMP','B', 81.00, 85.99,'Good'),
 ('SMP','B-',76.00, 80.99,'Good'),
 ('SMP','C', 70.00, 75.99,'Average'),
 ('SMP','D',  0.00, 69.99,'Below Average'),
 ('SMA','A+',100.00,100.00,'Perfect'),
 ('SMA','A', 96.00, 99.99,'Excellent'),
 ('SMA','A-',91.00, 95.99,'Excellent'),
 ('SMA','B+',86.00, 90.99,'Terrific'),
 ('SMA','B', 81.00, 85.99,'Good'),
 ('SMA','B-',76.00, 80.99,'Good'),
 ('SMA','C', 70.00, 75.99,'Average'),
 ('SMA','D',  0.00, 69.99,'Below Average');

-- Subject categories + sample mapel
INSERT INTO subject_categories (nama) VALUES ('Kelompok A (Wajib Umum)'), ('Kelompok B (Wajib)'), ('Muatan Lokal');

INSERT INTO subjects (kode, nama, category_id) VALUES
 ('PAI','Pendidikan Agama Islam', 1),
 ('PKN','PPKn',                    1),
 ('BIN','Bahasa Indonesia',        1),
 ('MTK','Matematika',              1),
 ('IPA','Ilmu Pengetahuan Alam',   1),
 ('IPS','Ilmu Pengetahuan Sosial', 1),
 ('BIG','Bahasa Inggris',          1),
 ('SBK','Seni Budaya & Prakarya',  2),
 ('PJK','PJOK',                    2);

INSERT INTO subject_jenjang_map (subject_id, jenjang)
SELECT id, 'SD' FROM subjects WHERE kode IN ('PAI','PKN','BIN','MTK','IPA','IPS','BIG','SBK','PJK');
INSERT INTO subject_jenjang_map (subject_id, jenjang)
SELECT id, 'SMP' FROM subjects;
INSERT INTO subject_jenjang_map (subject_id, jenjang)
SELECT id, 'SMA' FROM subjects;

-- Character aspects
INSERT INTO character_aspects (nama, kategori) VALUES
 ('Ketaatan beribadah','spiritual'),
 ('Berdoa sebelum/sesudah kegiatan','spiritual'),
 ('Toleransi beragama','spiritual'),
 ('Jujur','sosial'),
 ('Disiplin','sosial'),
 ('Tanggung jawab','sosial'),
 ('Santun','sosial'),
 ('Peduli','sosial'),
 ('Percaya diri','sosial');

-- Sample students (2 SD, 1 SMP, 1 SMA)
INSERT INTO students (nisn, nis, nama, jenjang, tingkat, jk, tempat_lahir, tgl_lahir, alamat, nama_ayah, nama_ibu) VALUES
 ('0123456701','0010001','Ahmad Fauzi',     'SD',  1, 'L', 'Jakarta','2018-02-01','Jl. Anggrek 1','Bapak Fauzi','Ibu Fauzi'),
 ('0123456702','0010002','Siti Aisyah',     'SD',  1, 'P', 'Bandung','2018-04-12','Jl. Mawar 2','Bapak Hendra','Ibu Diana'),
 ('0123456703','0070001','Bagas Pratama',   'SMP', 7, 'L', 'Bogor',  '2012-09-20','Jl. Melati 3','Bapak Eko','Ibu Sri'),
 ('0123456704','0100001','Citra Lestari',   'SMA',10, 'P', 'Depok',  '2009-11-30','Jl. Kenanga 4','Bapak Hadi','Ibu Wati');

-- Parent auth (default password = ddmmyyyy of tgl_lahir, hashed by install.php)
INSERT INTO parents_auth (student_id, password_hash) SELECT id, '$2y$10$placeholderplaceholderplaceholderplaceholderpla' FROM students;

-- Sample rombel + members
INSERT INTO rombel (academic_year_id, jenjang, tingkat, nama, wali_id, kapasitas)
VALUES (1,'SD',1,'1A',(SELECT id FROM users WHERE niy='1990060006'),28);
INSERT INTO rombel_members (rombel_id, student_id)
SELECT 1, id FROM students WHERE jenjang='SD' AND tingkat=1;

-- Sample guru pengampu (Pak Budi mengajar MTK di Rombel 1A, semester ganjil)
INSERT INTO rombel_subject_teachers (rombel_id, subject_id, teacher_id, semester)
VALUES (1, (SELECT id FROM subjects WHERE kode='MTK'),
           (SELECT id FROM teachers WHERE user_id=(SELECT id FROM users WHERE niy='1990070007')),
           'ganjil');

-- Extracurriculars sample
INSERT INTO extracurriculars (nama, pembina, jadwal) VALUES
 ('Pramuka',  'Kak Tono', 'Sabtu 08:00'),
 ('Tahfidz',  'Ust. Yusuf','Senin 14:00'),
 ('Futsal',   'Coach Iwan','Rabu 15:00');

-- Report template stubs
INSERT INTO report_templates (jenjang) VALUES ('SD'),('SMP'),('SMA');


