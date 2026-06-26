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

/** Workflow status definitions. */
function fg_statuses(): array
{
    return [
        'draft'     => ['label' => 'Draft',     'class' => 'badge'],
        'submitted' => ['label' => 'Diajukan',  'class' => 'badge-info'],
        'revised'   => ['label' => 'Revisi',    'class' => 'badge-warning'],
        'approved'  => ['label' => 'Disetujui', 'class' => 'badge-success'],
        'published' => ['label' => 'Terbit',    'class' => 'badge-primary'],
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
    $needsReview = in_array($status, ['approved','revised','published'], true);
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
    $out = ['draft'=>0,'submitted'=>0,'revised'=>0,'approved'=>0,'published'=>0];
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
          AND fg.status IN ('submitted','revised','approved','published')
          AND r.academic_year_id = :y";
    $params = ['sem'=>$semester,'p'=>$period,'y'=>$yearId];
    if (($user['role'] ?? '') === 'kepsek' && !empty($user['jenjang'])) {
        $sql .= " AND r.jenjang = :j";
        $params['j'] = $user['jenjang'];
    }
    $sql .= " ORDER BY CASE fg.status WHEN 'submitted' THEN 0 WHEN 'revised' THEN 1 WHEN 'approved' THEN 2 WHEN 'published' THEN 3 ELSE 4 END,
                      COALESCE(u.nama, 'zzz'), r.jenjang, r.tingkat, r.nama, sb.nama, st.nama";
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}