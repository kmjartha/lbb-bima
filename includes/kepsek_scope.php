<?php
/**
 * Kepsek rombel-scope helpers.
 *
 * Kepsek has TWO distinct access concepts that must never be conflated:
 *
 *   1. Structural / managerial scope ("jenjang scope") — all rombel that
 *      belong to the Kepsek's assigned jenjang. This is the oversight view
 *      Kepsek gets by virtue of being the school-level administrator for
 *      that jenjang.
 *
 *   2. Teaching scope — only the rombel where the Kepsek is personally
 *      assigned as a subject teacher via rombel_subject_teachers, exactly
 *      like a guru. This supports cross-jenjang teaching (e.g. a Kepsek SMP
 *      who also personally teaches a subject in an SMA rombel).
 *
 * Different features need different scopes (see docs/rombel-scope ticket):
 *   - Guru Pengampu, Absensi Harian, Rekap Absensi  -> jenjang scope only
 *   - Subject Penilaian, Penilaian Harian           -> teaching scope only
 *   - Rekap Nilai Harian, Nilai Akhir PTS/PAS       -> jenjang UNION teaching
 *
 * Each page must pick the correct helper below AND validate the selected
 * rombel_id (and, where relevant, subject_id) against that exact scope
 * server-side — filtering the dropdown alone is not sufficient.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/scope.php';
require_once __DIR__ . '/grading_helpers.php';

/**
 * All rombel in the Kepsek's own jenjang for the active academic year.
 * (If the Kepsek somehow has no jenjang set, falls back to all jenjang —
 * mirrors the previous behaviour of accessible_attendance_rombel().)
 *
 * Used by: Guru Pengampu, Absensi Harian, Rekap Absensi.
 */
function kepsek_jenjang_rombels(array $user): array
{
    $sc  = active_scope();
    $pdo = db();
    $jen = $user['jenjang'] ?? null;

    $sql = "SELECT r.*, u.nama AS wali_nama
            FROM rombel r LEFT JOIN users u ON u.id = r.wali_id
            WHERE r.academic_year_id = :y AND r.deleted_at IS NULL";
    $params = ['y' => $sc['year_id']];
    if ($jen) {
        $sql .= " AND r.jenjang = :j";
        $params['j'] = $jen;
    }
    $sql .= " ORDER BY FIELD(r.jenjang,'SD','SMP','SMA'), r.tingkat, r.nama";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/**
 * Only the rombel where the Kepsek is personally assigned as a teacher via
 * rombel_subject_teachers for the active academic year + semester. Not
 * restricted to the Kepsek's own jenjang — cross-jenjang teaching is
 * intentionally supported here.
 *
 * Used by: Subject Penilaian, Penilaian Harian.
 */
function kepsek_teaching_rombels(array $user): array
{
    $sc  = active_scope();
    $pdo = db();

    $stt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = :u");
    $stt->execute(['u' => $user['id']]);
    $tid = (int)($stt->fetchColumn() ?: 0);
    if (!$tid) return [];

    $st = $pdo->prepare(
        "SELECT DISTINCT r.*, u.nama AS wali_nama
         FROM rombel r
         LEFT JOIN users u ON u.id = r.wali_id
         JOIN rombel_subject_teachers rst ON rst.rombel_id = r.id
         WHERE r.academic_year_id = :y AND r.deleted_at IS NULL
           AND rst.teacher_id = :t
           AND (rst.semester IS NULL OR rst.semester = :sem)
         ORDER BY FIELD(r.jenjang,'SD','SMP','SMA'), r.tingkat, r.nama"
    );
    $st->execute(['y' => $sc['year_id'], 't' => $tid, 'sem' => $sc['semester']]);
    return $st->fetchAll();
}

/**
 * UNION of kepsek_jenjang_rombels() and kepsek_teaching_rombels() — never an
 * intersection. Lets a Kepsek see everything under their jenjang oversight
 * PLUS any rombel elsewhere that they personally teach.
 *
 * Used by: Rekap Nilai Harian, Nilai Akhir PTS/PAS.
 */
function kepsek_combined_rombels(array $user): array
{
    $byId = [];
    foreach (kepsek_jenjang_rombels($user) as $r)  $byId[(int)$r['id']] = $r;
    foreach (kepsek_teaching_rombels($user) as $r) $byId[(int)$r['id']] = $r;

    $order = ['SD' => 0, 'SMP' => 1, 'SMA' => 2, 'TK' => -1];
    uasort($byId, function (array $a, array $b) use ($order): int {
        $ja = $order[$a['jenjang']] ?? 99;
        $jb = $order[$b['jenjang']] ?? 99;
        if ($ja !== $jb) return $ja <=> $jb;
        if ((int)$a['tingkat'] !== (int)$b['tingkat']) return (int)$a['tingkat'] <=> (int)$b['tingkat'];
        return strcmp((string)$a['nama'], (string)$b['nama']);
    });

    return array_values($byId);
}

/**
 * True if $rombelId is NOT in the Kepsek's own jenjang, i.e. it is only
 * present in a combined-scope list because the Kepsek personally teaches
 * there. Used to decide subject-visibility on combined-scope pages.
 */
function kepsek_rombel_is_outside_jenjang(array $user, array $rombel): bool
{
    $jen = $user['jenjang'] ?? null;
    if (!$jen) return false; // no jenjang set -> nothing is "outside" it
    return ($rombel['jenjang'] ?? null) !== $jen;
}

/**
 * Subject list for a rombel on "combined scope" pages (Rekap Nilai Harian,
 * Nilai Akhir PTS/PAS):
 *   - Kepsek, rombel inside their own jenjang -> full oversight subject list
 *     (accessible_subjects_for_rombel), same as the existing behaviour.
 *   - Kepsek, rombel outside their jenjang (present only via personal
 *     teaching assignment) -> only subjects the Kepsek personally teaches
 *     in that rombel.
 *   - Any other role -> identical to accessible_subjects_for_rombel().
 */
function combined_scope_subjects_for_rombel(array $user, array $rombel): array
{
    $rombelId = (int)$rombel['id'];
    if (($user['role'] ?? '') === 'kepsek' && kepsek_rombel_is_outside_jenjang($user, $rombel)) {
        return teaching_subjects_for_rombel($user, $rombelId);
    }
    return accessible_subjects_for_rombel($user, $rombelId);
}

/**
 * Generic guard: throws 403 unless $rombelId is present in the given
 * (already correctly-scoped) rombel list. Use this instead of
 * assert_can_access_rombel() whenever the page's rombel dropdown is built
 * from something other than accessible_rombel() (e.g. the Kepsek-specific
 * scopes above) — assert_can_access_rombel() always re-derives its own
 * generic scope internally and would silently re-apply the wrong rule.
 */
function assert_rombel_in_list(array $rombels, int $rombelId): array
{
    foreach ($rombels as $r) {
        if ((int)$r['id'] === $rombelId) return $r;
    }
    http_response_code(403);
    die('403 — Anda tidak memiliki akses ke rombel ini.');
}
