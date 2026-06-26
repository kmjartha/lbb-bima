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

/** General-evaluation upsert. */
function general_eval_upsert(int $rombelId, int $studentId, string $sem, string $period, ?string $narasi): void
{
    $pdo = db();
    $exists = $pdo->prepare(
        "SELECT id FROM general_evaluations
         WHERE rombel_id=:r AND student_id=:st AND semester=:sem AND period_kind=:p"
    );
    $exists->execute(['r'=>$rombelId,'st'=>$studentId,'sem'=>$sem,'p'=>$period]);
    $id = (int)($exists->fetchColumn() ?: 0);
    if ($id) {
        $pdo->prepare("UPDATE general_evaluations SET narasi=:n WHERE id=:i")
            ->execute(['n'=>$narasi,'i'=>$id]);
    } else {
        $pdo->prepare(
            "INSERT INTO general_evaluations (rombel_id, student_id, semester, period_kind, narasi)
             VALUES (:r,:st,:sem,:p,:n)"
        )->execute(['r'=>$rombelId,'st'=>$studentId,'sem'=>$sem,'p'=>$period,'n'=>$narasi]);
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

