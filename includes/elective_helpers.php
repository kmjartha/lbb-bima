<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/scope.php';
require_once __DIR__ . '/grading_helpers.php'; // subject_kkm_save(), tingkat_for_jenjang()

function electives_for_year(int $yearId): array
{
    $st = db()->prepare(
        "SELECT * FROM electives WHERE academic_year_id = :y AND deleted_at IS NULL ORDER BY kode, nama"
    );
    $st->execute(['y' => $yearId]);
    return $st->fetchAll();
}

function elective_by_id(int $id, ?int $yearId = null): ?array
{
    $sql = "SELECT * FROM electives WHERE id = :id AND deleted_at IS NULL";
    $params = ['id' => $id];
    if ($yearId !== null) {
        $sql .= " AND academic_year_id = :y";
        $params['y'] = $yearId;
    }
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row ?: null;
}

function elective_classes(int $electiveId): array
{
    $st = db()->prepare(
        "SELECT id, nama, kapasitas, subject_id
         FROM elective_classes
         WHERE elective_id = :e AND deleted_at IS NULL
         ORDER BY id"
    );
    $st->execute(['e' => $electiveId]);
    return $st->fetchAll();
}

function elective_rombels_for(int $electiveId): array
{
    $st = db()->prepare(
        "SELECT r.id, r.jenjang, r.tingkat, r.nama
         FROM elective_rombels er
         JOIN rombel r ON r.id = er.rombel_id
         WHERE er.elective_id = :e AND r.deleted_at IS NULL
         ORDER BY FIELD(r.jenjang,'SD','SMP','SMA'), r.tingkat, r.nama"
    );
    $st->execute(['e' => $electiveId]);
    return $st->fetchAll();
}

function elective_students(int $electiveId): array
{
    $st = db()->prepare(
        "SELECT DISTINCT s.id, s.nisn, s.nama,
                r.jenjang, r.tingkat, r.nama AS rombel_nama
         FROM elective_rombels er
         JOIN rombel_members rm ON rm.rombel_id = er.rombel_id
         JOIN students s ON s.id = rm.student_id
         JOIN rombel r ON r.id = rm.rombel_id
         WHERE er.elective_id = :e
           AND s.deleted_at IS NULL
           AND r.deleted_at IS NULL
         ORDER BY r.jenjang, r.tingkat, r.nama, s.nama"
    );
    $st->execute(['e' => $electiveId]);
    return $st->fetchAll();
}

function elective_rombel_ids(int $electiveId): array
{
    $st = db()->prepare("SELECT rombel_id FROM elective_rombels WHERE elective_id = :e");
    $st->execute(['e' => $electiveId]);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}

function elective_class_counts(int $electiveId, string $semester): array
{
    $st = db()->prepare(
        "SELECT elective_class_id, COUNT(*) AS cnt
         FROM elective_assignments
         WHERE elective_id = :e AND semester = :sem
         GROUP BY elective_class_id"
    );
    $st->execute(['e' => $electiveId, 'sem' => $semester]);
    $out = [];
    foreach ($st->fetchAll() as $row) {
        $out[(int)$row['elective_class_id']] = (int)$row['cnt'];
    }
    return $out;
}

function electives_for_rombel(int $rombelId): array
{
    $sc = active_scope();
    $st = db()->prepare(
        "SELECT DISTINCT e.*
         FROM electives e
         JOIN elective_rombels er ON er.elective_id = e.id
         WHERE er.rombel_id = :r
           AND e.academic_year_id = :y
           AND e.deleted_at IS NULL
         ORDER BY e.nama"
    );
    $st->execute(['r' => $rombelId, 'y' => $sc['year_id']]);
    return $st->fetchAll();
}

function elective_rombel_options(int $yearId, string $jenjang): array
{
    $st = db()->prepare(
        "SELECT id, jenjang, tingkat, nama
         FROM rombel
         WHERE academic_year_id = :y
           AND jenjang = :j
           AND deleted_at IS NULL
         ORDER BY tingkat, nama"
    );
    $st->execute(['y' => $yearId, 'j' => $jenjang]);
    return $st->fetchAll();
}

/* =========================================================================
 * Elective-option -> "shadow subject" sync.
 *
 * Each elective_classes row (opsi mapel pilihan, e.g. "Grafis" under the
 * "CGV" elective) gets mirrored into a real subjects row so every existing
 * subject-driven page (Guru Pengampu, Subjek Penilaian, Nilai Akhir, Leger,
 * Rapor, KKM) picks it up automatically via their normal subject_id-based
 * queries -- with no changes needed to those FK-bound tables.
 *
 * subjects.elective_class_id marks a row as shadow/elective-derived so the
 * regular Mapel admin page (subjects.php) can label and protect it instead
 * of letting admins edit/delete it out of sync with the source of truth.
 * ========================================================================= */

/**
 * Build a unique subjects.kode candidate for an elective option name,
 * scoped to one academic year (matches the uq_subject_year_kode constraint).
 * Strips to A-Z0-9, uppercases, truncates to fit the 20-char column even
 * after a numeric collision suffix is appended.
 */
function elective_subject_kode_for(PDO $pdo, int $yearId, string $optionName, ?int $excludeSubjectId = null): string
{
    $base = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $optionName) ?? '');
    if ($base === '') {
        $base = 'OPSI';
    }
    $base = substr($base, 0, 20);

    $check = $pdo->prepare(
        "SELECT 1 FROM subjects WHERE academic_year_id = :y AND kode = :k"
        . ($excludeSubjectId ? " AND id != :ex" : '')
    );

    $candidate = $base;
    $suffix = 1;
    while (true) {
        $params = ['y' => $yearId, 'k' => $candidate];
        if ($excludeSubjectId) {
            $params['ex'] = $excludeSubjectId;
        }
        $check->execute($params);
        if (!$check->fetchColumn()) {
            return $candidate;
        }
        $suffix++;
        $suffixStr = (string)$suffix;
        $candidate = substr($base, 0, 20 - strlen($suffixStr)) . $suffixStr;
    }
}

/**
 * Create or update the shadow subject for one elective option, and keep its
 * jenjang map + default KKM rows in sync with the parent elective.
 *
 * Call this whenever an elective option is saved (created or renamed) from
 * admin/electives.php, inside the same transaction as that save.
 *
 * - nama       = the option's own name (e.g. "Grafis"), NOT the elective's
 *                name ("CGV") -- the elective only supplies grouping info.
 * - category_id = the elective's chosen mapel category.
 * - jenjang    = the elective's jenjang (one jenjang per elective).
 *
 * Existing teacher-set KKM values are preserved on update (KKM defaults are
 * only applied the first time a shadow subject is created).
 */
function elective_class_sync_subject(
    int $electiveClassId,
    string $optionName,
    int $electiveYearId,
    string $electiveJenjang,
    int $electiveCategoryId
): int {
    $pdo = db();

    $st = $pdo->prepare("SELECT subject_id FROM elective_classes WHERE id = :id");
    $st->execute(['id' => $electiveClassId]);
    $existingSubjectId = $st->fetchColumn();
    $existingSubjectId = $existingSubjectId ? (int)$existingSubjectId : null;

    if ($existingSubjectId) {
        // Shadow subject already exists -- update name/category, restore if
        // it was previously soft-deleted, but keep its own kode (kode is
        // not user-facing for shadow subjects, no need to reshuffle it).
        $pdo->prepare(
            "UPDATE subjects SET nama = :n, category_id = :c, deleted_at = NULL, updated_at = NOW()
             WHERE id = :id"
        )->execute(['n' => $optionName, 'c' => $electiveCategoryId, 'id' => $existingSubjectId]);
        $subjectId = $existingSubjectId;
    } else {
        $kode = elective_subject_kode_for($pdo, $electiveYearId, $optionName);
        $pdo->prepare(
            "INSERT INTO subjects (academic_year_id, kode, nama, category_id, elective_class_id)
             VALUES (:y, :k, :n, :c, :ec)"
        )->execute([
            'y' => $electiveYearId,
            'k' => $kode,
            'n' => $optionName,
            'c' => $electiveCategoryId,
            'ec' => $electiveClassId,
        ]);
        $subjectId = (int)$pdo->lastInsertId();
        $pdo->prepare("UPDATE elective_classes SET subject_id = :s WHERE id = :id")
            ->execute(['s' => $subjectId, 'id' => $electiveClassId]);
    }

    // Keep jenjang map in sync with the elective's own jenjang (single jenjang).
    $pdo->prepare("DELETE FROM subject_jenjang_map WHERE subject_id = :s")->execute(['s' => $subjectId]);
    $pdo->prepare("INSERT INTO subject_jenjang_map (subject_id, jenjang) VALUES (:s, :j)")
        ->execute(['s' => $subjectId, 'j' => $electiveJenjang]);

    // Keep KKM rows aligned with the elective's jenjang. If this is the first
    // sync (no KKM rows yet), seed sensible defaults (70). If the elective's
    // jenjang changed since the last sync (e.g. SD -> SMP), the old jenjang's
    // tingkat rows would otherwise be orphaned (subject_kkm has no FK to
    // subject_jenjang_map) -- detect that and rebuild for the new jenjang.
    $existingTingkat = $pdo->prepare("SELECT tingkat FROM subject_kkm WHERE subject_id = :s");
    $existingTingkat->execute(['s' => $subjectId]);
    $existingTingkat = array_map('intval', $existingTingkat->fetchAll(PDO::FETCH_COLUMN));

    $expectedTingkat = tingkat_for_jenjang($electiveJenjang);
    $jenjangMismatch = $existingTingkat && array_diff($existingTingkat, $expectedTingkat) !== [];

    if (($jenjangMismatch || !$existingTingkat) && $electiveJenjang !== 'TK') {
        subject_kkm_save($subjectId, [$electiveJenjang], [], []);
    }

    return $subjectId;
}

/**
 * Soft-delete the shadow subject for an elective option that was removed
 * or whose parent elective was deleted. Grade history (grades_daily,
 * final_grades, etc.) stays intact via ON DELETE CASCADE-free soft delete;
 * the option simply stops appearing as a selectable subject going forward.
 */
function elective_class_archive_subject(int $electiveClassId): void
{
    $pdo = db();
    $st = $pdo->prepare("SELECT subject_id FROM elective_classes WHERE id = :id");
    $st->execute(['id' => $electiveClassId]);
    $subjectId = $st->fetchColumn();
    if ($subjectId) {
        $pdo->prepare("UPDATE subjects SET deleted_at = NOW() WHERE id = :id")
            ->execute(['id' => (int)$subjectId]);
    }
}
