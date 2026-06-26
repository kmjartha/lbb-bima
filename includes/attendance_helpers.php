<?php
/**
 * Helpers for Stage 4 — Absensi.
 * Centralizes rombel access scoping (admin/kepsek/guru) and date utilities.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/scope.php';

/** Status enum for attendance. */
function att_statuses(): array {
    return [
        'H' => ['label' => 'Hadir', 'class' => 'badge-success'],
        'I' => ['label' => 'Izin',  'class' => 'badge-warning'],
        'S' => ['label' => 'Sakit', 'class' => 'badge-info'],
        'A' => ['label' => 'Alpa',  'class' => 'badge-danger'],
    ];
}

/**
 * List rombel the current user can access for the active TA.
 * - administrator/admin/kepsek -> all (kepsek filtered by jenjang if set)
 * - guru -> rombel where they are wali OR a subject teacher (rombel_subject_teachers)
 */
function accessible_rombel(array $user): array
{
    $sc = active_scope();
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
        // Map users.id -> teachers.id
        $st = $pdo->prepare("SELECT id FROM teachers WHERE user_id=:u");
        $st->execute(['u' => $user['id']]);
        $tid = (int)($st->fetchColumn() ?: 0);

        $st = $pdo->prepare(
            "SELECT DISTINCT r.*, u.nama AS wali_nama
             FROM rombel r LEFT JOIN users u ON u.id = r.wali_id
             LEFT JOIN rombel_subject_teachers rst
                    ON rst.rombel_id = r.id
                   AND rst.teacher_id = :tid
                   AND (rst.semester IS NULL OR rst.semester = :sem)
             WHERE r.academic_year_id = :y AND r.deleted_at IS NULL
               AND (r.wali_id = :uid OR rst.id IS NOT NULL)
             ORDER BY FIELD(r.jenjang,'SD','SMP','SMA'), r.tingkat, r.nama"
        );
        $st->execute([
            'y'   => $sc['year_id'],
            'sem' => $sc['semester'],
            'uid' => $user['id'],
            'tid' => $tid,
        ]);
        return $st->fetchAll();
    }

    return [];
}

/** Throws 403 if the user cannot access this rombel. */
function assert_can_access_rombel(array $user, int $rombelId): array
{
    foreach (accessible_rombel($user) as $r) {
        if ((int)$r['id'] === $rombelId) return $r;
    }
    http_response_code(403);
    die('403 — Anda tidak memiliki akses ke rombel ini.');
}

/** Members of a rombel, ordered by name. */
function rombel_members(int $rombelId): array
{
    $st = db()->prepare(
        "SELECT s.id, s.nisn, s.nama, s.jk
         FROM rombel_members rm
         JOIN students s ON s.id = rm.student_id
         WHERE rm.rombel_id = :r AND s.deleted_at IS NULL
         ORDER BY s.nama"
    );
    $st->execute(['r' => $rombelId]);
    return $st->fetchAll();
}

/**
 * Members of a rombel yang boleh dinilai untuk subject tertentu.
 *
 * Jika subject adalah shadow subject dari elective_class (elective_class_id IS NOT NULL),
 * maka hanya siswa yang sudah di-assign ke elective_class tersebut pada semester aktif
 * yang dikembalikan — bukan semua siswa di rombel.
 *
 * Untuk subject biasa (non-elective), fungsi ini identik dengan rombel_members().
 */
function rombel_members_for_subject(int $rombelId, int $subjectId, string $semester): array
{
    $pdo = db();

    // Cek apakah subject ini adalah shadow subject dari elective_class
    $st = $pdo->prepare(
        "SELECT elective_class_id FROM subjects WHERE id = :s AND deleted_at IS NULL"
    );
    $st->execute(['s' => $subjectId]);
    $row = $st->fetch();

    if (!$row) {
        return [];
    }

    $electiveClassId = $row['elective_class_id'] !== null ? (int)$row['elective_class_id'] : null;

    if ($electiveClassId === null) {
        // Subject biasa — kembalikan semua siswa di rombel
        return rombel_members($rombelId);
    }

    // Elective subject — hanya siswa yang di-assign ke opsi ini di semester ini
    $st = $pdo->prepare(
        "SELECT s.id, s.nisn, s.nama, s.jk
         FROM elective_assignments ea
         JOIN students s ON s.id = ea.student_id
         JOIN rombel_members rm ON rm.student_id = s.id AND rm.rombel_id = :r
         WHERE ea.elective_class_id = :ec
           AND ea.semester = :sem
           AND s.deleted_at IS NULL
         ORDER BY s.nama"
    );
    $st->execute(['r' => $rombelId, 'ec' => $electiveClassId, 'sem' => $semester]);
    return $st->fetchAll();
}

/** Fetch attendance for one rombel-date keyed by student_id. */
function attendance_for(int $rombelId, string $date): array
{
    $st = db()->prepare(
        "SELECT student_id, status, catatan
         FROM attendance WHERE rombel_id=:r AND tanggal=:d"
    );
    $st->execute(['r' => $rombelId, 'd' => $date]);
    $out = [];
    foreach ($st->fetchAll() as $row) {
        $out[(int)$row['student_id']] = $row;
    }
    return $out;
}

/** Recap for a rombel between two dates: per-student counts. */
function attendance_recap(int $rombelId, string $from, string $to): array
{
    $st = db()->prepare(
        "SELECT s.id, s.nisn, s.nama, s.jk,
                SUM(a.status='H') AS h,
                SUM(a.status='I') AS i,
                SUM(a.status='S') AS s_,
                SUM(a.status='A') AS a,
                COUNT(a.id) AS total
         FROM rombel_members rm
         JOIN students s ON s.id = rm.student_id
         LEFT JOIN attendance a
                ON a.student_id = s.id
               AND a.rombel_id  = rm.rombel_id
               AND a.tanggal BETWEEN :f AND :t
         WHERE rm.rombel_id = :r AND s.deleted_at IS NULL
         GROUP BY s.id, s.nisn, s.nama, s.jk
         ORDER BY s.nama"
    );
    $st->execute(['r' => $rombelId, 'f' => $from, 't' => $to]);
    return $st->fetchAll();
}

/** All distinct dates with attendance recorded for a rombel in [from..to]. */
function attendance_dates(int $rombelId, string $from, string $to): array
{
    $st = db()->prepare(
        "SELECT DISTINCT tanggal FROM attendance
         WHERE rombel_id=:r AND tanggal BETWEEN :f AND :t
         ORDER BY tanggal"
    );
    $st->execute(['r' => $rombelId, 'f' => $from, 't' => $to]);
    return array_column($st->fetchAll(), 'tanggal');
}
