<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/scope.php';
$u = require_view('academic_years'); // Administrator only per spec

$pdo = db();
$action = $_GET['action'] ?? 'list';
$err = null;
$editYearId = int_or_null($_GET['edit'] ?? null);
$editYear = null;
$editDates = [];

function normalize_jenjang($value): string
{
    $v = trim((string)($value ?? ''));
    return in_array($v, ['TK', 'SD', 'SMP', 'SMA'], true) ? $v : 'TK';
}

function ensure_jenjang_enum_support(PDO $pdo): void
{
    $tables = [
        'students' => 'NOT NULL',
        'rombel' => 'NOT NULL',
        'character_aspects' => "NOT NULL DEFAULT 'SD'",
        'electives' => 'NOT NULL',
        'kkm_settings' => 'NOT NULL',
        'report_templates' => 'NOT NULL',
        'report_signatures' => 'NOT NULL',
        'subject_jenjang_map' => 'NOT NULL',
    ];

    foreach ($tables as $table => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'jenjang'");
        $col = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$col) {
            continue;
        }

        if (stripos($col['Type'], "enum('TK'") !== false) {
            continue;
        }

        $pdo->exec("ALTER TABLE `$table` MODIFY COLUMN `jenjang` ENUM('TK','SD','SMP','SMA') $definition");
    }
}

// ---------- POST handlers ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $op = (string)($_POST['op'] ?? '');

        if ($op === 'create') {
    require_administrator();
    $label = req_str($_POST, 'label', 9);
    if (!preg_match('#^\d{4}/\d{4}$#', $label)) {
        throw new RuntimeException('Format tahun ajaran: YYYY/YYYY (contoh 2025/2026).');
    }
    $ganjil_start = req_str($_POST, 'ganjil_start', 10);
    $ganjil_end   = req_str($_POST, 'ganjil_end', 10);
    $genap_start  = req_str($_POST, 'genap_start', 10);
    $genap_end    = req_str($_POST, 'genap_end', 10);
    $copy_from     = int_or_null($_POST['copy_from'] ?? null);
    $set_active    = !empty($_POST['set_active']);

    $validateDate = function (string $value, string $label): string {
        $d = DateTime::createFromFormat('Y-m-d', $value);
        if (!$d || $d->format('Y-m-d') !== $value) {
            throw new RuntimeException("$label harus format YYYY-MM-DD.");
        }
        return $value;
    };

    $ganjil_start = $validateDate($ganjil_start, 'Semester Ganjil mulai');
    $ganjil_end   = $validateDate($ganjil_end,   'Semester Ganjil selesai');
    $genap_start  = $validateDate($genap_start,  'Semester Genap mulai');
    $genap_end    = $validateDate($genap_end,    'Semester Genap selesai');
    if ($ganjil_start > $ganjil_end) {
        throw new RuntimeException('Semester Ganjil: tanggal mulai harus sebelum atau sama dengan tanggal selesai.');
    }
    if ($genap_start > $genap_end) {
        throw new RuntimeException('Semester Genap: tanggal mulai harus sebelum atau sama dengan tanggal selesai.');
    }

    ensure_jenjang_enum_support($pdo);
    $pdo->beginTransaction();

    // 1) create the new year + semester state rows
    $pdo->prepare("INSERT INTO academic_years (label, is_active) VALUES (:l, 0)")
        ->execute(['l' => $label]);
    $newId = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO semesters_state (academic_year_id, semester, start_date, end_date)
                   VALUES (:y1,'ganjil',:g1s,:g1e),(:y2,'genap',:g2s,:g2e)")
        ->execute([
            'y1' => $newId,
            'y2' => $newId,
            'g1s' => $ganjil_start,
            'g1e' => $ganjil_end,
            'g2s' => $genap_start,
            'g2e' => $genap_end,
        ]);

    // 2) deep copy if requested
    if ($copy_from) {

        // helper: remap an old id through a map (returns null if missing)
        $map = function (array $m, $k) { return isset($m[$k]) ? $m[$k] : null; };

        // ---- subject_categories ----
        $catMap = [];
        $s = $pdo->prepare("SELECT id, nama FROM subject_categories WHERE academic_year_id = :y");
        $s->execute(['y' => $copy_from]);
        $ins = $pdo->prepare("INSERT INTO subject_categories (academic_year_id, nama) VALUES (:y,:n)");
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $ins->execute(['y' => $newId, 'n' => $r['nama']]);
            $catMap[(int)$r['id']] = (int)$pdo->lastInsertId();
        }

        // ---- subjects ----
        // Exclude elective-derived ("shadow") subjects: their source of truth
        // is an elective option, which this routine does not copy. Copying
        // them here would create orphaned plain subjects in the new year.
        $subjMap = [];
        $s = $pdo->prepare("SELECT id, kode, nama, category_id
                            FROM subjects
                            WHERE academic_year_id = :y AND deleted_at IS NULL
                              AND elective_class_id IS NULL");
        $s->execute(['y' => $copy_from]);
        $ins = $pdo->prepare("INSERT INTO subjects (academic_year_id, kode, nama, category_id)
                              VALUES (:y, :k, :n, :c)");
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $ins->execute([
                'y' => $newId,
                'k' => $r['kode'],
                'n' => $r['nama'],
                'c' => $r['category_id'] ? $map($catMap, (int)$r['category_id']) : null,
            ]);
            $subjMap[(int)$r['id']] = (int)$pdo->lastInsertId();
        }

        // ---- subject_jenjang_map (per new subject) ----
        if ($subjMap) {
            $s = $pdo->prepare("SELECT subject_id, jenjang FROM subject_jenjang_map
                                WHERE subject_id IN (" . implode(',', array_keys($subjMap)) . ")");
            $s->execute();
            $ins = $pdo->prepare("INSERT IGNORE INTO subject_jenjang_map (subject_id, jenjang) VALUES (:s,:j)");
            foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $ins->execute(['s' => $subjMap[(int)$r['subject_id']], 'j' => normalize_jenjang($r['jenjang'] ?? null)]);
            }
        }

        // ---- students (year-scoped after migration) ----
        $stuMap = [];
        $s = $pdo->prepare("SELECT * FROM students
                            WHERE academic_year_id = :y AND deleted_at IS NULL");
        $s->execute(['y' => $copy_from]);
        $cols = ['nisn','nis','nama','jenjang','tingkat','jk','tempat_lahir','tgl_lahir',
                 'alamat','nama_ayah','nama_ibu','pekerjaan_ayah','pekerjaan_ibu',
                 'telp_ortu','foto_path','is_active'];
        $colList   = 'academic_year_id,' . implode(',', $cols);
        $placeList = ':academic_year_id,' . implode(',', array_map(fn($c)=>':'.$c, $cols));
        $ins = $pdo->prepare("INSERT INTO students ($colList) VALUES ($placeList)");
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $params = ['academic_year_id' => $newId];
            foreach ($cols as $c) {
                $params[$c] = ($c === 'jenjang') ? normalize_jenjang($r[$c] ?? null) : $r[$c];
            }
            $ins->execute($params);
            $stuMap[(int)$r['id']] = (int)$pdo->lastInsertId();
        }

        // ---- rombel ----
        $rombelMap = [];
        $s = $pdo->prepare("SELECT * FROM rombel
                            WHERE academic_year_id = :y AND deleted_at IS NULL");
        $s->execute(['y' => $copy_from]);
        $ins = $pdo->prepare("INSERT INTO rombel
            (academic_year_id, jenjang, tingkat, nama, wali_id, kapasitas)
            VALUES (:y,:j,:t,:n,:w,:k)");
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $ins->execute([
                'y' => $newId,
                'j' => normalize_jenjang($r['jenjang'] ?? null),
                't' => $r['tingkat'],
                'n' => $r['nama'],
                'w' => $r['wali_id'],          // users.id — global, do not remap
                'k' => $r['kapasitas'],
            ]);
            $rombelMap[(int)$r['id']] = (int)$pdo->lastInsertId();
        }

        // ---- rombel_members (with remapped student + rombel ids) ----
        if ($rombelMap) {
            $s = $pdo->prepare("SELECT rombel_id, student_id FROM rombel_members
                                WHERE rombel_id IN (" . implode(',', array_keys($rombelMap)) . ")");
            $s->execute();
            $ins = $pdo->prepare("INSERT IGNORE INTO rombel_members (rombel_id, student_id) VALUES (:r,:s)");
            foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $newSid = $stuMap[(int)$r['student_id']] ?? null;
                if (!$newSid) continue; // student wasn't copied (inactive/deleted)
                $ins->execute([
                    'r' => $rombelMap[(int)$r['rombel_id']],
                    's' => $newSid,
                ]);
            }
        }

        // ---- rombel_subject_teachers (remap rombel + subject; teacher stays) ----
        if ($rombelMap) {
            $s = $pdo->prepare("SELECT rombel_id, subject_id, teacher_id, semester
                                FROM rombel_subject_teachers
                                WHERE rombel_id IN (" . implode(',', array_keys($rombelMap)) . ")");
            $s->execute();
            $ins = $pdo->prepare("INSERT IGNORE INTO rombel_subject_teachers
                (rombel_id, subject_id, teacher_id, semester) VALUES (:r,:s,:t,:sem)");
            foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $newSubj = $subjMap[(int)$r['subject_id']] ?? null;
                if (!$newSubj) continue;
                $ins->execute([
                    'r'   => $rombelMap[(int)$r['rombel_id']],
                    's'   => $newSubj,
                    't'   => $r['teacher_id'],
                    'sem' => $r['semester'],
                ]);
            }
        }

        // ---- teacher_years (carry guru list into the new TA) ----
        $s = $pdo->prepare("SELECT teacher_id FROM teacher_years WHERE academic_year_id = :y");
        $s->execute(['y' => $copy_from]);
        $ins = $pdo->prepare("INSERT IGNORE INTO teacher_years (teacher_id, academic_year_id) VALUES (:t,:y)");
        foreach ($s->fetchAll(PDO::FETCH_COLUMN) as $tid) {
            $ins->execute(['t' => $tid, 'y' => $newId]);
        }

        // ---- kkm_settings ----
        $s = $pdo->prepare("SELECT jenjang, grade, min_val, max_val, predikat
                            FROM kkm_settings WHERE academic_year_id = :y");
        $s->execute(['y' => $copy_from]);
        $ins = $pdo->prepare("INSERT INTO kkm_settings
            (academic_year_id, jenjang, grade, min_val, max_val, predikat)
            VALUES (:y,:j,:g,:mn,:mx,:p)");
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $ins->execute([
                'y'=>$newId,'j'=>$r['jenjang'],'g'=>$r['grade'],
                'mn'=>$r['min_val'],'mx'=>$r['max_val'],'p'=>$r['predikat']
            ]);
        }

        // ---- character_aspects ----
        $s = $pdo->prepare("SELECT nama, kategori FROM character_aspects WHERE academic_year_id = :y");
        $s->execute(['y' => $copy_from]);
        $ins = $pdo->prepare("INSERT INTO character_aspects (academic_year_id, nama, kategori) VALUES (:y,:n,:k)");
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $ins->execute(['y'=>$newId,'n'=>$r['nama'],'k'=>$r['kategori']]);
        }

        // ---- extracurriculars ----
        $s = $pdo->prepare("SELECT nama, pembina, jadwal, deskripsi, is_active
                            FROM extracurriculars WHERE academic_year_id = :y");
        $s->execute(['y' => $copy_from]);
        $ins = $pdo->prepare("INSERT INTO extracurriculars
            (academic_year_id, nama, pembina, jadwal, deskripsi, is_active)
            VALUES (:y,:n,:p,:j,:d,:a)");
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $ins->execute([
                'y'=>$newId,'n'=>$r['nama'],'p'=>$r['pembina'],
                'j'=>$r['jadwal'],'d'=>$r['deskripsi'],'a'=>$r['is_active'],
            ]);
        }

        // ---- report_templates + report_signatures ----
        $s = $pdo->prepare("SELECT jenjang, layout_json, header_img, footer_img
                            FROM report_templates WHERE academic_year_id = :y");
        $s->execute(['y' => $copy_from]);
        $ins = $pdo->prepare("INSERT INTO report_templates
            (academic_year_id, jenjang, layout_json, header_img, footer_img)
            VALUES (:y,:j,:l,:h,:f)");
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $ins->execute([
                'y'=>$newId,'j'=>$r['jenjang'],'l'=>$r['layout_json'],
                'h'=>$r['header_img'],'f'=>$r['footer_img'],
            ]);
        }
        $s = $pdo->prepare("SELECT jenjang, slot, nama, jabatan, ttd_path
                            FROM report_signatures WHERE academic_year_id = :y");
        $s->execute(['y' => $copy_from]);
        $ins = $pdo->prepare("INSERT INTO report_signatures
            (academic_year_id, jenjang, slot, nama, jabatan, ttd_path)
            VALUES (:y,:j,:s,:n,:jb,:t)");
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $ins->execute([
                'y'=>$newId,'j'=>$r['jenjang'],'s'=>$r['slot'],
                'n'=>$r['nama'],'jb'=>$r['jabatan'],'t'=>$r['ttd_path'],
            ]);
        }

        // NOTE: assessment data (attendance, grades_daily, grade_descriptions,
        // final_grades, character_evaluations, general_evaluations,
        // extracurricular_grades, achievements, wali_notes) is intentionally
        // NOT copied — those are produced fresh in the new tahun ajaran.
    }

    if ($set_active) {
        $pdo->exec("UPDATE academic_years SET is_active = 0");
        $pdo->prepare("UPDATE academic_years SET is_active = 1 WHERE id = :id")
            ->execute(['id' => $newId]);
        $_SESSION['scope']['year_id'] = $newId;
    }

    $pdo->commit();
    audit('create', 'academic_year:' . $label,
          ['copy_from' => $copy_from, 'deep_copy' => (bool)$copy_from]);
    flash('success',
          $copy_from
            ? "Tahun ajaran $label dibuat (salinan independen dari TA sumber)."
            : "Tahun ajaran $label dibuat (kosong).");
    redirect('admin/academic_years.php');
}


        if ($op === 'edit_dates') {
            require_administrator();
            $yearId = (int)($_POST['year_id'] ?? 0);
            $ganjilStart = req_str($_POST, 'ganjil_start', 10);
            $ganjilEnd   = req_str($_POST, 'ganjil_end', 10);
            $genapStart  = req_str($_POST, 'genap_start', 10);
            $genapEnd    = req_str($_POST, 'genap_end', 10);

            $validateDate = function (string $value, string $label): string {
                $d = DateTime::createFromFormat('Y-m-d', $value);
                if (!$d || $d->format('Y-m-d') !== $value) {
                    throw new RuntimeException("$label harus format YYYY-MM-DD.");
                }
                return $value;
            };

            $ganjilStart = $validateDate($ganjilStart, 'Semester Ganjil mulai');
            $ganjilEnd   = $validateDate($ganjilEnd,   'Semester Ganjil selesai');
            $genapStart  = $validateDate($genapStart,  'Semester Genap mulai');
            $genapEnd    = $validateDate($genapEnd,    'Semester Genap selesai');

            if ($ganjilStart > $ganjilEnd) {
                throw new RuntimeException('Semester Ganjil: tanggal mulai harus sebelum atau sama dengan tanggal selesai.');
            }
            if ($genapStart > $genapEnd) {
                throw new RuntimeException('Semester Genap: tanggal mulai harus sebelum atau sama dengan tanggal selesai.');
            }

            $pdo->beginTransaction();
            $check = $pdo->prepare("SELECT 1 FROM semesters_state WHERE academic_year_id = :y AND semester = :s");
            $update = $pdo->prepare("UPDATE semesters_state SET start_date = :sd, end_date = :ed WHERE academic_year_id = :y AND semester = :s");
            $insert = $pdo->prepare("INSERT INTO semesters_state (academic_year_id, semester, start_date, end_date) VALUES (:y, :s, :sd, :ed)");

            foreach ([
                ['semester' => 'ganjil', 'start' => $ganjilStart, 'end' => $ganjilEnd],
                ['semester' => 'genap',  'start' => $genapStart,  'end' => $genapEnd],
            ] as $row) {
                $check->execute(['y' => $yearId, 's' => $row['semester']]);
                if ((int)$check->fetchColumn() === 1) {
                    $update->execute(['y' => $yearId, 's' => $row['semester'], 'sd' => $row['start'], 'ed' => $row['end']]);
                } else {
                    $insert->execute(['y' => $yearId, 's' => $row['semester'], 'sd' => $row['start'], 'ed' => $row['end']]);
                }
            }

            $pdo->commit();
            audit('edit_dates', 'academic_year:' . $yearId);
            flash('success', 'Tanggal semester berhasil diperbarui.');
            redirect('admin/academic_years.php');
        }

        if ($op === 'set_active') {
            require_administrator();
            $id = (int)($_POST['id'] ?? 0);
            $pdo->beginTransaction();
            $pdo->exec("UPDATE academic_years SET is_active = 0");
            $pdo->prepare("UPDATE academic_years SET is_active = 1 WHERE id = :id")->execute(['id' => $id]);
            $pdo->commit();
            $_SESSION['scope']['year_id'] = $id;
            audit('set_active', 'academic_year:' . $id);
            flash('success', 'Tahun ajaran aktif diperbarui.');
            redirect('admin/academic_years.php');
        }

        if ($op === 'toggle_lock') {
            require_administrator();
            $yearId   = (int)($_POST['year_id'] ?? 0);
            $semester = (string)($_POST['semester'] ?? '');
            if (!in_array($semester, ['ganjil','genap'], true)) throw new RuntimeException('Semester invalid.');
            // Ensure a row exists, then flip the per-semester lock.
            [$startDate, $endDate] = semester_date_window($yearId, $semester);
            $pdo->prepare(
                "INSERT IGNORE INTO semesters_state (academic_year_id, semester, start_date, end_date)
                 VALUES (:y, :s, :sd, :ed)"
            )->execute(['y' => $yearId, 's' => $semester, 'sd' => $startDate, 'ed' => $endDate]);
            $pdo->prepare("UPDATE semesters_state SET semester_locked = 1 - semester_locked WHERE academic_year_id = :y AND semester = :s")
                ->execute(['y' => $yearId, 's' => $semester]);
            audit('toggle_semester_lock', "year:$yearId/$semester");
            flash('success', 'Status kunci semester diperbarui.');
            redirect('admin/academic_years.php');
        }

        if ($op === 'delete') {
            require_administrator();
            $id = (int)($_POST['id'] ?? 0);
            // Refuse to delete the active year
            $isActive = (int)$pdo->prepare("SELECT is_active FROM academic_years WHERE id = :id");
            $stmt = $pdo->prepare("SELECT is_active FROM academic_years WHERE id = :id");
            $stmt->execute(['id' => $id]);
            if ((int)$stmt->fetchColumn() === 1) throw new RuntimeException('Tidak bisa menghapus tahun ajaran yang aktif.');
            $pdo->prepare("DELETE FROM academic_years WHERE id = :id")->execute(['id' => $id]);
            audit('delete', 'academic_year:' . $id);
            flash('success', 'Tahun ajaran dihapus.');
            redirect('admin/academic_years.php');
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
}

// ---------- DATA ----------
$years = $pdo->query(
    "SELECT y.*,
        COALESCE((SELECT semester_locked FROM semesters_state WHERE academic_year_id=y.id AND semester='ganjil'),0) AS gj_lock,
        COALESCE((SELECT semester_locked FROM semesters_state WHERE academic_year_id=y.id AND semester='genap'),0)  AS gn_lock,
        COALESCE((SELECT CONCAT_WS(' - ', start_date, end_date) FROM semesters_state WHERE academic_year_id=y.id AND semester='ganjil'), '—') AS gj_range,
        COALESCE((SELECT CONCAT_WS(' - ', start_date, end_date) FROM semesters_state WHERE academic_year_id=y.id AND semester='genap'),  '—') AS gn_range
     FROM academic_years y ORDER BY label DESC"
)->fetchAll();

if ($editYearId) {
    $stmt = $pdo->prepare("SELECT id, label FROM academic_years WHERE id = :id");
    $stmt->execute(['id' => $editYearId]);
    $editYear = $stmt->fetch();
    if ($editYear) {
        $stmt = $pdo->prepare("SELECT semester, start_date, end_date FROM semesters_state WHERE academic_year_id = :y ORDER BY FIELD(semester, 'ganjil', 'genap')");
        $stmt->execute(['y' => $editYearId]);
        foreach ($stmt->fetchAll() as $row) {
            $editDates[$row['semester']] = [
                'start' => (string)$row['start_date'],
                'end'   => (string)$row['end_date'],
            ];
        }
    }
}

$page_title = 'Tahun Ajaran';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<?php if ($u['role'] === 'administrator'): ?>
<div class="card mb-4">
  <div class="card-header"><h3 class="card-title">Tambah Tahun Ajaran</h3></div>
  <div class="card-body">
    <form method="post" class="row" style="align-items: end">
      <?= csrf_field() ?><input type="hidden" name="op" value="create">
      <div class="field" style="flex: 1"><label class="label">Label *</label><input class="input" name="label" placeholder="2025/2026" pattern="\d{4}/\d{4}" required></div>
      <div class="field" style="flex: 1">
        <label class="label">Salin dari</label>
        <select class="select" name="copy_from">
          <option value="">— Mulai kosong —</option>
          <?php foreach ($years as $y): ?>
            <option value="<?= (int)$y['id'] ?>"><?= esc($y['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="flex: 1"><label class="label">Ganjil mulai *</label><input class="input" type="date" name="ganjil_start" required></div>
      <div class="field" style="flex: 1"><label class="label">Ganjil selesai *</label><input class="input" type="date" name="ganjil_end" required></div>
      <div class="field" style="flex: 1"><label class="label">Genap mulai *</label><input class="input" type="date" name="genap_start" required></div>
      <div class="field" style="flex: 1"><label class="label">Genap selesai *</label><input class="input" type="date" name="genap_end" required></div>
      <div class="field" style="flex: 0 0 auto">
        <label class="checkbox-row"><input type="checkbox" name="set_active" value="1"> Jadikan aktif</label>
      </div>
      <div class="field" style="flex: 0 0 auto"><button class="btn btn-primary" type="submit">Buat</button></div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if ($u['role'] === 'administrator' && $editYear): ?>
<div class="card mb-4">
  <div class="card-header"><h3 class="card-title">Edit Tanggal Semester — <?= esc($editYear['label']) ?></h3></div>
  <div class="card-body">
    <form method="post" class="row" style="align-items:end; gap: .75rem">
      <?= csrf_field() ?>
      <input type="hidden" name="op" value="edit_dates">
      <input type="hidden" name="year_id" value="<?= (int)$editYear['id'] ?>">
      <div class="field" style="flex:1; min-width:220px">
        <label class="label">Ganjil mulai</label>
        <input class="input" type="date" name="ganjil_start" value="<?= esc($editDates['ganjil']['start'] ?? '') ?>" required>
      </div>
      <div class="field" style="flex:1; min-width:220px">
        <label class="label">Ganjil selesai</label>
        <input class="input" type="date" name="ganjil_end" value="<?= esc($editDates['ganjil']['end'] ?? '') ?>" required>
      </div>
      <div class="field" style="flex:1; min-width:220px">
        <label class="label">Genap mulai</label>
        <input class="input" type="date" name="genap_start" value="<?= esc($editDates['genap']['start'] ?? '') ?>" required>
      </div>
      <div class="field" style="flex:1; min-width:220px">
        <label class="label">Genap selesai</label>
        <input class="input" type="date" name="genap_end" value="<?= esc($editDates['genap']['end'] ?? '') ?>" required>
      </div>
      <div class="field" style="flex:0 0 auto">
        <button class="btn btn-primary" type="submit">Simpan</button>
        <a class="btn btn-ghost" href="<?= esc(url('admin/academic_years.php')) ?>">Batal</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><h3 class="card-title">Daftar Tahun Ajaran</h3></div>
  <div class="table-wrap">
    <table class="t">
      <thead><tr>
        <th>Label</th><th>Status</th>
        <th>Semester Ganjil</th>
        <th>Semester Genap</th>
        <th style="text-align:right">Aksi</th>
      </tr></thead>
      <tbody>
      <?php foreach ($years as $y): ?>
        <tr>
          <td><strong><?= esc($y['label']) ?></strong></td>
          <td><?= $y['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge">Nonaktif</span>' ?></td>
          <?php foreach ([['ganjil', (int)$y['gj_lock']], ['genap', (int)$y['gn_lock']]] as [$sem, $val]): ?>
            <td>
              <?php if ($u['role'] === 'administrator'): ?>
                <form method="post" style="display:inline-flex; align-items:center; gap:.5rem; margin:0">
                  <?= csrf_field() ?>
                  <input type="hidden" name="op" value="toggle_lock">
                  <input type="hidden" name="year_id" value="<?= (int)$y['id'] ?>">
                  <input type="hidden" name="semester" value="<?= esc($sem) ?>">
                  <label class="switch" title="<?= $val ? 'Klik untuk membuka kunci' : 'Klik untuk mengunci' ?> Semester <?= esc(ucfirst($sem)) ?>">
                    <input type="checkbox" <?= $val ? 'checked' : '' ?> onchange="this.form.submit()">
                    <span class="slider"></span>
                  </label>
                  <span class="badge <?= $val ? 'badge-danger' : 'badge-success' ?>"><?= $val ? '🔒 Terkunci' : '🔓 Terbuka' ?></span>
                </form>
              <?php else: ?>
                <span class="badge <?= $val ? 'badge-danger' : 'badge-success' ?>"><?= $val ? '🔒 Terkunci' : '🔓 Terbuka' ?></span>
              <?php endif; ?>
              <div class="text-muted" style="font-size:.85rem; margin-top:.4rem">
                <?= esc($sem === 'ganjil' ? $y['gj_range'] : $y['gn_range']) ?>
              </div>
            </td>
          <?php endforeach; ?>
          <td style="text-align:right">
            <?php if ($u['role'] === 'administrator'): ?>
              <a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/academic_years.php?edit=' . (int)$y['id'])) ?>">Edit Tanggal</a>
              <?php if (!$y['is_active']): ?>
              <form method="post" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="op" value="set_active"><input type="hidden" name="id" value="<?= (int)$y['id'] ?>">
                <button class="btn btn-secondary btn-sm" type="submit">Aktifkan</button>
              </form>
              <form method="post" style="display:inline" data-confirm="Hapus tahun ajaran <?= esc($y['label']) ?>? Semua rombel & nilai akan ikut terhapus.">
                <?= csrf_field() ?><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= (int)$y['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
              </form>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
