<?php
/**
 * Stage 7 — Catatan Wali, Character Evaluation, General Evaluation, Ekskul Grades.
 * Shared helpers for scope, scale labels, and wali-only rombel access.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/scope.php';
require_once __DIR__ . '/attendance_helpers.php';

/** Character-evaluation 4-point scale (NI/SI/WI/PR). */
function character_scales(): array
{
    return [
        'NI' => ['label' => 'Need Improvement',    'class' => 'badge-danger'],
        'SI' => ['label' => 'Showing Improvement', 'class' => 'badge-warning'],
        'WI' => ['label' => 'Well Improvement',    'class' => 'badge-info'],
        'PR' => ['label' => 'Proficient',          'class' => 'badge-success'],
    ];
}

/**
 * Rombel yang bisa diakses untuk halaman wali (catatan/karakter/general).
 * - administrator/admin: semua rombel di tahun aktif
 * - kepsek: rombel di jenjang miliknya
 * - guru: hanya rombel yang ia jadi wali kelas-nya.
 */
function accessible_wali_rombel(array $user): array
{
    $sc  = active_scope();
    $pdo = db();
    $role = $user['role'] ?? '';

    if (in_array($role, ['administrator','admin'], true)) {
        $st = $pdo->prepare(
            "SELECT r.*, u.nama AS wali_nama
             FROM rombel r LEFT JOIN users u ON u.id = r.wali_id
             WHERE r.academic_year_id = :y AND r.deleted_at IS NULL
             ORDER BY FIELD(r.jenjang,'SD','SMP','SMA'), r.tingkat, r.nama"
        );
        $st->execute(['y' => $sc['year_id']]);
        return $st->fetchAll();
    }
    if ($role === 'kepsek') {
        $jen = $user['jenjang'] ?? null;
        $sql = "SELECT r.*, u.nama AS wali_nama
                FROM rombel r LEFT JOIN users u ON u.id = r.wali_id
                WHERE r.academic_year_id = :y AND r.deleted_at IS NULL";
        $params = ['y' => $sc['year_id']];
        if ($jen) { $sql .= " AND r.jenjang = :j"; $params['j'] = $jen; }
        $sql .= " ORDER BY FIELD(r.jenjang,'SD','SMP','SMA'), r.tingkat, r.nama";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }
    if ($role === 'guru') {
        $st = $pdo->prepare(
            "SELECT r.*, u.nama AS wali_nama
             FROM rombel r LEFT JOIN users u ON u.id = r.wali_id
             WHERE r.academic_year_id = :y AND r.deleted_at IS NULL
               AND r.wali_id = :uid
             ORDER BY FIELD(r.jenjang,'SD','SMP','SMA'), r.tingkat, r.nama"
        );
        $st->execute(['y' => $sc['year_id'], 'uid' => $user['id']]);
        return $st->fetchAll();
    }
    return [];
}

/** Throws 403 if user can't access this rombel via wali rules. */
function assert_wali_rombel(array $user, int $rombelId): array
{
    foreach (accessible_wali_rombel($user) as $r) {
        if ((int)$r['id'] === $rombelId) return $r;
    }
    http_response_code(403);
    die('403 — Hanya wali kelas (atau admin/kepsek) yang dapat mengakses rombel ini.');
}

/** Read-only flag for wali-stage pages (catatan/karakter/general).
 *  True when the user lacks edit permission OR the period is locked. */
function wali_readonly(array $user, string $feature = 'wali_notes'): bool
{
    if (!can_edit($feature, $user)) return true;
    return scope_is_locked();
}

/**
 * General-evaluation upsert.
 * Menyimpan narasi sekaligus status alur-review: setiap kali wali menyimpan
 * narasi yang tidak kosong, statusnya di-set/reset ke 'submitted' — artinya
 * menunggu (atau menunggu ulang, kalau sebelumnya sudah disetujui/direvisi)
 * verifikasi Kepsek. Narasi kosong dianggap 'draft' dan tidak masuk antrean
 * Verifikasi > Deskripsi Umum.
 */
function general_eval_upsert(int $rombelId, int $studentId, string $sem, string $period, ?string $narasi, ?int $submittedBy = null): void
{
    $pdo = db();
    $status = ($narasi !== null && trim($narasi) !== '') ? 'submitted' : 'draft';
    $exists = $pdo->prepare(
        "SELECT id FROM general_evaluations
         WHERE rombel_id=:r AND student_id=:st AND semester=:sem AND period_kind=:p"
    );
    $exists->execute(['r'=>$rombelId,'st'=>$studentId,'sem'=>$sem,'p'=>$period]);
    $id = (int)($exists->fetchColumn() ?: 0);
    if ($id) {
        $pdo->prepare(
            "UPDATE general_evaluations
                SET narasi=:n, status=:st,
                    submitted_by = COALESCE(:sb, submitted_by)
              WHERE id=:i"
        )->execute(['n'=>$narasi,'st'=>$status,'sb'=>$submittedBy,'i'=>$id]);
    } else {
        $pdo->prepare(
            "INSERT INTO general_evaluations (rombel_id, student_id, semester, period_kind, narasi, status, submitted_by)
             VALUES (:r,:st,:sem,:p,:n,:stt,:sb)"
        )->execute(['r'=>$rombelId,'st'=>$studentId,'sem'=>$sem,'p'=>$period,'n'=>$narasi,'stt'=>$status,'sb'=>$submittedBy]);
    }
}

/** Map general_evaluations rows -> [student_id => narasi]. */
function general_evals_for(int $rombelId, string $sem, string $period): array
{
    $st = db()->prepare(
        "SELECT student_id, narasi FROM general_evaluations
         WHERE rombel_id=:r AND semester=:sem AND period_kind=:p"
    );
    $st->execute(['r'=>$rombelId,'sem'=>$sem,'p'=>$period]);
    $out = [];
    foreach ($st->fetchAll() as $row) $out[(int)$row['student_id']] = $row['narasi'];
    return $out;
}

/** Map general_evaluations rows -> [student_id => status]. Untuk badge status di halaman wali. */
function general_evals_status_for(int $rombelId, string $sem, string $period): array
{
    $st = db()->prepare(
        "SELECT student_id, status FROM general_evaluations
         WHERE rombel_id=:r AND semester=:sem AND period_kind=:p"
    );
    $st->execute(['r'=>$rombelId,'sem'=>$sem,'p'=>$period]);
    $out = [];
    foreach ($st->fetchAll() as $row) $out[(int)$row['student_id']] = $row['status'];
    return $out;
}

/** Workflow status labels untuk General Description / Narrative (sama pola dengan fg_statuses()). */
function ge_statuses(): array
{
    return [
        'draft'     => ['label' => 'Draft',     'class' => 'badge'],
        'submitted' => ['label' => 'Diajukan',  'class' => 'badge-info'],
        'revised'   => ['label' => 'Revisi',    'class' => 'badge-warning'],
        'approved'  => ['label' => 'Disetujui', 'class' => 'badge-success'],
    ];
}

/** Set status (dan info reviewer bila approve/revise) untuk satu baris general_evaluations. */
function general_eval_set_status(int $id, string $status, ?int $reviewerId = null): void
{
    $pdo = db();
    $needsReview = in_array($status, ['approved', 'revised'], true);
    if ($needsReview) {
        $pdo->prepare("UPDATE general_evaluations SET status=:s, reviewed_by=:u, reviewed_at=NOW() WHERE id=:i")
            ->execute(['s'=>$status,'u'=>$reviewerId,'i'=>$id]);
    } else {
        $pdo->prepare("UPDATE general_evaluations SET status=:s WHERE id=:i")
            ->execute(['s'=>$status,'i'=>$id]);
    }
}

/**
 * Antrean review General Description / Narrative untuk Kepsek (jenjang-nya
 * saja) atau Administrator (semua jenjang). Sama pola dengan review_queue()
 * di final_grades_helpers.php, tapi sumbernya general_evaluations.
 */
function general_eval_review_queue(array $user, string $semester, string $period, ?int $yearId = null): array
{
    if ($yearId === null) {
        $yearId = active_scope()['year_id'];
    }
    $sql =
       "SELECT ge.*,
               r.jenjang, r.tingkat, r.nama AS rombel_nama,
               st.nis, st.nisn, st.nama AS student_nama,
               u.nama AS submitted_by_name, u.niy AS submitted_by_niy
        FROM general_evaluations ge
        JOIN rombel   r  ON r.id = ge.rombel_id
        JOIN students st ON st.id = ge.student_id
        LEFT JOIN users u ON u.id = ge.submitted_by
        WHERE ge.semester=:sem AND ge.period_kind=:p
          AND ge.status IN ('submitted','revised','approved')
          AND r.academic_year_id = :y";
    $params = ['sem'=>$semester,'p'=>$period,'y'=>$yearId];
    if (($user['role'] ?? '') === 'kepsek' && !empty($user['jenjang'])) {
        $sql .= " AND r.jenjang = :j";
        $params['j'] = $user['jenjang'];
    }
    $sql .= " ORDER BY CASE ge.status WHEN 'submitted' THEN 0 WHEN 'revised' THEN 1 WHEN 'approved' THEN 2 ELSE 3 END,
                      COALESCE(u.nama, 'zzz'), r.jenjang, r.tingkat, r.nama, st.nama";
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** All character aspects ordered by kategori, nama and optionally filtered by jenjang. */
function character_aspects_all(?string $jenjang = null): array
{
    $yearId = active_scope()['year_id'];
    if ($jenjang) {
        $st = db()->prepare(
            "SELECT * FROM character_aspects
             WHERE academic_year_id = :y AND jenjang = :j
             ORDER BY jenjang, FIELD(kategori,'Spiritual and morality','Discipline','Manner','Obedience','Focus and Confidence','spiritual','sosial'), nama"
        );
        $st->execute(['y' => $yearId, 'j' => $jenjang]);
    } else {
        $st = db()->prepare(
            "SELECT * FROM character_aspects
             WHERE academic_year_id = :y
             ORDER BY jenjang, FIELD(kategori,'Spiritual and morality','Discipline','Manner','Obedience','Focus and Confidence','spiritual','sosial'), nama"
        );
        $st->execute(['y' => $yearId]);
    }
    return $st->fetchAll();
}

/** Character-eval rows for (rombel, semester, period) keyed by [student_id][aspect_id]. */
function character_evals_for(int $rombelId, string $sem, string $period): array
{
    $st = db()->prepare(
        "SELECT * FROM character_evaluations
         WHERE rombel_id=:r AND semester=:sem AND period_kind=:p"
    );
    $st->execute(['r'=>$rombelId,'sem'=>$sem,'p'=>$period]);
    $out = [];
    foreach ($st->fetchAll() as $row) {
        $out[(int)$row['student_id']][(int)$row['aspect_id']] = $row;
    }
    return $out;
}

function character_eval_upsert(int $rombelId, int $studentId, int $aspectId, string $sem, string $period, string $scale, ?string $remark): void
{
    $pdo = db();
    $exists = $pdo->prepare(
        "SELECT id FROM character_evaluations
         WHERE rombel_id=:r AND student_id=:st AND aspect_id=:a AND semester=:sem AND period_kind=:p"
    );
    $exists->execute(['r'=>$rombelId,'st'=>$studentId,'a'=>$aspectId,'sem'=>$sem,'p'=>$period]);
    $id = (int)($exists->fetchColumn() ?: 0);
    if ($id) {
        $pdo->prepare("UPDATE character_evaluations SET scale=:s, remark=:rm WHERE id=:i")
            ->execute(['s'=>$scale,'rm'=>$remark,'i'=>$id]);
    } else {
        $pdo->prepare(
            "INSERT INTO character_evaluations
               (rombel_id, student_id, aspect_id, semester, period_kind, scale, remark)
             VALUES (:r,:st,:a,:sem,:p,:s,:rm)"
        )->execute(['r'=>$rombelId,'st'=>$studentId,'a'=>$aspectId,'sem'=>$sem,'p'=>$period,'s'=>$scale,'rm'=>$remark]);
    }
}

