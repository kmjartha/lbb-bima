<?php
/**
 * Stage 10 — Audit + Notification helpers.
 *
 * Read-only utilities. All write paths still go through audit() in helpers.php.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Paginated audit log query.
 *
 * @param array $f Filters: action, user_id (int), q (substring of target/user_label),
 *                 date_from (YYYY-MM-DD), date_to (YYYY-MM-DD)
 * @param int $page 1-indexed
 * @param int $per
 * @return array ['rows'=>[], 'total'=>int, 'page'=>int, 'pages'=>int, 'per'=>int]
 */
function audit_query(array $f = [], int $page = 1, int $per = 50): array
{
    $page = max(1, $page);
    $per  = max(10, min(200, $per));
    [$where, $bind] = audit_filter_sql($f);

    $sqlBase = "FROM audit_log a $where";
    $st = db()->prepare("SELECT COUNT(*) $sqlBase");
    $st->execute($bind);
    $total = (int)$st->fetchColumn();

    $offset = ($page - 1) * $per;
    $st = db()->prepare("SELECT a.* $sqlBase ORDER BY a.id DESC LIMIT $per OFFSET $offset");
    $st->execute($bind);
    $rows = $st->fetchAll();

    $pages = (int)max(1, ceil($total / $per));
    return compact('rows','total','page','pages','per');
}

/**
 * Stream the full filtered audit log to the browser as CSV.
 * Caller must NOT have produced output yet.
 */
function audit_export_csv(array $f = []): void
{
    [$where, $bind] = audit_filter_sql($f);
    $st = db()->prepare("SELECT id, created_at, user_id, user_label, action, target, ip, meta_json FROM audit_log a $where ORDER BY a.id DESC");
    $st->execute($bind);

    $fname = 'audit_log_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    $out = fopen('php://output', 'w');
    // BOM for Excel
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['id','created_at','user_id','user_label','action','target','ip','meta_json']);
    while ($r = $st->fetch()) {
        fputcsv($out, [
            $r['id'], $r['created_at'], $r['user_id'], $r['user_label'],
            $r['action'], $r['target'], $r['ip'], $r['meta_json'],
        ]);
    }
    fclose($out);
}

/** Build WHERE clause + bound params from filters. */
function audit_filter_sql(array $f): array
{
    $w = []; $b = [];
    if (!empty($f['action'])) {
        $w[] = 'a.action = :action'; $b['action'] = (string)$f['action'];
    }
    if (!empty($f['user_id'])) {
        $w[] = 'a.user_id = :uid'; $b['uid'] = (int)$f['user_id'];
    }
    if (!empty($f['q'])) {
        $w[] = '(a.target LIKE :q OR a.user_label LIKE :q OR a.action LIKE :q)';
        $b['q'] = '%' . str_replace(['%','_'], ['\\%','\\_'], $f['q']) . '%';
    }
    if (!empty($f['date_from'])) {
        $w[] = 'a.created_at >= :df'; $b['df'] = $f['date_from'] . ' 00:00:00';
    }
    if (!empty($f['date_to'])) {
        $w[] = 'a.created_at <= :dt'; $b['dt'] = $f['date_to'] . ' 23:59:59';
    }
    return [$w ? 'WHERE ' . implode(' AND ', $w) : '', $b];
}

/** Distinct action values for the filter dropdown. */
function audit_distinct_actions(): array
{
    return db()->query("SELECT DISTINCT action FROM audit_log ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
}

/** Distinct staff users that appear in the log (for filter). */
function audit_distinct_users(int $limit = 200): array
{
    return db()->query(
        "SELECT u.id, u.nama, u.niy, u.role
           FROM users u
          WHERE EXISTS (SELECT 1 FROM audit_log a WHERE a.user_id = u.id)
          ORDER BY u.nama LIMIT $limit"
    )->fetchAll();
}

/** Today's audit count grouped by action (for dashboard). */
function audit_today_by_action(int $limit = 8): array
{
    return db()->query(
        "SELECT action, COUNT(*) AS n
           FROM audit_log
          WHERE created_at >= CURDATE()
          GROUP BY action ORDER BY n DESC LIMIT $limit"
    )->fetchAll();
}

/* =====================================================================
 * Notification helpers — Kepsek pending review/publish queue.
 * Source of truth: final_grades.status in ('submitted','revised','approved').
 * Filtered by jenjang for kepsek (their own jenjang only).
 * ===================================================================== */

/** Number of final-grade rows awaiting review or publication for a kepsek. */
function notif_pending_review_count(?string $jenjang = null, ?int $yearId = null): int
{
    $sql = "SELECT COUNT(*) FROM final_grades fg
            JOIN rombel r ON r.id = fg.rombel_id
            WHERE fg.status IN ('submitted','revised','approved')";
    $b = [];
    if ($jenjang) { $sql .= " AND r.jenjang = :j"; $b['j'] = $jenjang; }
    if ($yearId !== null) { $sql .= " AND r.academic_year_id = :y"; $b['y'] = $yearId; }
    $st = db()->prepare($sql);
    $st->execute($b);
    return (int)$st->fetchColumn();
}

/**
 * List recent pending submissions or approved rows grouped by (rombel, subject, semester, period).
 */
function notif_pending_review_list(?string $jenjang = null, int $limit = 10, ?int $yearId = null): array
{
    $sql = "
        SELECT fg.rombel_id, fg.subject_id, fg.semester, fg.period_kind,
               r.nama AS rombel_nama, r.jenjang, r.tingkat,
               s.nama AS subj_nama, s.kode AS subj_kode,
               COUNT(*) AS n_rows,
               MAX(fg.updated_at) AS last_at
          FROM final_grades fg
          JOIN rombel r   ON r.id = fg.rombel_id
          JOIN subjects s ON s.id = fg.subject_id
         WHERE fg.status IN ('submitted','revised','approved')";
    $b = [];
    if ($jenjang) { $sql .= " AND r.jenjang = :j"; $b['j'] = $jenjang; }
    if ($yearId !== null) { $sql .= " AND r.academic_year_id = :y"; $b['y'] = $yearId; }
    $sql .= " GROUP BY fg.rombel_id, fg.subject_id, fg.semester, fg.period_kind
              ORDER BY last_at DESC LIMIT $limit";
    $st = db()->prepare($sql);
    $st->execute($b);
    return $st->fetchAll();
}

/**
 * Per-role dashboard counters used by the upgraded dashboard widget.
 */
function dashboard_counters_for(array $user, ?int $yearId = null): array
{
    $pdo = db();
    $role = $user['role'] ?? '';
    $jenjang = $user['jenjang'] ?? null;
    if ($yearId === null) {
        $yearId = active_scope()['year_id'];
    }

    $st = $pdo->prepare(
        "SELECT COUNT(DISTINCT s.id) FROM students s
         JOIN rombel_members rm ON rm.student_id = s.id
         JOIN rombel r ON r.id = rm.rombel_id
         WHERE s.deleted_at IS NULL AND r.deleted_at IS NULL AND r.academic_year_id = :y"
    );
    $st->execute(['y' => $yearId]);
    $studentsCount = (int)$st->fetchColumn();

    $st = $pdo->prepare("SELECT COUNT(*) FROM rombel WHERE academic_year_id = :y AND deleted_at IS NULL");
    $st->execute(['y' => $yearId]);
    $rombelsCount = (int)$st->fetchColumn();

    $st = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE deleted_at IS NULL AND academic_year_id = :y");
    $st->execute(['y' => $yearId]);
    $subjectCount = (int)$st->fetchColumn();

    $base = [
        'siswa'  => $studentsCount,
        'guru'   => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='guru' AND deleted_at IS NULL")->fetchColumn(),
        'rombel' => $rombelsCount,
        'mapel'  => $subjectCount,
    ];

    if ($role === 'kepsek' && $jenjang) {
        $st = $pdo->prepare(
            "SELECT COUNT(DISTINCT s.id) FROM students s
             JOIN rombel_members rm ON rm.student_id = s.id
             JOIN rombel r ON r.id = rm.rombel_id
             WHERE s.deleted_at IS NULL AND r.deleted_at IS NULL
               AND r.jenjang = :j AND r.academic_year_id = :y"
        );
        $st->execute(['j' => $jenjang, 'y' => $yearId]);
        $base['siswa_jenjang'] = (int)$st->fetchColumn();

        $st = $pdo->prepare("SELECT COUNT(*) FROM rombel WHERE deleted_at IS NULL AND jenjang = :j AND academic_year_id = :y");
        $st->execute(['j' => $jenjang, 'y' => $yearId]);
        $base['rombel_jenjang'] = (int)$st->fetchColumn();

        $base['pending_review'] = notif_pending_review_count($jenjang, $yearId);
        $st = $pdo->prepare("SELECT COUNT(*) FROM final_grades fg JOIN rombel r ON r.id = fg.rombel_id WHERE fg.status='published' AND r.academic_year_id = :y");
        $st->execute(['y' => $yearId]);
        $base['published'] = (int)$st->fetchColumn();
    }

    if (in_array($role, ['guru', 'kepsek'], true)) {
        // rombel di mana user adalah wali atau pengampu via teachers
        $st = $pdo->prepare(
            "SELECT COUNT(DISTINCT r.id) FROM rombel r
              LEFT JOIN teachers t ON t.user_id = :uid1
              LEFT JOIN rombel_subject_teachers rst ON rst.rombel_id = r.id AND rst.teacher_id = t.id
             WHERE r.deleted_at IS NULL AND r.academic_year_id = :y
               AND (r.wali_id = :uid2 OR rst.id IS NOT NULL)"
        );
        $st->execute(['uid1' => $user['id'], 'uid2' => $user['id'], 'y' => $yearId]);
        $base['my_rombel'] = (int)$st->fetchColumn();

        $st = $pdo->prepare(
            "SELECT COUNT(DISTINCT s.id) FROM subjects s
              JOIN rombel_subject_teachers rst ON rst.subject_id = s.id
              JOIN rombel r ON r.id = rst.rombel_id
              JOIN teachers t ON t.id = rst.teacher_id
             WHERE s.deleted_at IS NULL AND r.deleted_at IS NULL
               AND r.academic_year_id = :y AND t.user_id = :uid"
        );
        $st->execute(['uid' => $user['id'], 'y' => $yearId]);
        $base['my_subjects'] = (int)$st->fetchColumn();

        $st = $pdo->prepare(
            "SELECT COUNT(DISTINCT s.id) FROM students s
              JOIN rombel_members rm ON rm.student_id = s.id
              JOIN rombel r ON r.id = rm.rombel_id
              LEFT JOIN teachers t ON t.user_id = :uid1
              LEFT JOIN rombel_subject_teachers rst ON rst.rombel_id = r.id AND rst.teacher_id = t.id
             WHERE s.deleted_at IS NULL AND r.deleted_at IS NULL
               AND r.academic_year_id = :y
               AND (r.wali_id = :uid2 OR rst.id IS NOT NULL)"
        );
        $st->execute(['uid1' => $user['id'], 'uid2' => $user['id'], 'y' => $yearId]);
        $base['my_students'] = (int)$st->fetchColumn();

        $st = $pdo->prepare(
            "SELECT COUNT(*) FROM final_grades fg
              JOIN rombel r ON r.id = fg.rombel_id
              LEFT JOIN teachers t ON t.user_id = :uid1
              LEFT JOIN rombel_subject_teachers rst ON rst.rombel_id = r.id AND rst.teacher_id = t.id
             WHERE r.deleted_at IS NULL AND r.academic_year_id = :y
               AND (r.wali_id = :uid2 OR rst.id IS NOT NULL)
               AND fg.status = 'draft'"
        );
        $st->execute(['uid1' => $user['id'], 'uid2' => $user['id'], 'y' => $yearId]);
        $base['my_draft_grades'] = (int)$st->fetchColumn();
    }

    return $base;
}
