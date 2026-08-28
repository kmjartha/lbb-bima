<?php
/**
 * Review Pengisian Nilai Harian — helpers.
 *
 * Goal: let Kepsek (and Admin/Administrator) check, per-guru, whether daily
 * grades (Penilaian Harian / grades_daily) are being filled diligently for
 * every mapel that guru is assigned to teach. Flow:
 *
 *   1) reviewable_teachers()            -> pick a guru (with at-a-glance status)
 *   2) teacher_assignments_for_review() -> pick a mapel (rombel+subject) taught
 *      by that guru, each with its own at-a-glance status
 *   3) teacher_period_recap()           -> weekly/monthly fill-rate breakdown
 *      for that one rombel+subject+guru combination
 *
 * Scoping mirrors accessible_rombel()/teaching_subjects_for_rombel(): kepsek
 * only sees guru teaching within their own jenjang (or teach across jenjang
 * if the kepsek personally has rombel_subject_teachers rows there); admin
 * and administrator see everyone.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/scope.php';
require_once __DIR__ . '/grading_helpers.php';

/** Recency thresholds (in days since last fill) used to classify status. */
const TGR_FRESH_DAYS = 7;
const TGR_STALE_DAYS = 14;

/**
 * Classify a "last filled" date into a status key + label + badge class.
 * $lastDate: 'Y-m-d' or null. $hasAssignment: false => guru has no mapel in scope.
 */
function tgr_status_from_last_date(?string $lastDate): array
{
    if ($lastDate === null) {
        return ['key' => 'never', 'label' => 'Belum Pernah Mengisi', 'class' => 'badge-danger', 'dot' => 'danger'];
    }
    $days = (int)floor((strtotime(date('Y-m-d')) - strtotime($lastDate)) / 86400);
    if ($days <= TGR_FRESH_DAYS) {
        return ['key' => 'active', 'label' => 'Aktif Mengisi', 'class' => 'badge-success', 'dot' => 'filled'];
    }
    if ($days <= TGR_STALE_DAYS) {
        return ['key' => 'warning', 'label' => 'Perlu Diperhatikan', 'class' => 'badge-warning', 'dot' => 'empty'];
    }
    return ['key' => 'stale', 'label' => 'Tidak Aktif', 'class' => 'badge-danger', 'dot' => 'absent'];
}

/**
 * List of guru the current user (kepsek/admin/administrator) may review,
 * scoped to the active academic year + semester, each annotated with:
 *   - jumlah_mapel   : distinct (rombel,subject) combos taught in scope
 *   - last_fill_date : MAX(tanggal) across all their grades_daily entries in scope
 *   - status         : tgr_status_from_last_date() result
 * Guru with zero assignments in the active scope are excluded (nothing to review).
 */
function reviewable_teachers(array $user): array
{
    $sc  = active_scope();
    $pdo = db();
    $role = $user['role'] ?? '';

    $jenFilter = '';
    // Native (non-emulated) prepares can't bind the same named placeholder
    // twice in one query, so :sem is duplicated as :sem1/:sem2 below.
    $params = ['y' => $sc['year_id'], 'sem1' => $sc['semester'], 'sem2' => $sc['semester']];
    if ($role === 'kepsek' && !empty($user['jenjang'])) {
        $jenFilter = ' AND r.jenjang = :jen';
        $params['jen'] = $user['jenjang'];
    } elseif (!in_array($role, ['administrator', 'admin'], true)) {
        return []; // guru themselves don't use this review feature
    }

    $sql = "
        SELECT t.id AS teacher_id, u.id AS user_id, u.nama, u.niy,
               COUNT(DISTINCT CONCAT(rst.rombel_id,'-',rst.subject_id)) AS jumlah_mapel,
               MAX(gd.tanggal) AS last_fill_date
        FROM teachers t
        JOIN users u ON u.id = t.user_id AND u.deleted_at IS NULL AND u.is_active = 1
        JOIN rombel_subject_teachers rst ON rst.teacher_id = t.id
             AND (rst.semester IS NULL OR rst.semester = :sem1)
        JOIN rombel r ON r.id = rst.rombel_id AND r.academic_year_id = :y AND r.deleted_at IS NULL
        JOIN subjects sub ON sub.id = rst.subject_id AND sub.deleted_at IS NULL
        LEFT JOIN grades_daily gd ON gd.rombel_id = rst.rombel_id AND gd.subject_id = rst.subject_id
             AND gd.recorded_by = u.id AND gd.semester = :sem2
        WHERE u.role = 'guru' {$jenFilter}
        GROUP BY t.id, u.id, u.nama, u.niy
        ORDER BY u.nama
    ";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    foreach ($rows as &$r) {
        $r['jumlah_mapel']   = (int)$r['jumlah_mapel'];
        $r['last_fill_date'] = $r['last_fill_date'] ?: null;
        $r['status']         = tgr_status_from_last_date($r['last_fill_date']);
    }
    unset($r);
    return $rows;
}

/** Fetch one reviewable teacher by teacher_id, or null if out of scope. */
function reviewable_teacher_by_id(array $user, int $teacherId): ?array
{
    foreach (reviewable_teachers($user) as $t) {
        if ((int)$t['teacher_id'] === $teacherId) return $t;
    }
    return null;
}

/**
 * Mapel (rombel+subject) assignments taught by one guru in the active scope,
 * each annotated with topics_count, last_fill_date and status. Restricted to
 * the same jenjang scope as reviewable_teachers() for kepsek.
 */
function teacher_assignments_for_review(array $user, int $teacherId): array
{
    $sc  = active_scope();
    $pdo = db();
    $role = $user['role'] ?? '';

    $jenFilter = '';
    // :sem is needed twice in this query — native prepares reject reusing one
    // named placeholder twice, so we bind :sem1/:sem2 with the same value.
    $params = ['y' => $sc['year_id'], 'sem1' => $sc['semester'], 'sem2' => $sc['semester'], 't' => $teacherId];
    if ($role === 'kepsek' && !empty($user['jenjang'])) {
        $jenFilter = ' AND r.jenjang = :jen';
        $params['jen'] = $user['jenjang'];
    } elseif (!in_array($role, ['administrator', 'admin'], true)) {
        return [];
    }

    $sql = "
        SELECT rst.rombel_id, rst.subject_id, u.id AS teacher_user_id,
               r.jenjang, r.tingkat, r.nama AS rombel_nama,
               s.kode AS subj_kode, s.nama AS subj_nama, e.kode AS elective_kode,
               MAX(gd.tanggal) AS last_fill_date,
               COUNT(DISTINCT gd.tanggal) AS hari_terisi
        FROM rombel_subject_teachers rst
        JOIN teachers t ON t.id = rst.teacher_id
        JOIN users u ON u.id = t.user_id
        JOIN rombel r ON r.id = rst.rombel_id AND r.academic_year_id = :y AND r.deleted_at IS NULL
        JOIN subjects s ON s.id = rst.subject_id AND s.deleted_at IS NULL
        LEFT JOIN elective_classes ec ON ec.id = s.elective_class_id
        LEFT JOIN electives e ON e.id = ec.elective_id
        LEFT JOIN grades_daily gd ON gd.rombel_id = rst.rombel_id AND gd.subject_id = rst.subject_id
             AND gd.recorded_by = u.id AND gd.semester = :sem2
        WHERE rst.teacher_id = :t
          AND (rst.semester IS NULL OR rst.semester = :sem1)
          {$jenFilter}
        GROUP BY rst.rombel_id, rst.subject_id, u.id, r.jenjang, r.tingkat, r.nama,
                 s.kode, s.nama, e.kode
        ORDER BY FIELD(r.jenjang,'TK','SD','SMP','SMA'), r.tingkat, r.nama, s.nama
    ";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    foreach ($rows as &$r) {
        $r['rombel_id']       = (int)$r['rombel_id'];
        $r['subject_id']      = (int)$r['subject_id'];
        $r['teacher_user_id'] = (int)$r['teacher_user_id'];
        $r['hari_terisi']     = (int)$r['hari_terisi'];
        $r['last_fill_date']  = $r['last_fill_date'] ?: null;
        $r['status']          = tgr_status_from_last_date($r['last_fill_date']);
        $r['topics_count']    = count(topics_for($r['rombel_id'], $r['subject_id'], $sc['semester']));
    }
    unset($r);
    return $rows;
}

/** Find one assignment row (for the header/summary of the detail view). */
function teacher_assignment_find(array $rows, int $rombelId, int $subjectId): ?array
{
    foreach ($rows as $r) {
        if ($r['rombel_id'] === $rombelId && $r['subject_id'] === $subjectId) return $r;
    }
    return null;
}

/**
 * Weekly or monthly fill-rate recap for one (rombel, subject, guru) combo
 * across the active semester's date window.
 *
 * Returns ['groupBy'=>'week'|'month', 'total_students'=>int, 'periods'=>[...]]
 * where each period has:
 *   label, start, end, is_future, is_current, hari_terisi (int),
 *   entri_count (int), siswa_disentuh (int), tanggal_list (array of 'Y-m-d'),
 *   status ('kosong'|'sebagian'|'terisi'|'akan_datang')
 */
function teacher_period_recap(
    int $rombelId,
    int $subjectId,
    int $teacherUserId,
    string $semester,
    int $yearId,
    string $groupBy = 'week'
): array {
    $groupBy = in_array($groupBy, ['week', 'month'], true) ? $groupBy : 'week';
    [$semStart, $semEnd] = semester_date_window($yearId, $semester);
    $today = date('Y-m-d');

    // Per-date aggregation of this guru's entries for this rombel+subject+semester.
    $st = db()->prepare(
        "SELECT tanggal, COUNT(*) AS entri_count, COUNT(DISTINCT student_id) AS siswa_count
         FROM grades_daily
         WHERE rombel_id = :r AND subject_id = :s AND recorded_by = :u AND semester = :sem
         GROUP BY tanggal
         ORDER BY tanggal"
    );
    $st->execute(['r' => $rombelId, 's' => $subjectId, 'u' => $teacherUserId, 'sem' => $semester]);
    $byDate = [];
    foreach ($st->fetchAll() as $row) {
        $byDate[$row['tanggal']] = ['entri' => (int)$row['entri_count'], 'siswa' => (int)$row['siswa_count']];
    }

    $totalStudents = count(rombel_members_for_subject($rombelId, $subjectId, $semester));

    // Build period buckets across the whole semester window.
    $periods = [];
    if ($groupBy === 'week') {
        // Align to Monday-start weeks covering [semStart, semEnd].
        $cursor = new DateTime($semStart);
        $cursor->modify('monday this week');
        $end = new DateTime($semEnd);
        while ($cursor <= $end) {
            $wStart = $cursor->format('Y-m-d');
            $wEndDt = (clone $cursor)->modify('+6 days');
            $wEnd   = $wEndDt->format('Y-m-d');
            $periods[] = [
                'label' => 'Minggu ' . $cursor->format('d M') . ' – ' . $wEndDt->format('d M Y'),
                'start' => $wStart,
                'end'   => $wEnd,
            ];
            $cursor->modify('+7 days');
        }
    } else {
        $cursor = new DateTime(date('Y-m-01', strtotime($semStart)));
        $end = new DateTime($semEnd);
        while ($cursor <= $end) {
            $mEndDt = (clone $cursor)->modify('last day of this month');
            $periods[] = [
                'label' => $cursor->format('F Y'),
                'start' => $cursor->format('Y-m-d'),
                'end'   => $mEndDt->format('Y-m-d'),
            ];
            $cursor->modify('first day of next month');
        }
    }

    $out = [];
    foreach ($periods as $p) {
        $hariTerisi = 0; $entri = 0; $siswaTouched = 0; $tanggalList = [];
        foreach ($byDate as $tgl => $agg) {
            if ($tgl >= $p['start'] && $tgl <= $p['end']) {
                $hariTerisi++;
                $entri += $agg['entri'];
                $siswaTouched = max($siswaTouched, $agg['siswa']);
                $tanggalList[] = $tgl;
            }
        }
        $isFuture  = $p['start'] > $today;
        $isCurrent = $today >= $p['start'] && $today <= $p['end'];

        if ($isFuture) {
            $status = 'akan_datang';
        } elseif ($hariTerisi === 0) {
            $status = 'kosong';
        } elseif ($totalStudents > 0 && $siswaTouched < $totalStudents) {
            $status = 'sebagian';
        } else {
            $status = 'terisi';
        }

        $out[] = [
            'label'         => $p['label'],
            'start'         => $p['start'],
            'end'           => $p['end'],
            'is_future'     => $isFuture,
            'is_current'    => $isCurrent,
            'hari_terisi'   => $hariTerisi,
            'entri_count'   => $entri,
            'siswa_disentuh'=> $siswaTouched,
            'tanggal_list'  => $tanggalList,
            'status'        => $status,
        ];
    }

    return ['groupBy' => $groupBy, 'total_students' => $totalStudents, 'periods' => $out];
}
