<?php
/**
 * Stage 5 — Penilaian Harian SKP helpers.
 * Centralises:
 *   - period_bucket mapping (semester + PTS/PAS -> ENUM)
 *   - subject access scoping for the current user/rombel
 *   - topics + grade fetch helpers
 *   - attendance sync (peek the H/I/S/A status for a date)
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/scope.php';
require_once __DIR__ . '/attendance_helpers.php';

/** Three ranah definitions used everywhere in Stage 5+. */
function ranah_defs(): array
{
    return [
        'sikap'         => ['label' => 'Sikap',         'col' => 'nilai_sikap',        'class' => 'badge-info'],
        'pengetahuan'   => ['label' => 'Pengetahuan',   'col' => 'nilai_pengetahuan',  'class' => 'badge-primary'],
        'keterampilan'  => ['label' => 'Keterampilan',  'col' => 'nilai_keterampilan', 'class' => 'badge-success'],
    ];
}

/**
 * Extract ranah list from a topic row.
 * Handles both old ENUM format (single value) and new JSON format (array).
 * Returns array of ranah keys: ['sikap'], ['sikap','pengetahuan'], etc.
 */
function extract_ranah_from_topic(array $topic): array
{
    if (isset($topic['ranah_list']) && $topic['ranah_list']) {
        $list = $topic['ranah_list'];
        // If stored as JSON string, decode it
        if (is_string($list)) {
            $list = json_decode($list, true);
        }
        if (is_array($list)) {
            return array_filter($list, fn($r) => in_array($r, ['sikap','pengetahuan','keterampilan'], true));
        }
    }
    // Fallback to old single-value 'ranah' column for backward compatibility
    if (isset($topic['ranah']) && $topic['ranah']) {
        return [$topic['ranah']];
    }
    return [];
}

/**
 * Get the display name for a ranah.
 */
function ranah_label(string $ranah): string
{
    return ranah_defs()[$ranah]['label'] ?? $ranah;
}

/**
 * Get the column name for a ranah.
 */
function ranah_column(string $ranah): string
{
    return ranah_defs()[$ranah]['col'] ?? 'nilai_' . $ranah;
}

/** Map (semester, period) -> period_bucket enum. */
function period_bucket(string $semester, string $period): string
{
    if ($semester === 'ganjil') return $period === 'PTS' ? 'tengah_ganjil' : 'ganjil';
    return $period === 'PTS' ? 'tengah_genap' : 'genap';
}

/** Active period_bucket from session scope. */
function active_bucket(): string
{
    $sc = active_scope();
    return period_bucket($sc['semester'], $sc['period']);
}

/**
 * Subjects the user can grade in this rombel for the active scope.
 * - administrator/admin/kepsek: every subject mapped via rombel_subject_teachers (or all subjects).
 * - guru: only subjects where they are mapped via rombel_subject_teachers, OR the wali (any subject).
 */
function accessible_subjects_for_rombel(array $user, int $rombelId): array
{
    $sc  = active_scope();
    $pdo = db();
    $role = $user['role'] ?? '';

    if (in_array($role, ['administrator','admin','kepsek'], true)) {
        $st = $pdo->prepare(
            "SELECT DISTINCT s.id, s.kode, s.nama, e.kode AS elective_kode
             FROM subjects s
             LEFT JOIN rombel_subject_teachers rst
                    ON rst.subject_id = s.id
                   AND rst.rombel_id  = :r
                   AND (rst.semester IS NULL OR rst.semester = :sem)
             LEFT JOIN elective_classes ec ON ec.id = s.elective_class_id
             LEFT JOIN electives e ON e.id = ec.elective_id
             WHERE s.deleted_at IS NULL
               AND s.academic_year_id = :y
             ORDER BY s.nama"
        );
        $st->execute(['r' => $rombelId, 'sem' => $sc['semester'], 'y' => $sc['year_id']]);
        return $st->fetchAll();
    }

    if ($role === 'guru') {
        // resolve teacher_id
        $stt = $pdo->prepare("SELECT id FROM teachers WHERE user_id=:u");
        $stt->execute(['u' => $user['id']]);
        $tid = (int)($stt->fetchColumn() ?: 0);

        $st = $pdo->prepare(
            "SELECT DISTINCT s.id, s.kode, s.nama, e.kode AS elective_kode
             FROM rombel_subject_teachers rst
             JOIN subjects s ON s.id = rst.subject_id
             LEFT JOIN elective_classes ec ON ec.id = s.elective_class_id
             LEFT JOIN electives e ON e.id = ec.elective_id
             WHERE rst.rombel_id = :r
               AND rst.teacher_id = :t
               AND (rst.semester IS NULL OR rst.semester = :sem)
               AND s.deleted_at IS NULL
               AND s.academic_year_id = :y
             ORDER BY s.nama"
        );
        $st->execute(['r' => $rombelId, 't' => $tid, 'sem' => $sc['semester'], 'y' => $sc['year_id']]);
        return $st->fetchAll();
    }

    return [];
}

/** Throws 403 if subject is not accessible for the user/rombel. */
function assert_can_grade_subject(array $user, int $rombelId, int $subjectId): void
{
    foreach (accessible_subjects_for_rombel($user, $rombelId) as $s) {
        if ((int)$s['id'] === $subjectId) return;
    }
    http_response_code(403);
    die('403 — Anda tidak memiliki akses untuk menilai mata pelajaran ini di rombel tsb.');
}

/** All non-deleted topics for rombel/subject in active semester. */
function topics_for(int $rombelId, int $subjectId, ?string $semester = null): array
{
    $sem = $semester ?: active_semester();
    $st  = db()->prepare(
        "SELECT id, kode, judul, ranah, ranah_list, kategori, bobot
         FROM subject_topics
         WHERE rombel_id=:r AND subject_id=:s AND semester=:sem AND deleted_at IS NULL
         ORDER BY ranah_list IS NULL, ranah, kode, judul"
    );
    $st->execute(['r' => $rombelId, 's' => $subjectId, 'sem' => $sem]);
    return $st->fetchAll();
}

/** A single topic by id, or null. */
function topic_by_id(int $topicId): ?array
{
    $st = db()->prepare("SELECT * FROM subject_topics WHERE id=:i AND deleted_at IS NULL");
    $st->execute(['i' => $topicId]);
    $row = $st->fetch();
    return $row ?: null;
}

/**
 * Existing daily grades for a (rombel, subject, topic, semester, bucket, date),
 * keyed by student_id. Returns the full row so the renderer can show the
 * per-ranah column we care about.
 */
function grades_for_topic_date(int $rombelId, int $subjectId, int $topicId, string $semester, string $bucket, string $date): array
{
    $st = db()->prepare(
        "SELECT * FROM grades_daily
         WHERE rombel_id=:r AND subject_id=:s AND topic_id=:t
           AND semester=:sem AND period_bucket=:b AND tanggal=:d"
    );
    $st->execute([
        'r' => $rombelId, 's' => $subjectId, 't' => $topicId,
        'sem' => $semester, 'b' => $bucket, 'd' => $date,
    ]);
    $out = [];
    foreach ($st->fetchAll() as $row) {
        $out[(int)$row['student_id']] = $row;
    }
    return $out;
}

/**
 * Per-topic recap for a rombel+subject in active semester.
 * Returns nested: [student_id => [topic_id => {ranah => avg_value}, ...]].
 * Handles both single-ranah and multi-ranah topics.
 */
function recap_topics(int $rombelId, int $subjectId, string $semester): array
{
    $pdo = db();
    $topics = topics_for($rombelId, $subjectId, $semester);
    if (!$topics) return ['topics' => [], 'data' => []];

    $st = $pdo->prepare(
        "SELECT student_id, topic_id,
                AVG(nilai_sikap)        AS av_si,
                AVG(nilai_pengetahuan)  AS av_pe,
                AVG(nilai_keterampilan) AS av_ke
         FROM grades_daily
         WHERE rombel_id=:r AND subject_id=:s AND semester=:sem
           AND topic_id IS NOT NULL
         GROUP BY student_id, topic_id"
    );
    $st->execute(['r' => $rombelId, 's' => $subjectId, 'sem' => $semester]);

    $rows = $st->fetchAll();
    $byTopic = [];
    foreach ($topics as $t) $byTopic[(int)$t['id']] = $t;

    // SPK mode: every topic always carries all 3 ranah (Sikap, Pengetahuan,
    // Keterampilan) regardless of the topic's stored ranah_list. The daily
    // input form always writes all three columns, so the recap must read them
    // back the same way to stay in sync.
    $data = [];
    foreach ($rows as $r) {
        $sid = (int)$r['student_id'];
        $tid = (int)$r['topic_id'];
        if (!isset($byTopic[$tid])) continue;

        $data[$sid][$tid] = [
            'sikap'        => $r['av_si'] !== null ? round((float)$r['av_si'], 2) : null,
            'pengetahuan'  => $r['av_pe'] !== null ? round((float)$r['av_pe'], 2) : null,
            'keterampilan' => $r['av_ke'] !== null ? round((float)$r['av_ke'], 2) : null,
        ];
    }

    return ['topics' => $topics, 'data' => $data];
}

/**
 * Weighted average per ranah for a student over all topics in
 * rombel+subject+semester. Used as preview before final grading (Stage 6).
 *
 * SPK mode: every topic contributes to all 3 ranah (Sikap, Pengetahuan,
 * Keterampilan) with its full bobot. Each ranah is averaged independently
 * across topics — null values are skipped.
 *
 * Returns: [ranah => float|null].
 */
function weighted_average_ranah(int $rombelId, int $subjectId, int $studentId, string $semester): array
{
    $rec = recap_topics($rombelId, $subjectId, $semester);
    $sums = ['sikap' => [0.0, 0.0], 'pengetahuan' => [0.0, 0.0], 'keterampilan' => [0.0, 0.0]];

    foreach ($rec['topics'] as $t) {
        $tid = (int)$t['id'];
        $topicData = $rec['data'][$studentId][$tid] ?? null;
        if (!$topicData) continue;

        $w = (float)$t['bobot'];
        foreach (['sikap','pengetahuan','keterampilan'] as $ranah) {
            $val = $topicData[$ranah] ?? null;
            if ($val !== null) {
                $sums[$ranah][0] += $val * $w;
                $sums[$ranah][1] += $w;
            }
        }
    }

    $out = [];
    foreach ($sums as $r => [$num, $den]) {
        $out[$r] = $den > 0 ? round($num / $den, 2) : null;
    }
    return $out;
}

/**
 * Combined SPK average (rata-rata gabungan Sikap + Pengetahuan + Keterampilan)
 * untuk satu siswa pada (rombel, subject, semester).
 * Menghitung mean dari nilai per-ranah yang tersedia (mengabaikan ranah null).
 * Dipakai sebagai "nilai akhir gabungan" di Rekap, Nilai Akhir, dan Rapor.
 */
function weighted_average_overall(int $rombelId, int $subjectId, int $studentId, string $semester): ?float
{
    $w = weighted_average_ranah($rombelId, $subjectId, $studentId, $semester);
    $vals = array_filter([$w['sikap'], $w['pengetahuan'], $w['keterampilan']], fn($v) => $v !== null);
    if (!$vals) return null;
    return round(array_sum($vals) / count($vals), 2);
}

/**
 * Combined SPK average dari tiga nilai ranah yang sudah dihitung (mis. dari final_grades).
 * Mengembalikan null bila ketiganya null.
 */
function spk_overall(?float $si, ?float $pe, ?float $ke): ?float
{
    $vals = array_filter([$si, $pe, $ke], fn($v) => $v !== null);
    if (!$vals) return null;
    return round(array_sum($vals) / count($vals), 2);
}

/**
 * ---------------------------------------------------------------------------
 * KKM (Kriteria Ketuntasan Minimal) helpers.
 *
 * KKM is stored per (subject_id, tingkat) in `subject_kkm`, where `tingkat`
 * is the numeric grade level (1-12) taken from `rombel.tingkat`. It is only
 * ever compared against the combined SPK average (Σ Gabungan), never against
 * individual ranah — Sikap is qualitative and is excluded by design.
 *
 * If no KKM row exists for a given subject+tingkat, helpers return null and
 * callers must treat that as "no threshold configured" (never assume 0/70 —
 * no red highlighting should be applied in that case).
 * ---------------------------------------------------------------------------
 */

/**
 * KKM value for one subject at one tingkat (1-12), or null if not set.
 * Pass $reset = true (used internally by subject_kkm_save()) to clear the
 * in-memory cache after a write, so a later read in the same request never
 * returns a stale value.
 */
function subject_kkm_for(int $subjectId, int $tingkat, bool $reset = false): ?float
{
    static $cache = [];
    if ($reset) { $cache = []; return null; }

    $key = $subjectId . ':' . $tingkat;
    if (array_key_exists($key, $cache)) return $cache[$key];

    $st = db()->prepare("SELECT kkm FROM subject_kkm WHERE subject_id = :s AND tingkat = :t");
    $st->execute(['s' => $subjectId, 't' => $tingkat]);
    $val = $st->fetchColumn();
    return $cache[$key] = ($val === false ? null : (float)$val);
}

/** Full KKM map for a subject: [tingkat => kkm]. Used by the admin edit form. */
function subject_kkm_map(int $subjectId): array
{
    $st = db()->prepare("SELECT tingkat, kkm FROM subject_kkm WHERE subject_id = :s");
    $st->execute(['s' => $subjectId]);
    $out = [];
    foreach ($st->fetchAll() as $row) {
        $out[(int)$row['tingkat']] = (float)$row['kkm'];
    }
    return $out;
}

/**
 * True jika nilai gabungan SPK berada di bawah KKM. Mengembalikan false
 * (tidak ada highlight) bila nilai atau KKM null -- tidak pernah berasumsi.
 */
function kkm_below(?float $value, ?float $kkm): bool
{
    if ($value === null || $kkm === null) return false;
    return $value < $kkm;
}

/**
 * Format angka KKM/nilai untuk tampilan: buang trailing zero desimal tanpa
 * merusak bilangan bulat (mis. 90.00 -> "90", 78.50 -> "78.5", 100.0 -> "100").
 * Catatan: TIDAK memakai rtrim($s,'0') pada string tanpa titik -- itu juga
 * akan memakan digit signifikan (mis. "90" jadi "9").
 */
function fmt_kkm(float $val): string
{
    $s = number_format($val, 2, '.', '');
    if (str_contains($s, '.')) {
        $s = rtrim($s, '0');
        $s = rtrim($s, '.');
    }
    return $s;
}
function tingkat_for_jenjang(string $jenjang): array
{
    return match ($jenjang) {
        'SD'  => [1, 2, 3, 4, 5, 6],
        'SMP' => [7, 8, 9],
        'SMA' => [10, 11, 12],
        default => [], // TK has no numeric tingkat / no KKM
    };
}

/** Given a tingkat (1-12), return its jenjang label, or null (e.g. for TK). */
function jenjang_for_tingkat(int $tingkat): ?string
{
    if ($tingkat >= 1 && $tingkat <= 6) return 'SD';
    if ($tingkat >= 7 && $tingkat <= 9) return 'SMP';
    if ($tingkat >= 10 && $tingkat <= 12) return 'SMA';
    return null;
}

/**
 * Replace all subject_kkm rows for a subject based on submitted jenjang
 * defaults + per-tingkat overrides. Call inside a transaction.
 *
 * $jenjangChecked: list of jenjang keys that are active for this subject (e.g. ['SD','SMP']).
 * $defaults: [jenjang => float] default KKM per jenjang.
 * $overrides: [tingkat => float] explicit per-tingkat overrides (optional, sparse).
 */
function subject_kkm_save(int $subjectId, array $jenjangChecked, array $defaults, array $overrides): void
{
    $pdo = db();
    $pdo->prepare("DELETE FROM subject_kkm WHERE subject_id = :s")->execute(['s' => $subjectId]);

    $ins = $pdo->prepare("INSERT INTO subject_kkm (subject_id, tingkat, kkm) VALUES (:s, :t, :k)");
    foreach ($jenjangChecked as $j) {
        if ($j === 'TK') continue; // TK has no numeric tingkat
        $default = $defaults[$j] ?? 70.0;
        foreach (tingkat_for_jenjang($j) as $t) {
            $kkm = $overrides[$t] ?? $default;
            $ins->execute(['s' => $subjectId, 't' => $t, 'k' => $kkm]);
        }
    }
    subject_kkm_for(0, 0, true); // invalidate cache so subsequent reads in this request are fresh
}
