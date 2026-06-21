<?php
/**
 * Stage 9 — Parent helpers.
 *
 * Centralizes parent-portal data access and gating. All functions assume the
 * caller is an authenticated parent (see require_parent()) and resolve data
 * for the *bound* student only — never accept a student_id from request input.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/scope.php';
require_once __DIR__ . '/report_helpers.php';
require_once __DIR__ . '/attendance_helpers.php';
require_once __DIR__ . '/wali_helpers.php';

/** Full student row for the logged-in parent (with safe fallback). */
function parent_student(array $p): array
{
    $s = student_by_id((int)$p['student_id']);
    if (!$s) { parent_logout(); redirect('parent/login.php'); }
    return $s;
}

/**
 * Resolve the student's rombel for a given academic year.
 * Returns rombel row or null when the student is not enrolled that year.
 */
function parent_rombel_for_year(int $studentId, int $yearId): ?array
{
    $st = db()->prepare(
        "SELECT r.*
           FROM rombel_members rm
           JOIN rombel r ON r.id = rm.rombel_id
          WHERE rm.student_id = :s AND r.academic_year_id = :y AND r.deleted_at IS NULL
          ORDER BY rm.joined_at DESC LIMIT 1"
    );
    $st->execute(['s' => $studentId, 'y' => $yearId]);
    $r = $st->fetch();
    return $r ?: null;
}

/** Wali kelas (user) for a rombel, if any. */
function rombel_wali_user(int $rombelId): ?array
{
    $st = db()->prepare(
        "SELECT u.id, u.nama, u.niy
           FROM rombel r LEFT JOIN users u ON u.id = r.wali_id
          WHERE r.id = :r"
    );
    $st->execute(['r' => $rombelId]);
    $r = $st->fetch();
    return ($r && $r['id']) ? $r : null;
}

/**
 * For each (semester, period) in the active year, return whether the student's
 * report is published. Used by parent home and rapor selector.
 */
function parent_publish_matrix(int $studentId, int $yearId): array
{
    $rombel = parent_rombel_for_year($studentId, $yearId);
    $matrix = [];
    foreach (['ganjil','genap'] as $sem) {
        foreach (['PTS','PAS'] as $pk) {
            $matrix[$sem][$pk] = $rombel
                ? rapor_is_published((int)$rombel['id'], $studentId, $sem, $pk, $yearId)
                : false;
        }
    }
    return $matrix;
}

/** Convenience: quickly check the *active scope* for the parent. */
function parent_scope_published(array $student): bool
{
    $sc = active_scope();
    $rombel = parent_rombel_for_year((int)$student['id'], (int)$sc['year_id']);
    if (!$rombel) return false;
    return rapor_is_published((int)$rombel['id'], (int)$student['id'], $sc['semester'], $sc['period'], (int)$sc['year_id']);
}

/**
 * Attendance summary for a student for the active semester/year.
 * Returns ['h'=>..,'i'=>..,'s'=>..,'a'=>..,'total'=>..].
 */
function parent_attendance_summary(int $studentId, int $rombelId, string $semester, int $yearId): array
{
    $all = attendance_summary_for_rombel($rombelId, $semester, $yearId);
    return $all[$studentId] ?? ['h'=>0,'i'=>0,'s'=>0,'a'=>0,'total'=>0];
}

/**
 * Daily attendance log for the student (most recent N rows).
 */
function parent_attendance_log(int $studentId, int $rombelId, string $semester, int $yearId, int $limit = 60): array
{
    $st = db()->prepare(
        "SELECT a.tanggal, a.status, a.keterangan
           FROM attendance a
          WHERE a.student_id=:s AND a.rombel_id=:r AND a.semester=:sem AND a.academic_year_id=:y
          ORDER BY a.tanggal DESC LIMIT $limit"
    );
    $st->execute(['s'=>$studentId,'r'=>$rombelId,'sem'=>$semester,'y'=>$yearId]);
    return $st->fetchAll();
}

/**
 * Final-grades rows for the student in the (rombel, sem, period). Only
 * returned when status = 'published' (parent gating).
 */
function parent_published_grades(int $studentId, int $rombelId, string $semester, string $period): array
{
    $st = db()->prepare(
        "SELECT fg.*, s.kode AS subj_kode, s.nama AS subj_nama, c.nama AS kategori_nama
           FROM final_grades fg
           JOIN subjects s ON s.id = fg.subject_id
           LEFT JOIN subject_categories c ON c.id = s.category_id
          WHERE fg.student_id=:st AND fg.rombel_id=:r
            AND fg.semester=:sem AND fg.period_kind=:p
            AND fg.status='published'
          ORDER BY c.nama IS NULL, c.nama, s.nama"
    );
    $st->execute(['st'=>$studentId,'r'=>$rombelId,'sem'=>$semester,'p'=>$period]);
    return $st->fetchAll();
}

/**
 * Nilai akhir gabungan SPK (Sikap + Pengetahuan + Keterampilan) per baris,
 * lalu rata-rata seluruh mapel. Inilah angka tunggal yang ditampilkan ke ortu di rapor.
 */
function parent_grades_overall_avg(array $rows): ?float
{
    $vals = [];
    foreach ($rows as $r) {
        $si = $r['nilai_sikap']        !== null ? (float)$r['nilai_sikap']        : null;
        $pe = $r['nilai_pengetahuan'] !== null ? (float)$r['nilai_pengetahuan'] : null;
        $ke = $r['nilai_keterampilan'] !== null ? (float)$r['nilai_keterampilan'] : null;
        $parts = array_filter([$si, $pe, $ke], fn($v) => $v !== null);
        if ($parts) $vals[] = array_sum($parts) / count($parts);
    }
    if (!$vals) return null;
    return array_sum($vals) / count($vals);
}

/**
 * Wali note for the student in the active (semester, period). Only shown when
 * the period is published.
 */
function parent_wali_note(int $studentId, int $rombelId, string $semester, string $period): ?string
{
    $rows = wali_notes_for($rombelId, $semester, $period);
    return $rows[$studentId] ?? null;
}

/**
 * Change parent password. Throws on validation failure. Clears must_change_pw.
 */
function parent_change_password(int $parentAuthId, string $newPw): void
{
    if (strlen($newPw) < 8) throw new RuntimeException('Password minimal 8 karakter.');
    if (!preg_match('/[A-Za-z]/', $newPw) || !preg_match('/\d/', $newPw)) {
        throw new RuntimeException('Password harus berisi huruf dan angka.');
    }
    $hash = password_hash($newPw, PASSWORD_DEFAULT);
    db()->prepare("UPDATE parents_auth SET password_hash=:h, must_change_pw=0 WHERE id=:i")
        ->execute(['h'=>$hash,'i'=>$parentAuthId]);
    if (!empty($_SESSION['parent'])) $_SESSION['parent']['must_change_pw'] = 0;
    audit('parent_change_pw', 'parent_auth:' . $parentAuthId);
}

/** Pretty-print scope label for the parent UI. */
function parent_scope_label(array $sc): string
{
    return 'TA ' . $sc['year'] . ' · ' . ucfirst($sc['semester']) . ' · ' . $sc['period'];
}

/**
 * Allow the parent to switch only semester+period within the *active* year.
 * (Parents are not allowed to switch years — they always see the active one.)
 */
function parent_set_period(?string $semester, ?string $period): void
{
    $y = active_year();
    set_scope((int)$y['id'], $semester, $period);
}
