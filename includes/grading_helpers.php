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
            "SELECT DISTINCT s.id, s.kode, s.nama
             FROM subjects s
             LEFT JOIN rombel_subject_teachers rst
                    ON rst.subject_id = s.id
                   AND rst.rombel_id  = :r
                   AND (rst.semester IS NULL OR rst.semester = :sem)
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

        // wali_id check
        $wal = $pdo->prepare("SELECT wali_id FROM rombel WHERE id=:r");
        $wal->execute(['r' => $rombelId]);
        $isWali = (int)($wal->fetchColumn() ?: 0) === (int)$user['id'];

        if ($isWali) {
            $st = $pdo->prepare(
                "SELECT s.id, s.kode, s.nama FROM subjects s
                 WHERE s.deleted_at IS NULL
                   AND s.academic_year_id = :y
                 ORDER BY s.nama"
            );
            $st->execute(['y' => $sc['year_id']]);
            return $st->fetchAll();
        }

        $st = $pdo->prepare(
            "SELECT DISTINCT s.id, s.kode, s.nama
             FROM rombel_subject_teachers rst
             JOIN subjects s ON s.id = rst.subject_id
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
