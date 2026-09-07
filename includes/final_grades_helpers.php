<?php
/**
 * Stage 6 — Nilai Akhir PTS/PAS helpers.
 * Tabel: final_grades
 * (rombel_id, subject_id, student_id, semester, period_kind=PTS|PAS,
 * nilai_sikap, nilai_pengetahuan, nilai_keterampilan,
 * catatan_guru, status=draft|submitted|revised|approved|published,
 * submitted_by, reviewed_by, reviewed_at, image_path)
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/scope.php';
require_once __DIR__ . '/grading_helpers.php';

/**
 * Workflow status definitions.
 *
 * CATATAN MIGRASI: 'published' DIHAPUS dari sini sengaja. Sejak rapor
 * dipublish di level rapor (tabel rapor_publications, lihat
 * report_helpers.php), final_grades.status berhenti dipakai untuk
 * menentukan visibilitas ke ortu — 'approved' sudah jadi status akhir
 * yang wajar untuk sebuah nilai mapel. Kolom status di DB tetap boleh
 * memuat nilai 'published' secara teknis (kalau enum lama belum
 * disempitkan), tapi kode ini tidak pernah men-set atau membaca nilai
 * itu lagi. Lihat migrations/2026_09_rapor_level_publish.sql — semua
 * baris lama sudah dikonversi ke 'approved' di situ.
 */
function fg_statuses(): array
{
    return [
        'draft'     => ['label' => 'Draft',     'class' => 'badge'],
        'submitted' => ['label' => 'Diajukan',  'class' => 'badge-info'],
        'revised'   => ['label' => 'Revisi',    'class' => 'badge-warning'],
        'approved'  => ['label' => 'Disetujui', 'class' => 'badge-success'],
    ];
}

/** Fetch existing final_grades rows for (rombel, subject, semester, period) keyed by student_id. */
function final_grades_for(int $rombelId, int $subjectId, string $semester, string $period): array
{
    $st = db()->prepare(
        "SELECT * FROM final_grades
         WHERE rombel_id=:r AND subject_id=:s AND semester=:sem AND period_kind=:p"
    );
    $st->execute(['r' => $rombelId, 's' => $subjectId, 'sem' => $semester, 'p' => $period]);
    $out = [];
    foreach ($st->fetchAll() as $row) {
        $out[(int)$row['student_id']] = $row;
    }
    return $out;
}

/** Insert/update one final_grades row. Returns the row's id. */
function final_grade_upsert(array $data): int
{
    $pdo = db();
    $exists = $pdo->prepare(
        "SELECT id FROM final_grades
         WHERE rombel_id=:r AND subject_id=:s AND student_id=:st
           AND semester=:sem AND period_kind=:p"
    );
    $exists->execute([
        'r'=>$data['rombel_id'],'s'=>$data['subject_id'],'st'=>$data['student_id'],
        'sem'=>$data['semester'],'p'=>$data['period_kind'],
    ]);
    $id = (int)($exists->fetchColumn() ?: 0);

    if ($id) {
        $sql = "UPDATE final_grades SET
                  nilai_sikap=:si, nilai_pengetahuan=:pe, nilai_keterampilan=:ke,
                  catatan_guru=:c, status=:status, image_path=:img,
                  submitted_by = COALESCE(:submitted_by_val, submitted_by)
                WHERE id=:id";
        $p = $pdo->prepare($sql);
        $p->execute([
            'si'=>$data['nilai_sikap'], 'pe'=>$data['nilai_pengetahuan'], 'ke'=>$data['nilai_keterampilan'],
            'c'=>$data['catatan_guru'], 'status'=>$data['status'], 'img'=>$data['image_path'] ?? null,
            'submitted_by_val'=>$data['submitted_by'] ?? null,
            'id'=>$id,
        ]);
        return $id;
    }
    
    $sql = "INSERT INTO final_grades
              (rombel_id, subject_id, student_id, semester, period_kind,
               nilai_sikap, nilai_pengetahuan, nilai_keterampilan,
               catatan_guru, status, submitted_by, image_path)
            VALUES (:r,:s,:st,:sem,:p,:si,:pe,:ke,:c,:status,:submitted_by,:img)";
    $stm = $pdo->prepare($sql);
    $stm->execute([
        'r'=>$data['rombel_id'],'s'=>$data['subject_id'],'st'=>$data['student_id'],
        'sem'=>$data['semester'],'p'=>$data['period_kind'],
        'si'=>$data['nilai_sikap'], 'pe'=>$data['nilai_pengetahuan'], 'ke'=>$data['nilai_keterampilan'],
        'c'=>$data['catatan_guru'], 'status'=>$data['status'], 'submitted_by'=>$data['submitted_by'] ?? null,
        'img'=>$data['image_path'] ?? null,
    ]);
    return (int)$pdo->lastInsertId();
}

/** Set status (and reviewer info if approving/revising). */
function final_grade_set_status(int $id, string $status, ?int $reviewerId = null): void
{
    $pdo = db();
    $needsReview = in_array($status, ['approved','revised'], true);
    if ($needsReview) {
        $pdo->prepare("UPDATE final_grades SET status=:s, reviewed_by=:u, reviewed_at=NOW() WHERE id=:i")
            ->execute(['s'=>$status,'u'=>$reviewerId,'i'=>$id]);
    } else {
        $pdo->prepare("UPDATE final_grades SET status=:s WHERE id=:i")
            ->execute(['s'=>$status,'i'=>$id]);
    }
}

/** Aggregate counts per status for a (rombel, subject, semester, period). */
function final_grade_status_counts(int $rombelId, int $subjectId, string $semester, string $period): array
{
    $st = db()->prepare(
        "SELECT status, COUNT(*) AS n FROM final_grades
         WHERE rombel_id=:r AND subject_id=:s AND semester=:sem AND period_kind=:p
         GROUP BY status"
    );
    $st->execute(['r'=>$rombelId,'s'=>$subjectId,'sem'=>$semester,'p'=>$period]);
    $out = ['draft'=>0,'submitted'=>0,'revised'=>0,'approved'=>0];
    foreach ($st->fetchAll() as $row) $out[$row['status']] = (int)$row['n'];
    return $out;
}

/**
 * Review/publish queue for a Kepsek (filtered by jenjang) or Admin (all).
 * Includes rows still awaiting review, revision, or publication.
 * Returns rows with rombel/subject/student labels.
 */
function review_queue(array $user, string $semester, string $period, ?int $yearId = null): array
{
    if ($yearId === null) {
        $yearId = active_scope()['year_id'];
    }
    $sql =
       "SELECT fg.*,
               r.jenjang, r.tingkat, r.nama AS rombel_nama,
               sb.kode AS subj_kode, sb.nama AS subj_nama, e.kode AS elective_kode,
               st.nis, st.nisn, st.nama AS student_nama,
               u.nama AS submitted_by_name, u.niy AS submitted_by_niy
        FROM final_grades fg
        JOIN rombel   r  ON r.id  = fg.rombel_id
        JOIN subjects sb ON sb.id = fg.subject_id
        LEFT JOIN elective_classes ec ON ec.id = sb.elective_class_id
        LEFT JOIN electives e ON e.id = ec.elective_id
        JOIN students st ON st.id = fg.student_id
        LEFT JOIN users u ON u.id = fg.submitted_by
        WHERE fg.semester=:sem AND fg.period_kind=:p
          AND fg.status IN ('submitted','revised','approved')
          AND r.academic_year_id = :y";
    $params = ['sem'=>$semester,'p'=>$period,'y'=>$yearId];
    if (($user['role'] ?? '') === 'kepsek' && !empty($user['jenjang'])) {
        $sql .= " AND r.jenjang = :j";
        $params['j'] = $user['jenjang'];
    }
    $sql .= " ORDER BY CASE fg.status WHEN 'submitted' THEN 0 WHEN 'revised' THEN 1 WHEN 'approved' THEN 2 ELSE 3 END,
                      COALESCE(u.nama, 'zzz'), r.jenjang, r.tingkat, r.nama, sb.nama, st.nama";
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/**
 * Raw final_grades rows (ALL statuses, not just approved/published) for a
 * Semester × Periode — the source data for the Publish Rapor overview
 * (per-kelas summary drilling down to a per-siswa completeness list).
 * Kepsek is scoped to jenjang; pass $rombelId to narrow to one class.
 */
function publish_overview_rows(array $user, string $semester, string $period, ?int $yearId = null, ?int $rombelId = null): array
{
    if ($yearId === null) {
        $yearId = active_scope()['year_id'];
    }
    $sql =
       "SELECT fg.id, fg.rombel_id, fg.subject_id, fg.student_id, fg.status,
               r.jenjang, r.tingkat, r.nama AS rombel_nama,
               sb.kode AS subj_kode, sb.nama AS subj_nama, e.kode AS elective_kode,
               st.nis, st.nisn, st.nama AS student_nama
        FROM final_grades fg
        JOIN rombel   r  ON r.id  = fg.rombel_id
        JOIN subjects sb ON sb.id = fg.subject_id
        LEFT JOIN elective_classes ec ON ec.id = sb.elective_class_id
        LEFT JOIN electives e ON e.id = ec.elective_id
        JOIN students st ON st.id = fg.student_id
        WHERE fg.semester=:sem AND fg.period_kind=:p
          AND r.academic_year_id = :y";
    $params = ['sem'=>$semester,'p'=>$period,'y'=>$yearId];
    if (($user['role'] ?? '') === 'kepsek' && !empty($user['jenjang'])) {
        $sql .= " AND r.jenjang = :j";
        $params['j'] = $user['jenjang'];
    }
    if ($rombelId !== null) {
        $sql .= " AND fg.rombel_id = :rid";
        $params['rid'] = $rombelId;
    }
    $st = db()->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    $existing = [];
    foreach ($rows as $r) {
        $existing[$r['rombel_id'] . '-' . $r['subject_id'] . '-' . $r['student_id']] = true;
    }

    // Tambahan: mapel-siswa yang SEHARUSNYA ada (berdasarkan penugasan mapel
    // ke rombel + keanggotaan siswa, difilter elective) tapi belum PERNAH
    // disimpan sama sekali oleh guru (tidak ada baris final_grades). Baris ini
    // ditambahkan sebagai semu (id null, status 'draft') supaya "rincian
    // mapel" dan hitungan X/Y per siswa juga mencakup mapel yang belum
    // digarap guru, bukan cuma yang sudah sempat tersimpan sebagai draft.
    $sql2 =
       "SELECT rst.rombel_id, rst.subject_id, rm.student_id,
               r.jenjang, r.tingkat, r.nama AS rombel_nama,
               sb.kode AS subj_kode, sb.nama AS subj_nama, e.kode AS elective_kode,
               st.nis, st.nisn, st.nama AS student_nama
        FROM rombel_subject_teachers rst
        JOIN rombel   r  ON r.id = rst.rombel_id AND r.academic_year_id = :y
        JOIN subjects sb ON sb.id = rst.subject_id AND sb.deleted_at IS NULL AND sb.academic_year_id = :y2
        LEFT JOIN elective_classes ec ON ec.id = sb.elective_class_id
        LEFT JOIN electives e ON e.id = ec.elective_id
        JOIN rombel_members rm ON rm.rombel_id = rst.rombel_id
        JOIN students st ON st.id = rm.student_id AND st.deleted_at IS NULL
        LEFT JOIN elective_assignments ea ON ea.student_id = rm.student_id
               AND ea.elective_class_id = sb.elective_class_id AND ea.semester = :sem
        WHERE (rst.semester IS NULL OR rst.semester = :sem2)
          AND (sb.elective_class_id IS NULL OR ea.student_id IS NOT NULL)";
    $params2 = ['y' => $yearId, 'y2' => $yearId, 'sem' => $semester, 'sem2' => $semester];
    if (($user['role'] ?? '') === 'kepsek' && !empty($user['jenjang'])) {
        $sql2 .= " AND r.jenjang = :j";
        $params2['j'] = $user['jenjang'];
    }
    if ($rombelId !== null) {
        $sql2 .= " AND rst.rombel_id = :rid";
        $params2['rid'] = $rombelId;
    }
    $sql2 .= " GROUP BY rst.rombel_id, rst.subject_id, rm.student_id";
    $st2 = db()->prepare($sql2);
    $st2->execute($params2);

    foreach ($st2->fetchAll() as $r) {
        $key = $r['rombel_id'] . '-' . $r['subject_id'] . '-' . $r['student_id'];
        if (isset($existing[$key])) continue; // sudah punya baris nyata di final_grades
        $rows[] = [
            'id'         => null,
            'rombel_id'  => $r['rombel_id'],
            'subject_id' => $r['subject_id'],
            'student_id' => $r['student_id'],
            'status'     => 'draft', // belum pernah disimpan guru == tampil sbg draft
            'jenjang'      => $r['jenjang'],
            'tingkat'      => $r['tingkat'],
            'rombel_nama'  => $r['rombel_nama'],
            'subj_kode'    => $r['subj_kode'],
            'subj_nama'    => $r['subj_nama'],
            'elective_kode'=> $r['elective_kode'],
            'nis'          => $r['nis'],
            'nisn'         => $r['nisn'],
            'student_nama' => $r['student_nama'],
        ];
    }

    usort($rows, fn($a, $b) => [$a['jenjang'], $a['tingkat'], $a['rombel_nama'], $a['student_nama'], $a['subj_nama']]
                     <=> [$b['jenjang'], $b['tingkat'], $b['rombel_nama'], $b['student_nama'], $b['subj_nama']]);

    return $rows;
}

/**
 * Aggregates publish_overview_rows() into one summary row per rombel.
 *
 * MIGRASI: ini sekarang murni informasi KELENGKAPAN VERIFIKASI mapel
 * (draft/submitted/revised vs approved) — tidak lagi punya arti "tampil
 * ke ortu". Visibilitas ortu ada di rapor_publications, lihat
 * rapor_class_publish_summary() di report_helpers.php.
 *  - n_pending  : draft/submitted/revised -> belum disetujui kepsek
 *  - n_approved : approved -> sudah disetujui (siap jadi bahan publish rapor)
 * n_total = n_pending + n_approved.
 *
 * "Kelengkapan siswa" (n_students_complete) dihitung dari approved saja,
 * menjawab "apakah rapor siswa ini sudah tidak menunggu guru/kepsek lagi"
 * — terlepas dari apakah rapornya sudah dipublish atau belum.
 *
 * Sorted by jenjang/tingkat/nama.
 */
function publish_class_summary(array $rows): array
{
    $byRombel = [];
    $students = []; // rombel_id => [student_id => ['total'=>int,'complete'=>int]]
    foreach ($rows as $r) {
        $rid = (int)$r['rombel_id'];
        if (!isset($byRombel[$rid])) {
            $byRombel[$rid] = [
                'rombel_id' => $rid,
                'label'     => $r['jenjang'] . ' ' . $r['tingkat'] . ' · ' . $r['rombel_nama'],
                'n_total' => 0, 'n_pending' => 0, 'n_approved' => 0,
            ];
            $students[$rid] = [];
        }
        $byRombel[$rid]['n_total']++;
        if ($r['status'] === 'approved') {
            $byRombel[$rid]['n_approved']++;
        } else {
            $byRombel[$rid]['n_pending']++;
        }

        $sid = (int)$r['student_id'];
        if (!isset($students[$rid][$sid])) $students[$rid][$sid] = ['total'=>0,'complete'=>0];
        $students[$rid][$sid]['total']++;
        if ($r['status'] === 'approved') $students[$rid][$sid]['complete']++;
    }
    foreach ($byRombel as $rid => &$c) {
        $c['n_students'] = count($students[$rid]);
        $c['n_students_complete'] = 0;
        foreach ($students[$rid] as $s) {
            if ($s['total'] > 0 && $s['complete'] === $s['total']) $c['n_students_complete']++;
        }
        $c['n_students_pending'] = $c['n_students'] - $c['n_students_complete'];
    }
    unset($c);
    $byRombel = array_values($byRombel);
    usort($byRombel, fn($a, $b) => strcmp($a['label'], $b['label']));
    return $byRombel;
}

/**
 * Aggregates publish_overview_rows() (already scoped to one rombel) into
 * one row per siswa — n_pending / n_approved (kelengkapan verifikasi
 * mapel, murni informasi), plus underlying per-mapel status list untuk
 * drill-down detail. Status publish rapor (tampil ke ortu atau tidak)
 * TIDAK ada di sini — itu digabungkan terpisah dari rapor_published_map()
 * di halaman publish_rapor.php, karena sumbernya beda tabel.
 */
function publish_student_summary(array $rows): array
{
    $out = [];
    foreach ($rows as $r) {
        $sid = (int)$r['student_id'];
        if (!isset($out[$sid])) {
            $out[$sid] = [
                'student_id' => $sid,
                'nama'       => $r['student_nama'],
                'nis'        => $r['nis'],
                'n_total' => 0, 'n_pending' => 0, 'n_approved' => 0,
                'subjects'   => [],
            ];
        }
        $out[$sid]['n_total']++;
        if ($r['status'] === 'approved') {
            $out[$sid]['n_approved']++;
        } else {
            $out[$sid]['n_pending']++;
        }
        $out[$sid]['subjects'][] = [
            'id'     => (int)$r['id'],
            'nama'   => ($r['subj_kode'] ? $r['subj_kode'] . ' · ' : '') . elective_subject_label($r['subj_nama'], $r['elective_kode'] ?? null),
            'status' => $r['status'],
        ];
    }
    $out = array_values($out);
    usort($out, fn($a, $b) => strcmp($a['nama'], $b['nama']));
    return $out;
}

/**
 * Counts broken down per Semester × Periode (Ganjil/Genap × PTS/PAS) for
 * one academic year, for the summary panel at the top of Publish Rapor:
 *  - 'approved'  : jumlah baris final_grades (mapel-siswa) berstatus
 *    approved -- murni informasi kelengkapan verifikasi, TIDAK berarti
 *    "siap publish" lagi (publish rapor boleh partial).
 *  - 'published' : jumlah SISWA (bukan baris mapel) yang rapornya sudah
 *    published di rapor_publications untuk kombinasi ini -- ini yang
 *    menentukan visibilitas ke ortu.
 */
function publish_summary_counts(array $user, int $yearId): array
{
    $sql =
       "SELECT fg.semester, fg.period_kind, SUM(fg.status='approved') AS n_approved
        FROM final_grades fg
        JOIN rombel r ON r.id = fg.rombel_id
        WHERE r.academic_year_id = :y";
    $params = ['y' => $yearId];
    if (($user['role'] ?? '') === 'kepsek' && !empty($user['jenjang'])) {
        $sql .= " AND r.jenjang = :j";
        $params['j'] = $user['jenjang'];
    }
    $sql .= " GROUP BY fg.semester, fg.period_kind";
    $st = db()->prepare($sql);
    $st->execute($params);

    $out = [
        'ganjil' => ['PTS' => ['approved' => 0, 'published' => 0], 'PAS' => ['approved' => 0, 'published' => 0]],
        'genap'  => ['PTS' => ['approved' => 0, 'published' => 0], 'PAS' => ['approved' => 0, 'published' => 0]],
    ];
    foreach ($st->fetchAll() as $row) {
        $sem = $row['semester'];
        $pk  = $row['period_kind'];
        if (!isset($out[$sem][$pk])) continue;
        $out[$sem][$pk]['approved'] = (int)$row['n_approved'];
    }

    $sql2 = "SELECT rp.semester, rp.period_kind, COUNT(DISTINCT rp.student_id) AS n_published
             FROM rapor_publications rp
             JOIN rombel r ON r.id = rp.rombel_id
             WHERE rp.academic_year_id = :y AND rp.status = 'published'";
    $params2 = ['y' => $yearId];
    if (($user['role'] ?? '') === 'kepsek' && !empty($user['jenjang'])) {
        $sql2 .= " AND r.jenjang = :j";
        $params2['j'] = $user['jenjang'];
    }
    $sql2 .= " GROUP BY rp.semester, rp.period_kind";
    $st2 = db()->prepare($sql2);
    $st2->execute($params2);
    foreach ($st2->fetchAll() as $row) {
        $sem = $row['semester'];
        $pk  = $row['period_kind'];
        if (!isset($out[$sem][$pk])) continue;
        $out[$sem][$pk]['published'] = (int)$row['n_published'];
    }
    return $out;
}