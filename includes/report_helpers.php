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
        "SELECT DISTINCT s.id, s.kode, s.nama, s.category_id, e.kode AS elective_kode
         FROM rombel_subject_teachers rst
         JOIN subjects s ON s.id = rst.subject_id
         LEFT JOIN elective_classes ec ON ec.id = s.elective_class_id
         LEFT JOIN electives e ON e.id = ec.elective_id
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
                catatan_guru AS note, status, image_path
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
            'image_path' => $row['image_path'] === null ? null : (string)$row['image_path'],
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
    $pdo = db();
    $st = $pdo->prepare("SELECT * FROM report_signatures WHERE academic_year_id = :y AND jenjang = :j");
    $st->execute(['y' => $yearId, 'j' => $jenjang]);
    $rows = $st->fetchAll();

    $waliData = null;
    if ($rombelId !== null) {
        $st2 = $pdo->prepare(
            "SELECT u.nama, u.ttd_path
             FROM rombel r
             JOIN users u ON u.id = r.wali_id
             WHERE r.id = :rid AND r.academic_year_id = :y AND u.deleted_at IS NULL
             LIMIT 1"
        );
        $st2->execute(['rid' => $rombelId, 'y' => $yearId]);
        $waliData = $st2->fetch() ?: null;
    }

    $out = [];
    foreach (['wali','kepsek','direktur','parent'] as $slot) {
        $found = null;
        foreach ($rows as $r) {
            if ($r['slot'] === $slot) {
                $found = $r;
                break;
            }
        }
        $out[$slot] = $found ?: ['slot' => $slot, 'jenjang' => $jenjang, 'nama' => null, 'jabatan' => null, 'ttd_path' => null];

        if ($slot === 'wali' && $waliData) {
            if (!empty($waliData['nama'])) {
                $out['wali']['nama'] = $waliData['nama'];
            }
            if (!empty($waliData['ttd_path'])) {
                $out['wali']['ttd_path'] = $waliData['ttd_path'];
            }
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
        'attendance',
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

/* =====================================================================
 * Rapor-level publish (Stage 8b — menggantikan publish per-baris
 * final_grades). Sumber kebenaran: tabel rapor_publications.
 *
 * final_grades.status TIDAK LAGI dipakai untuk menentukan visibilitas ke
 * ortu (nilai 'published' pada kolom itu sudah dihentikan penggunaannya
 * per migrasi 2026_09_rapor_level_publish.sql). 'approved' adalah status
 * akhir yang wajar untuk sebuah nilai mapel; publish adalah keputusan
 * TERPISAH di level rapor (per siswa, per semester x periode), boleh
 * dilakukan walau belum semua mapel siswa itu approved (partial publish
 * disengaja — kepsek yang menimbang, sistem tidak blokir).
 * ===================================================================== */

/** Apakah rapor siswa ini published (tampil di Parent Portal)? */
function rapor_is_published(int $rombelId, int $studentId, string $semester, string $period, int $yearId): bool
{
    $st = db()->prepare(
        "SELECT 1 FROM rapor_publications
         WHERE student_id = :st AND rombel_id = :r
           AND semester = :sem AND period_kind = :p
           AND academic_year_id = :y AND status = 'published' LIMIT 1"
    );
    $st->execute(['r'=>$rombelId,'st'=>$studentId,'sem'=>$semester,'p'=>$period,'y'=>$yearId]);
    return (bool)$st->fetchColumn();
}

/**
 * Matrix publish per siswa untuk SATU tahun ajaran, dipakai oleh
 * parent_publish_matrix() di parent_helpers.php (delegasi tipis, supaya
 * signature lama yang dipakai rapor.php tidak perlu berubah).
 * Return: ['ganjil'=>['PTS'=>bool,'PAS'=>bool], 'genap'=>['PTS'=>bool,'PAS'=>bool]]
 */
function rapor_publish_matrix_for_student(int $studentId, int $yearId): array
{
    $out = [
        'ganjil' => ['PTS' => false, 'PAS' => false],
        'genap'  => ['PTS' => false, 'PAS' => false],
    ];
    $st = db()->prepare(
        "SELECT semester, period_kind FROM rapor_publications
         WHERE student_id = :st AND academic_year_id = :y AND status = 'published'"
    );
    $st->execute(['st' => $studentId, 'y' => $yearId]);
    foreach ($st->fetchAll() as $row) {
        $sem = $row['semester'];
        $pk  = $row['period_kind'];
        if (isset($out[$sem][$pk])) $out[$sem][$pk] = true;
    }
    return $out;
}

/**
 * Publish rapor untuk sekumpulan siswa dalam satu (rombel, semester,
 * period, tahun ajaran). Upsert idempoten — memanggil ulang untuk siswa
 * yang sudah published tidak masalah (published_at/by ter-refresh).
 * TIDAK mengecek kelengkapan approval mapel (partial publish diizinkan
 * secara sadar). Return: array student_id yang benar-benar diproses.
 */
function rapor_publish_students(array $studentIds, int $rombelId, string $semester, string $period, int $yearId, int $publishedBy): array
{
    $studentIds = array_values(array_unique(array_map('intval', $studentIds)));
    if (!$studentIds) return [];
    $sql = "INSERT INTO rapor_publications
                (student_id, rombel_id, academic_year_id, semester, period_kind, status, published_by, published_at)
            VALUES (:st, :r, :y, :sem, :p, 'published', :by, NOW())
            ON DUPLICATE KEY UPDATE
                status = 'published', published_by = VALUES(published_by), published_at = VALUES(published_at)";
    $stmt = db()->prepare($sql);
    foreach ($studentIds as $sid) {
        $stmt->execute(['st' => $sid, 'r' => $rombelId, 'y' => $yearId, 'sem' => $semester, 'p' => $period, 'by' => $publishedBy]);
    }
    return $studentIds;
}

/**
 * Batal-publish (kembalikan ke draft) untuk sekumpulan siswa. Baris di
 * rapor_publications TIDAK dihapus (dipertahankan sebagai jejak
 * publish_by/published_at terakhir) — hanya status yang berubah, supaya
 * ortu langsung tidak bisa lihat rapor itu lagi.
 * Return: array student_id yang sebelumnya published dan baru saja
 * di-set balik ke draft (siswa yang memang belum pernah published
 * otomatis dilewati / tidak dihitung).
 */
function rapor_unpublish_students(array $studentIds, int $rombelId, string $semester, string $period, int $yearId): array
{
    $studentIds = array_values(array_unique(array_map('intval', $studentIds)));
    if (!$studentIds) return [];
    $ph = implode(',', array_fill(0, count($studentIds), '?'));
    $find = db()->prepare(
        "SELECT student_id FROM rapor_publications
         WHERE rombel_id = ? AND academic_year_id = ? AND semester = ? AND period_kind = ?
           AND status = 'published' AND student_id IN ($ph)"
    );
    $find->execute(array_merge([$rombelId, $yearId, $semester, $period], $studentIds));
    $affected = array_map('intval', array_column($find->fetchAll(), 'student_id'));
    if (!$affected) return [];

    $ph2 = implode(',', array_fill(0, count($affected), '?'));
    db()->prepare(
        "UPDATE rapor_publications SET status = 'draft'
         WHERE rombel_id = ? AND academic_year_id = ? AND semester = ? AND period_kind = ?
           AND student_id IN ($ph2)"
    )->execute(array_merge([$rombelId, $yearId, $semester, $period], $affected));
    return $affected;
}

/**
 * Set publish/draft untuk SEMUA siswa yang punya rapor (>=1 baris
 * final_grades) dalam satu scope (semester x period x tahun ajaran),
 * opsional dipersempit ke satu rombel, dan (untuk kepsek) otomatis
 * dipersempit ke jenjangnya. Dipakai oleh tombol "Publish/Batal Publish
 * Semua". Tidak mensyaratkan status mapel apapun (partial diizinkan).
 * Return: ['student_ids' => int[], 'rombel_ids' => int[]] yang diproses.
 */
function rapor_publish_scope(array $user, string $semester, string $period, int $yearId, ?int $rombelId, bool $publish, int $actorId): array
{
    $sql = "SELECT DISTINCT fg.student_id, fg.rombel_id
            FROM final_grades fg
            JOIN rombel r ON r.id = fg.rombel_id
            WHERE fg.semester = :sem AND fg.period_kind = :p AND r.academic_year_id = :y";
    $params = ['sem' => $semester, 'p' => $period, 'y' => $yearId];
    if ($rombelId !== null) {
        $sql .= " AND fg.rombel_id = :rid";
        $params['rid'] = $rombelId;
    } elseif (($user['role'] ?? '') === 'kepsek' && !empty($user['jenjang'])) {
        $sql .= " AND r.jenjang = :j";
        $params['j'] = $user['jenjang'];
    }
    $st = db()->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    $byRombel = [];
    foreach ($rows as $row) {
        $byRombel[(int)$row['rombel_id']][] = (int)$row['student_id'];
    }

    $allStudentIds = [];
    foreach ($byRombel as $rid => $studentIds) {
        $done = $publish
            ? rapor_publish_students($studentIds, $rid, $semester, $period, $yearId, $actorId)
            : rapor_unpublish_students($studentIds, $rid, $semester, $period, $yearId);
        $allStudentIds = array_merge($allStudentIds, $done);
    }
    return ['student_ids' => array_values(array_unique($allStudentIds)), 'rombel_ids' => array_keys($byRombel)];
}

/**
 * Peta student_id => bool published untuk satu (rombel, semester, period,
 * tahun ajaran). Dipakai untuk menggabungkan status publish rapor dengan
 * info kelengkapan mapel (dari publish_student_summary()) di halaman
 * Publish Rapor.
 */
function rapor_published_map(int $rombelId, string $semester, string $period, int $yearId): array
{
    $st = db()->prepare(
        "SELECT student_id FROM rapor_publications
         WHERE rombel_id = :r AND semester = :sem AND period_kind = :p
           AND academic_year_id = :y AND status = 'published'"
    );
    $st->execute(['r' => $rombelId, 'sem' => $semester, 'p' => $period, 'y' => $yearId]);
    $out = [];
    foreach ($st->fetchAll() as $row) $out[(int)$row['student_id']] = true;
    return $out;
}

/**
 * Ringkasan publish per rombel untuk satu scope (semester x period x
 * tahun ajaran), dipersempit ke jenjang kepsek bila relevan. Dipakai di
 * Level 1 (Daftar Kelas) Publish Rapor — n_students di sini dihitung dari
 * siswa yang PUNYA rapor (>=1 baris final_grades), bukan dari kelengkapan
 * approval (itu tanggung jawab publish_class_summary()).
 */
function rapor_class_publish_summary(array $user, string $semester, string $period, int $yearId): array
{
    $params = ['sem' => $semester, 'p' => $period, 'y' => $yearId];
    $jenjangFilter = '';
    if (($user['role'] ?? '') === 'kepsek' && !empty($user['jenjang'])) {
        $jenjangFilter = " AND r.jenjang = :j";
        $params['j'] = $user['jenjang'];
    }
    $sql = "SELECT fg.rombel_id, r.jenjang, r.tingkat, r.nama AS rombel_nama,
                   COUNT(DISTINCT fg.student_id) AS n_students,
                   COUNT(DISTINCT CASE WHEN rp.status = 'published' THEN fg.student_id END) AS n_published
            FROM final_grades fg
            JOIN rombel r ON r.id = fg.rombel_id
            LEFT JOIN rapor_publications rp
                   ON rp.student_id = fg.student_id AND rp.rombel_id = fg.rombel_id
                  AND rp.academic_year_id = r.academic_year_id
                  AND rp.semester = fg.semester AND rp.period_kind = fg.period_kind
            WHERE fg.semester = :sem AND fg.period_kind = :p AND r.academic_year_id = :y"
            . $jenjangFilter
            . " GROUP BY fg.rombel_id, r.jenjang, r.tingkat, r.nama";
    $st = db()->prepare($sql);
    $st->execute($params);

    $out = [];
    foreach ($st->fetchAll() as $row) {
        $out[(int)$row['rombel_id']] = [
            'rombel_id'    => (int)$row['rombel_id'],
            'label'        => $row['jenjang'] . ' ' . $row['tingkat'] . ' · ' . $row['rombel_nama'],
            'n_students'   => (int)$row['n_students'],
            'n_published'  => (int)$row['n_published'],
            'n_draft'      => (int)$row['n_students'] - (int)$row['n_published'],
        ];
    }
    return $out;
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
function character_evals_for_student(int $rombelId, int $studentId, string $sem, string $period): array
{
    $yearId = active_scope()['year_id'];
    $st = db()->prepare(
        "SELECT ce.*, ca.nama AS aspek_nama, ca.kategori
         FROM character_evaluations ce
         JOIN character_aspects ca ON ca.id = ce.aspect_id AND ca.academic_year_id = :y
         WHERE ce.rombel_id=:r AND ce.student_id=:st AND ce.semester=:sem AND ce.period_kind=:p
         ORDER BY FIELD(ca.kategori,'Spiritual and morality','Discipline','Manner','Obedience','Focus and Confidence','spiritual','sosial'), ca.nama"
    );
    $st->execute(['y'=>$yearId,'r'=>$rombelId,'st'=>$studentId,'sem'=>$sem,'p'=>$period]);
    return $st->fetchAll();
}

/** Subjects ordered by category for a rombel + semester (used in rapor body).
 *
 * If a student id is provided, elective-derived shadow subjects are filtered
 * so only the options assigned to that student for the semester appear.
 */
function subjects_grouped_for_rombel(int $rombelId, string $semester, ?int $studentId = null): array
{
    $yearId = active_scope()['year_id'];
    $st = db()->prepare(
        "SELECT DISTINCT s.id, s.kode, s.nama, s.category_id, sc.nama AS kategori_nama,
                         s.elective_class_id
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

    $assignedElectiveClassIds = [];
    if ($studentId !== null) {
        $st2 = db()->prepare(
            "SELECT elective_class_id FROM elective_assignments
             WHERE student_id = :st AND semester = :sem"
        );
        $st2->execute(['st' => $studentId, 'sem' => $semester]);
        foreach ($st2->fetchAll() as $row) {
            $assignedElectiveClassIds[] = (int)$row['elective_class_id'];
        }
    }

    $out = [];
    foreach ($rows as $r) {
        $electiveClassId = $r['elective_class_id'] !== null ? (int)$r['elective_class_id'] : null;
        if ($studentId !== null && $electiveClassId !== null && !in_array($electiveClassId, $assignedElectiveClassIds, true)) {
            continue;
        }
        unset($r['elective_class_id']);
        $cat = $r['kategori_nama'] ?? 'Lainnya';
        $out[$cat][] = $r;
    }
    return $out;
}
