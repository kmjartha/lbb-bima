<?php
/**
 * Stage 8 — Leger + Rapor PDF + Display Settings helpers.
 * Centralises:
 *   - Aggregate final grades per (rombel, semester, period) in a leger-friendly shape
 *   - Attendance recap per student per semester
 *   - Ranking computation (kelas + paralel)
 *   - Report template + signatures fetch
 *   - File upload helper for header/footer/TTD images
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/scope.php';
require_once __DIR__ . '/grading_helpers.php';
require_once __DIR__ . '/final_grades_helpers.php';
require_once __DIR__ . '/wali_helpers.php';

/** Uploads root (relative to /public). */
function uploads_dir(string $sub = ''): string
{
    $base = realpath(__DIR__ . '/../public/uploads') ?: (__DIR__ . '/../public/uploads');
    return rtrim($base, '/') . ($sub !== '' ? '/' . trim($sub, '/') : '');
}

/** URL-friendly path to an uploaded file (relative to /public). */
function uploads_url(string $relPath): string
{
    return url('uploads/' . ltrim($relPath, '/'));
}

/** Save an uploaded image into /public/uploads/<sub> and return relative path. */
function save_image_upload(array $file, string $sub, string $prefix = ''): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload gagal (error code ' . (int)($file['error'] ?? -1) . ').');
    }
    if (($file['size'] ?? 0) > 4 * 1024 * 1024) {
        throw new RuntimeException('Ukuran file terlalu besar (maks 4 MB).');
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info) throw new RuntimeException('File bukan gambar yang valid.');
    $allowed = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    if (!isset($allowed[$info[2]])) throw new RuntimeException('Format gambar tidak didukung (gunakan PNG/JPG/GIF/WEBP).');
    $ext = $allowed[$info[2]];
    $dir = uploads_dir($sub);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $name = ($prefix !== '' ? $prefix . '_' : '') . bin2hex(random_bytes(6)) . '.' . $ext;
    $rel  = trim($sub, '/') . '/' . $name;
    if (!@move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Gagal menyimpan file upload.');
    }
    return $rel;
}

/** Attendance recap (jumlah H/I/S/A) per student in (rombel, semester, year). */
function attendance_summary_for_rombel(int $rombelId, string $semester, int $yearId): array
{
    $st = db()->prepare(
        "SELECT start_date, end_date
         FROM semesters_state
         WHERE academic_year_id = :y AND semester = :s"
    );
    $st->execute(['y' => $yearId, 's' => $semester]);
    $period = $st->fetch();
    if ($period && $period['start_date'] && $period['end_date']) {
        $from = $period['start_date'];
        $to = $period['end_date'];
    } else {
        // Legacy fallback: derive reasonable semester windows from the year label.
        $row = db()->prepare("SELECT label FROM academic_years WHERE id=:y");
        $row->execute(['y' => $yearId]);
        $label = (string)$row->fetchColumn(); // e.g. 2025/2026
        $parts = explode('/', $label);
        $y1 = (int)($parts[0] ?? date('Y'));
        $y2 = (int)($parts[1] ?? ($y1 + 1));
        if ($semester === 'ganjil') { $from = "$y1-07-01"; $to = "$y1-12-31"; }
        else                        { $from = "$y2-01-01"; $to = "$y2-06-30"; }
    }

    $st = db()->prepare(
        "SELECT student_id,
                SUM(status='H') AS h,
                SUM(status='I') AS i,
                SUM(status='S') AS s,
                SUM(status='A') AS a,
                COUNT(*)        AS total
         FROM attendance
         WHERE rombel_id = :r AND tanggal BETWEEN :from AND :to
         GROUP BY student_id"
    );
    $st->execute(['r' => $rombelId, 'from' => $from, 'to' => $to]);
    $out = [];
    foreach ($st->fetchAll() as $row) {
        $out[(int)$row['student_id']] = [
            'h' => (int)$row['h'], 'i' => (int)$row['i'],
            's' => (int)$row['s'], 'a' => (int)$row['a'],
            'total' => (int)$row['total'],
        ];
    }
    return $out;
}

/**
 * Final-grade matrix for leger:
 * Returns:
 *   [
 *     'subjects' => [ [id, kode, nama], ... ],   // ordered by nama
 *     'data'     => [ student_id => [ subject_id => ['si','pe','ke','status'] ] ],
 *     'avg'      => [ student_id => [ 'si','pe','ke','overall' ] ],
 *   ]
 *
 * Uses period_kind from $period (PTS or PAS).
 */
function leger_matrix(int $rombelId, string $semester, string $period): array
{
    $pdo = db();

    $stSub = $pdo->prepare(
        "SELECT DISTINCT s.id, s.kode, s.nama, s.category_id
         FROM rombel_subject_teachers rst
         JOIN subjects s ON s.id = rst.subject_id
         WHERE rst.rombel_id = :r
           AND (rst.semester IS NULL OR rst.semester = :sem)
           AND s.deleted_at IS NULL
         ORDER BY s.nama"
    );
    $stSub->execute(['r' => $rombelId, 'sem' => $semester]);
    $subjects = $stSub->fetchAll();

    $stFg = $pdo->prepare(
        "SELECT student_id, subject_id,
                nilai_sikap AS si, nilai_pengetahuan AS pe, nilai_keterampilan AS ke,
                catatan_guru AS note, status
         FROM final_grades
         WHERE rombel_id = :r AND semester = :sem AND period_kind = :p"
    );
    $stFg->execute(['r' => $rombelId, 'sem' => $semester, 'p' => $period]);

    $data = [];
    foreach ($stFg->fetchAll() as $row) {
        $si = $row['si'] !== null ? (float)$row['si'] : null;
        $pe = $row['pe'] !== null ? (float)$row['pe'] : null;
        $ke = $row['ke'] !== null ? (float)$row['ke'] : null;
        $data[(int)$row['student_id']][(int)$row['subject_id']] = [
            'si' => $si,
            'pe' => $pe,
            'ke' => $ke,
            'overall' => spk_overall($si, $pe, $ke),
            'note' => $row['note'] === null ? null : (string)$row['note'],
            'status' => (string)$row['status'],
        ];
    }

    // Compute per-student averages across mapel
    $avg = [];
    foreach ($data as $sid => $bySubj) {
        $sums = ['si'=>[0.0,0], 'pe'=>[0.0,0], 'ke'=>[0.0,0]];
        foreach ($bySubj as $row) {
            foreach (['si','pe','ke'] as $k) {
                if ($row[$k] !== null) { $sums[$k][0] += $row[$k]; $sums[$k][1]++; }
            }
        }
        $a = [];
        foreach ($sums as $k => [$num, $cnt]) {
            $a[$k] = $cnt > 0 ? round($num / $cnt, 2) : null;
        }
        // Overall = mean of Sikap + Pengetahuan + Keterampilan (gabungan SPK).
        // Inilah nilai akhir tunggal yang ditampilkan di rapor siswa per mapel,
        // dan dipakai untuk ranking & predikat akhir.
        $vals = array_filter([$a['si'], $a['pe'], $a['ke']], fn($v) => $v !== null);
        $a['overall'] = $vals ? round(array_sum($vals) / count($vals), 2) : null;
        $avg[$sid] = $a;
    }

    return ['subjects' => $subjects, 'data' => $data, 'avg' => $avg];
}

/**
 * Compute ranking inside a list of [student_id => overall_value|null].
 * Returns [student_id => rank|null]; null for students without overall.
 * Ties share the same rank, then skip (1,2,2,4,...).
 */
function rank_overall(array $overallByStudent): array
{
    $pairs = [];
    foreach ($overallByStudent as $sid => $val) {
        if ($val !== null) $pairs[] = ['sid' => $sid, 'v' => (float)$val];
    }
    usort($pairs, fn($a, $b) => $b['v'] <=> $a['v']);
    $ranks = [];
    $prev = null; $rank = 0; $i = 0;
    foreach ($pairs as $p) {
        $i++;
        if ($prev === null || $p['v'] < $prev) { $rank = $i; }
        $ranks[$p['sid']] = $rank;
        $prev = $p['v'];
    }
    foreach ($overallByStudent as $sid => $v) {
        if (!isset($ranks[$sid])) $ranks[$sid] = null;
    }
    return $ranks;
}

/**
 * Paralel ranking: same jenjang + tingkat + academic_year + semester + period.
 * Returns [student_id => rank|null].
 */
function rank_paralel(string $jenjang, int $tingkat, int $yearId, string $semester, string $period): array
{
    $pdo = db();
    // Get all rombels in the paralel
    $st = $pdo->prepare(
        "SELECT id FROM rombel
         WHERE academic_year_id = :y AND jenjang = :j AND tingkat = :t AND deleted_at IS NULL"
    );
    $st->execute(['y' => $yearId, 'j' => $jenjang, 't' => $tingkat]);
    $rids = array_column($st->fetchAll(), 'id');
    if (!$rids) return [];
    $place = implode(',', array_fill(0, count($rids), '?'));

    // Average pe + ke per student across all subjects in this paralel/period
    $sql = "SELECT student_id,
                  AVG((COALESCE(nilai_pengetahuan,0) + COALESCE(nilai_keterampilan,0)) /
                       NULLIF((CASE WHEN nilai_pengetahuan IS NULL THEN 0 ELSE 1 END +
                               CASE WHEN nilai_keterampilan IS NULL THEN 0 ELSE 1 END),0)) AS overall
            FROM final_grades
            WHERE rombel_id IN ($place) AND semester = ? AND period_kind = ?
            GROUP BY student_id";
    $params = array_merge($rids, [$semester, $period]);
    $st2 = $pdo->prepare($sql);
    $st2->execute($params);

    $overall = [];
    foreach ($st2->fetchAll() as $row) {
        $overall[(int)$row['student_id']] = $row['overall'] !== null ? (float)$row['overall'] : null;
    }
    return rank_overall($overall);
}

/* ---------- Report templates & signatures ---------- */

function report_template_for(string $jenjang): array
{
    $yearId = active_scope()['year_id'];
    $st = db()->prepare("SELECT * FROM report_templates WHERE academic_year_id = :y AND jenjang = :j");
    $st->execute(['y' => $yearId, 'j' => $jenjang]);
    $row = $st->fetch();
    if ($row) return $row;
    db()->prepare("INSERT INTO report_templates (academic_year_id, jenjang) VALUES (:y,:j)")
        ->execute(['y' => $yearId, 'j' => $jenjang]);
    $st->execute(['y' => $yearId, 'j' => $jenjang]);
    return $st->fetch() ?: ['jenjang' => $jenjang, 'layout_json' => null, 'header_img' => null, 'footer_img' => null];
}

function report_template_save(string $jenjang, ?string $headerImg, ?string $footerImg, ?array $layout, ?array $hidden = null): void
{
    $yearId = active_scope()['year_id'];
    db()->prepare(
        "UPDATE report_templates SET header_img=:h, footer_img=:f, layout_json=:l, layout_hidden_json=:hd
         WHERE academic_year_id = :y AND jenjang=:j"
    )->execute([
        'h'  => $headerImg, 'f' => $footerImg,
        'l'  => $layout ? json_encode($layout, JSON_UNESCAPED_UNICODE) : null,
        'hd' => $hidden !== null ? json_encode(array_values($hidden), JSON_UNESCAPED_UNICODE) : null,
        'y'  => $yearId,
        'j'  => $jenjang,
    ]);
}

/**
 * Resolve layout order + hidden set from a template row.
 * Returns ['order' => [..keys..], 'hidden' => [key => true, ...]].
 * 'identitas' is always forced visible.
 */
function rapor_layout_resolve(?array $tpl): array
{
    $order = (!empty($tpl['layout_json']))
        ? (array)json_decode($tpl['layout_json'], true)
        : rapor_default_layout();
    $hiddenList = (!empty($tpl['layout_hidden_json']))
        ? (array)json_decode($tpl['layout_hidden_json'], true)
        : [];
    $hidden = [];
    foreach ($hiddenList as $k) {
        $k = (string)$k;
        if ($k !== 'identitas') $hidden[$k] = true;
    }
    return ['order' => $order, 'hidden' => $hidden];
}

function report_signatures_for(string $jenjang, ?int $rombelId = null): array
{
    $yearId = active_scope()['year_id'];
    $st = db()->prepare("SELECT * FROM report_signatures WHERE academic_year_id = :y AND jenjang = :j");
    $st->execute(['y' => $yearId, 'j' => $jenjang]);
    $rows = $st->fetchAll();
    $out = [];
    foreach (['wali','kepsek','direktur','parent'] as $slot) {
        $found = null;
        foreach ($rows as $r) if ($r['slot'] === $slot) { $found = $r; break; }
        $out[$slot] = $found ?: ['slot' => $slot, 'jenjang' => $jenjang, 'nama' => null, 'jabatan' => null, 'ttd_path' => null];
    }

    if ($rombelId !== null) {
        $st = db()->prepare(
            "SELECT u.id, u.nama, u.ttd_path
               FROM rombel r
               LEFT JOIN users u ON u.id = r.wali_id
              WHERE r.id = :r"
        );
        $st->execute(['r' => $rombelId]);
        $wali = $st->fetch();
        if ($wali && $wali['id']) {
            $out['wali'] = array_merge($out['wali'], [
                'nama'     => $wali['nama'] ?: $out['wali']['nama'],
                'jabatan'  => $out['wali']['jabatan'] ?: 'Wali Kelas',
                'ttd_path' => $wali['ttd_path'] ?? $out['wali']['ttd_path'],
            ]);
        }
    }

    return $out;
}

function report_signature_save(string $jenjang, string $slot, ?string $nama, ?string $jabatan, ?string $ttdPath): void
{
    $yearId = active_scope()['year_id'];
    $pdo = db();
    $st = $pdo->prepare("SELECT id FROM report_signatures WHERE academic_year_id = :y AND jenjang = :j AND slot = :s");
    $st->execute(['y' => $yearId, 'j' => $jenjang, 's' => $slot]);
    $id = (int)($st->fetchColumn() ?: 0);
    if ($id) {
        $pdo->prepare("UPDATE report_signatures SET nama=:n, jabatan=:jb, ttd_path=:t WHERE id=:i")
            ->execute(['n'=>$nama,'jb'=>$jabatan,'t'=>$ttdPath,'i'=>$id]);
    } else {
        $pdo->prepare(
            "INSERT INTO report_signatures (academic_year_id, jenjang, slot, nama, jabatan, ttd_path)
             VALUES (:y,:j,:s,:n,:jb,:t)"
        )->execute(['y'=>$yearId,'j'=>$jenjang,'s'=>$slot,'n'=>$nama,'jb'=>$jabatan,'t'=>$ttdPath]);
    }
}

/** Default layout for the rapor body (sections in order). */
function rapor_default_layout(): array
{
    return [
        'identitas',
        'character',
        'academic',
        'extracurricular',
        'attendance',
        'wali_note',
        'general_eval',
        'signatures',
    ];
}

/** Get a single student row. */
function student_by_id(int $sid): ?array
{
    $yearId = active_scope()['year_id'];
    $st = db()->prepare("SELECT * FROM students WHERE id=:i AND academic_year_id = :y AND deleted_at IS NULL");
    $st->execute(['i' => $sid, 'y' => $yearId]);
    $r = $st->fetch();
    return $r ?: null;
}

/** Whether the given (rombel, semester, period) has at least one published row. */
function rapor_is_published(int $rombelId, int $studentId, string $semester, string $period, int $yearId): bool
{
    $st = db()->prepare(
        "SELECT 1 FROM final_grades fg
         JOIN rombel r ON r.id = fg.rombel_id
         WHERE fg.rombel_id = :r AND fg.student_id = :st
           AND fg.semester = :sem AND fg.period_kind = :p
           AND fg.status = 'published'
           AND r.academic_year_id = :y LIMIT 1"
    );
    $st->execute(['r'=>$rombelId,'st'=>$studentId,'sem'=>$semester,'p'=>$period,'y'=>$yearId]);
    return (bool)$st->fetchColumn();
}

/** KKM scale rows for a jenjang ordered desc by min_val. */
function kkm_scale(string $jenjang): array
{
    $yearId = active_scope()['year_id'];
    $st = db()->prepare("SELECT grade, min_val, max_val, predikat FROM kkm_settings WHERE academic_year_id = :y AND jenjang=:j ORDER BY min_val DESC");
    $st->execute(['y' => $yearId, 'j' => $jenjang]);
    return $st->fetchAll();
}

/** All character evals for a student (joined with aspect). */
function character_evals_for_student(int $rombelId, int $studentId, string $sem, string $period, ?string $jenjang = null): array
{
    $yearId = active_scope()['year_id'];
    $query = 
        "SELECT ce.*, ca.nama AS aspek_nama, ca.kategori
         FROM character_evaluations ce
         JOIN character_aspects ca ON ca.id = ce.aspect_id AND ca.academic_year_id = :y";
    if ($jenjang) {
        $query .= " AND ca.jenjang = :j";
    }
    $query .= 
        " WHERE ce.rombel_id=:r AND ce.student_id=:st AND ce.semester=:sem AND ce.period_kind=:p
         ORDER BY FIELD(ca.kategori,'Spiritual and morality','Discipline','Manner','Obedience','Focus and Confidence','spiritual','sosial'), ca.nama";

    $params = ['y'=>$yearId,'r'=>$rombelId,'st'=>$studentId,'sem'=>$sem,'p'=>$period];
    if ($jenjang) {
        $params['j'] = $jenjang;
    }
    $st = db()->prepare($query);
    $st->execute($params);
    return $st->fetchAll();
}

/** Ekskul grades for a student in active TA + semester. */
function ekskul_grades_for_student(int $studentId, string $sem, int $yearId): array
{
    $st = db()->prepare(
        "SELECT eg.*, e.nama AS ekskul_nama
         FROM extracurricular_grades eg
         JOIN extracurriculars e ON e.id = eg.extracurricular_id
         WHERE eg.student_id=:s AND eg.semester=:sem AND eg.academic_year_id=:y
         ORDER BY e.nama"
    );
    $st->execute(['s'=>$studentId,'sem'=>$sem,'y'=>$yearId]);
    return $st->fetchAll();
}

/** Subjects ordered by category for a rombel + semester (used in rapor body). */
function subjects_grouped_for_rombel(int $rombelId, string $semester): array
{
    $yearId = active_scope()['year_id'];
    $st = db()->prepare(
        "SELECT DISTINCT s.id, s.kode, s.nama, s.category_id, sc.nama AS kategori_nama
         FROM rombel_subject_teachers rst
         JOIN subjects s          ON s.id = rst.subject_id AND s.academic_year_id = :y
         LEFT JOIN subject_categories sc ON sc.id = s.category_id
         WHERE rst.rombel_id = :r
           AND (rst.semester IS NULL OR rst.semester = :sem)
           AND s.deleted_at IS NULL
         ORDER BY sc.nama, s.nama"
    );
    $st->execute(['y'=>$yearId, 'r' => $rombelId, 'sem' => $semester]);
    $rows = $st->fetchAll();
    $out = [];
    foreach ($rows as $r) {
        $cat = $r['kategori_nama'] ?? 'Lainnya';
        $out[$cat][] = $r;
    }
    return $out;
}
